<?php

declare(strict_types=1);

namespace App\Service\AlertRules;

use App\Entity\AlertEvent;
use App\Entity\AlertRule;
use App\Repository\AlertEventRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service for managing alert cooldowns and deduplication.
 *
 * Prevents alert fatigue by ensuring the same alert is not
 * triggered repeatedly within a cooldown period.
 */
class AlertCooldownManager
{
    /**
     * In-memory cooldown cache for when database/cache is not available.
     *
     * @var array<string, int>
     */
    private array $memoryCache = [];

    public function __construct(
        private readonly ?AlertEventRepository $alertEventRepository,
        private readonly ?CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Check if an alert is currently in cooldown.
     *
     * @param AlertRule            $rule        The alert rule
     * @param array<string, mixed> $eventData   The event data (for dedupe key extraction)
     *
     * @return bool True if the alert is in cooldown (should be suppressed)
     */
    public function isInCooldown(AlertRule $rule, array $eventData = []): bool
    {
        $dedupeHash = $this->getDedupeHash($rule, $eventData);

        // Try cache first
        if ($this->cache !== null) {
            $cacheKey = $this->getCacheKey($rule->getName(), $dedupeHash);
            try {
                $cooldownUntil = $this->cache->get($cacheKey, function (): int {
                    return 0;
                });

                if ($cooldownUntil > time()) {
                    $this->logger->debug('Alert in cooldown (cache)', [
                        'rule' => $rule->getName(),
                        'cooldown_until' => date('Y-m-d H:i:s', $cooldownUntil),
                    ]);

                    return true;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Cache check failed, falling back to memory', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Check in-memory cache
        $memKey = $this->getMemoryKey($rule->getName(), $dedupeHash);
        if (isset($this->memoryCache[$memKey]) && $this->memoryCache[$memKey] > time()) {
            $this->logger->debug('Alert in cooldown (memory)', [
                'rule' => $rule->getName(),
            ]);

            return true;
        }

        // Check database if available
        if ($this->alertEventRepository !== null && $dedupeHash !== null) {
            try {
                $isInCooldown = $this->alertEventRepository->isInCooldown($rule->getName(), $dedupeHash);
                if ($isInCooldown) {
                    $this->logger->debug('Alert in cooldown (database)', [
                        'rule' => $rule->getName(),
                    ]);

                    return true;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Database cooldown check failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return false;
    }

    /**
     * Start a cooldown period for an alert.
     *
     * @param AlertRule            $rule      The alert rule
     * @param array<string, mixed> $eventData The event data
     */
    public function startCooldown(AlertRule $rule, array $eventData = []): void
    {
        $dedupeHash = $this->getDedupeHash($rule, $eventData);
        $cooldownUntil = time() + $rule->getCooldownSeconds();

        // Store in cache
        if ($this->cache !== null) {
            $cacheKey = $this->getCacheKey($rule->getName(), $dedupeHash);
            try {
                $this->cache->get($cacheKey, function (ItemInterface $item) use ($rule, $cooldownUntil): int {
                    $item->expiresAfter($rule->getCooldownSeconds());

                    return $cooldownUntil;
                });
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to set cache cooldown', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Store in memory cache
        $memKey = $this->getMemoryKey($rule->getName(), $dedupeHash);
        $this->memoryCache[$memKey] = $cooldownUntil;

        $this->logger->debug('Started cooldown', [
            'rule' => $rule->getName(),
            'cooldown_seconds' => $rule->getCooldownSeconds(),
            'cooldown_until' => date('Y-m-d H:i:s', $cooldownUntil),
        ]);
    }

    /**
     * Clear cooldown for a specific rule.
     *
     * @param AlertRule            $rule      The alert rule
     * @param array<string, mixed> $eventData The event data
     */
    public function clearCooldown(AlertRule $rule, array $eventData = []): void
    {
        $dedupeHash = $this->getDedupeHash($rule, $eventData);

        // Clear from cache
        if ($this->cache !== null) {
            $cacheKey = $this->getCacheKey($rule->getName(), $dedupeHash);
            try {
                $this->cache->delete($cacheKey);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to clear cache cooldown', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clear from memory
        $memKey = $this->getMemoryKey($rule->getName(), $dedupeHash);
        unset($this->memoryCache[$memKey]);

        $this->logger->debug('Cleared cooldown', [
            'rule' => $rule->getName(),
        ]);
    }

    /**
     * Get the remaining cooldown time in seconds.
     *
     * @param AlertRule            $rule      The alert rule
     * @param array<string, mixed> $eventData The event data
     *
     * @return int Remaining seconds, 0 if not in cooldown
     */
    public function getRemainingCooldown(AlertRule $rule, array $eventData = []): int
    {
        $dedupeHash = $this->getDedupeHash($rule, $eventData);
        $now = time();

        // Check cache
        if ($this->cache !== null) {
            $cacheKey = $this->getCacheKey($rule->getName(), $dedupeHash);
            try {
                $cooldownUntil = $this->cache->get($cacheKey, function (): int {
                    return 0;
                });

                if ($cooldownUntil > $now) {
                    return $cooldownUntil - $now;
                }
            } catch (\Throwable) {
                // Fall through to memory check
            }
        }

        // Check memory
        $memKey = $this->getMemoryKey($rule->getName(), $dedupeHash);
        if (isset($this->memoryCache[$memKey]) && $this->memoryCache[$memKey] > $now) {
            return $this->memoryCache[$memKey] - $now;
        }

        return 0;
    }

    /**
     * Generate a deduplication hash for an alert.
     *
     * @param AlertRule            $rule      The alert rule
     * @param array<string, mixed> $eventData The event data
     */
    public function getDedupeHash(AlertRule $rule, array $eventData): ?string
    {
        $dedupeKey = $rule->getDedupeKey();

        if ($dedupeKey === null) {
            return AlertEvent::generateDedupeHash($rule->getName(), null);
        }

        $dedupeValue = $this->extractFieldValue($eventData, $dedupeKey);

        return AlertEvent::generateDedupeHash($rule->getName(), $dedupeValue);
    }

    /**
     * Clear all cooldowns (useful for testing).
     */
    public function clearAll(): void
    {
        $this->memoryCache = [];
    }

    /**
     * Prune expired entries from memory cache.
     */
    public function pruneExpired(): void
    {
        $now = time();
        $this->memoryCache = array_filter(
            $this->memoryCache,
            fn (int $until) => $until > $now
        );
    }

    /**
     * Get cache key for a rule and dedupe hash.
     */
    private function getCacheKey(string $ruleName, ?string $dedupeHash): string
    {
        $hash = $dedupeHash ?? 'global';

        return "alert_cooldown.{$ruleName}.{$hash}";
    }

    /**
     * Get memory cache key.
     */
    private function getMemoryKey(string $ruleName, ?string $dedupeHash): string
    {
        return "{$ruleName}:{$dedupeHash}";
    }

    /**
     * Extract a field value from event data, supporting dot notation.
     *
     * @param array<string, mixed> $data
     */
    private function extractFieldValue(array $data, string $field): ?string
    {
        $keys = explode('.', $field);
        $value = $data;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value !== null ? (string) $value : null;
    }
}
