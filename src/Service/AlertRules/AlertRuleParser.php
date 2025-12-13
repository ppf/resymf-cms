<?php

declare(strict_types=1);

namespace App\Service\AlertRules;

use App\Entity\AlertRule;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Service for parsing alert rules from YAML configuration.
 *
 * Loads alert rule definitions from YAML files and converts them
 * into AlertRule value objects for in-memory evaluation.
 */
class AlertRuleParser
{
    /**
     * Parsed rules cache.
     *
     * @var array<string, AlertRule>
     */
    private array $rulesCache = [];

    private bool $cacheLoaded = false;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $configPath = '',
    ) {
    }

    /**
     * Parse all alert rules from the configuration.
     *
     * @param array<string, mixed> $config The alert_rules configuration array
     *
     * @return array<string, AlertRule> Array of parsed AlertRule objects keyed by name
     */
    public function parseRules(array $config): array
    {
        if ($this->cacheLoaded) {
            return $this->rulesCache;
        }

        $rules = [];
        $rulesConfig = $config['rules'] ?? [];

        foreach ($rulesConfig as $ruleConfig) {
            try {
                $rule = $this->parseRule($ruleConfig);
                $rules[$rule->getName()] = $rule;
            } catch (\InvalidArgumentException $e) {
                $this->logger->warning('Failed to parse alert rule', [
                    'config' => $ruleConfig,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->rulesCache = $rules;
        $this->cacheLoaded = true;

        $this->logger->info('Loaded alert rules from configuration', [
            'count' => count($rules),
            'rule_names' => array_keys($rules),
        ]);

        return $rules;
    }

    /**
     * Parse a single rule configuration into an AlertRule object.
     *
     * @param array<string, mixed> $config Single rule configuration
     */
    public function parseRule(array $config): AlertRule
    {
        $this->validateRuleConfig($config);

        $rule = new AlertRule();
        $rule->setName($config['name']);
        $rule->setDescription($config['description'] ?? null);
        $rule->setIsEnabled($config['enabled'] ?? true);
        $rule->setType($config['type']);
        $rule->setPriority($config['priority'] ?? AlertRule::PRIORITY_MEDIUM);
        $rule->setDedupeKey($config['dedupe_key'] ?? null);
        $rule->setActions($config['actions'] ?? []);

        // Parse cooldown
        $cooldown = $config['cooldown'] ?? '5 minutes';
        $rule->setCooldownSeconds($this->parseTimeToSeconds($cooldown));

        // Parse type-specific configuration
        if ($config['type'] === AlertRule::TYPE_PATTERN) {
            $rule->setPatternConfig($config['pattern'] ?? []);
        } elseif ($config['type'] === AlertRule::TYPE_THRESHOLD) {
            $thresholdConfig = $config['threshold'] ?? [];
            // Convert window string to include in config
            if (isset($thresholdConfig['window'])) {
                $thresholdConfig['window_seconds'] = $this->parseTimeToSeconds($thresholdConfig['window']);
            }
            $rule->setThresholdConfig($thresholdConfig);
        }

        // Parse aggregation if present
        if (isset($config['aggregation'])) {
            $rule->setAggregationConfig($config['aggregation']);
        }

        return $rule;
    }

    /**
     * Load rules from a YAML file.
     *
     * @return array<string, AlertRule>
     */
    public function loadFromFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Alert rules file not found: {$filePath}");
        }

        try {
            $content = Yaml::parseFile($filePath);
        } catch (ParseException $e) {
            throw new \InvalidArgumentException("Failed to parse YAML file: {$e->getMessage()}");
        }

        $config = $content['alert_rules'] ?? $content;

        return $this->parseRules($config);
    }

    /**
     * Clear the rules cache.
     */
    public function clearCache(): void
    {
        $this->rulesCache = [];
        $this->cacheLoaded = false;
    }

    /**
     * Validate rule configuration.
     *
     * @param array<string, mixed> $config
     *
     * @throws \InvalidArgumentException
     */
    private function validateRuleConfig(array $config): void
    {
        if (empty($config['name'])) {
            throw new \InvalidArgumentException('Alert rule must have a name');
        }

        if (empty($config['type'])) {
            throw new \InvalidArgumentException("Alert rule '{$config['name']}' must have a type");
        }

        if (!in_array($config['type'], [AlertRule::TYPE_PATTERN, AlertRule::TYPE_THRESHOLD], true)) {
            throw new \InvalidArgumentException("Invalid alert rule type: {$config['type']}");
        }

        if ($config['type'] === AlertRule::TYPE_PATTERN && empty($config['pattern']['regex'])) {
            throw new \InvalidArgumentException("Pattern rule '{$config['name']}' must have a regex pattern");
        }

        if ($config['type'] === AlertRule::TYPE_THRESHOLD && empty($config['threshold']['count'])) {
            throw new \InvalidArgumentException("Threshold rule '{$config['name']}' must have a count");
        }

        if (isset($config['priority']) && !in_array($config['priority'], [
            AlertRule::PRIORITY_LOW,
            AlertRule::PRIORITY_MEDIUM,
            AlertRule::PRIORITY_HIGH,
            AlertRule::PRIORITY_CRITICAL,
        ], true)) {
            throw new \InvalidArgumentException("Invalid priority: {$config['priority']}");
        }
    }

    /**
     * Parse a time string into seconds.
     *
     * Supports formats like "5 minutes", "1 hour", "30 seconds", "2 hours"
     */
    private function parseTimeToSeconds(string $time): int
    {
        $time = strtolower(trim($time));

        // Handle pure numeric (assume seconds)
        if (is_numeric($time)) {
            return (int) $time;
        }

        // Parse time string
        if (preg_match('/^(\d+)\s*(second|seconds|sec|s|minute|minutes|min|m|hour|hours|hr|h|day|days|d)s?$/i', $time, $matches)) {
            $value = (int) $matches[1];
            $unit = $matches[2];

            return match (true) {
                str_starts_with($unit, 'sec'), $unit === 's' => $value,
                str_starts_with($unit, 'min'), $unit === 'm' => $value * 60,
                str_starts_with($unit, 'hour'), str_starts_with($unit, 'hr'), $unit === 'h' => $value * 3600,
                str_starts_with($unit, 'day'), $unit === 'd' => $value * 86400,
                default => $value,
            };
        }

        // Default to 5 minutes if parsing fails
        $this->logger->warning('Failed to parse time string, using default', [
            'time' => $time,
            'default' => 300,
        ]);

        return 300;
    }
}
