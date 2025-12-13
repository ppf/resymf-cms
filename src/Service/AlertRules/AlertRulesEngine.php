<?php

declare(strict_types=1);

namespace App\Service\AlertRules;

use App\Entity\AlertEvent;
use App\Entity\AlertRule;
use App\Repository\AlertEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Main Alert Rules Engine service.
 *
 * Loads alert rules from YAML configuration and evaluates them
 * in-memory against incoming events. Supports pattern-based and
 * threshold-based alerting with cooldown and deduplication.
 */
class AlertRulesEngine
{
    /**
     * Loaded alert rules.
     *
     * @var array<string, AlertRule>
     */
    private array $rules = [];

    private bool $initialized = false;

    public function __construct(
        private readonly AlertRuleParser $parser,
        private readonly PatternMatcher $patternMatcher,
        private readonly ThresholdEvaluator $thresholdEvaluator,
        private readonly SlidingWindowAggregator $aggregator,
        private readonly AlertCooldownManager $cooldownManager,
        private readonly ?EntityManagerInterface $entityManager,
        private readonly ?AlertEventRepository $alertEventRepository,
        private readonly LoggerInterface $logger,
        /** @var array<string, mixed> */
        private readonly array $config = [],
    ) {
    }

    /**
     * Initialize the engine by loading rules from configuration.
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        if (!($this->config['enabled'] ?? true)) {
            $this->logger->info('Alert rules engine is disabled');
            $this->initialized = true;

            return;
        }

        // Load rules from configuration array or from file
        $configRules = $this->config['rules'] ?? [];
        if (!empty($configRules)) {
            $this->rules = $this->parser->parseRules($this->config);
        } else {
            // Load from YAML file if no inline rules
            $configPath = $this->config['config_path'] ?? null;
            if ($configPath !== null && file_exists($configPath)) {
                $this->rules = $this->parser->loadFromFile($configPath);
            }
        }

        $this->initialized = true;

        $this->logger->info('Alert rules engine initialized', [
            'rules_count' => count($this->rules),
            'rule_names' => array_keys($this->rules),
        ]);
    }

    /**
     * Evaluate an event against all loaded rules.
     *
     * @param array<string, mixed> $event The event to evaluate
     *
     * @return array<string, AlertEvent> Triggered alerts keyed by rule name
     */
    public function evaluate(array $event): array
    {
        $this->initialize();

        $triggeredAlerts = [];

        foreach ($this->rules as $rule) {
            if (!$rule->isEnabled()) {
                continue;
            }

            $alert = $this->evaluateRule($rule, $event);
            if ($alert !== null) {
                $triggeredAlerts[$rule->getName()] = $alert;
            }
        }

        return $triggeredAlerts;
    }

    /**
     * Evaluate a single rule against an event.
     *
     * @param AlertRule            $rule  The rule to evaluate
     * @param array<string, mixed> $event The event data
     *
     * @return AlertEvent|null The triggered alert, or null if not triggered
     */
    public function evaluateRule(AlertRule $rule, array $event): ?AlertEvent
    {
        // Check cooldown first
        if ($this->cooldownManager->isInCooldown($rule, $event)) {
            $this->logger->debug('Rule in cooldown, skipping', [
                'rule' => $rule->getName(),
            ]);

            return null;
        }

        $triggered = false;
        $alertEvent = new AlertEvent();
        $alertEvent->setRuleName($rule->getName());
        $alertEvent->setPriority($rule->getPriority());
        $alertEvent->setContext($event);

        if ($rule->isPatternRule()) {
            $triggered = $this->evaluatePatternRule($rule, $event, $alertEvent);
        } elseif ($rule->isThresholdRule()) {
            $triggered = $this->evaluateThresholdRule($rule, $event, $alertEvent);
        }

        if (!$triggered) {
            return null;
        }

        // Set cooldown
        $cooldownUntil = (new \DateTimeImmutable())->modify("+{$rule->getCooldownSeconds()} seconds");
        $alertEvent->setCooldownUntil($cooldownUntil);

        // Set dedupe hash
        $dedupeHash = $this->cooldownManager->getDedupeHash($rule, $event);
        $alertEvent->setDedupeHash($dedupeHash);

        // Start cooldown
        $this->cooldownManager->startCooldown($rule, $event);

        // Persist if entity manager is available
        if ($this->entityManager !== null) {
            try {
                $this->entityManager->persist($alertEvent);
                $this->entityManager->flush();
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to persist alert event', [
                    'rule' => $rule->getName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Execute actions
        $this->executeActions($rule, $alertEvent);

        $this->logger->info('Alert triggered', [
            'rule' => $rule->getName(),
            'priority' => $rule->getPriority(),
            'type' => $rule->getType(),
        ]);

        return $alertEvent;
    }

    /**
     * Add an event to the sliding window for threshold evaluation.
     *
     * @param array<string, mixed> $event The event data
     */
    public function addEvent(array $event): void
    {
        $this->initialize();

        foreach ($this->rules as $rule) {
            if ($rule->isThresholdRule() && $rule->isEnabled()) {
                $this->aggregator->addEvent($rule->getName(), $event);
            }
        }
    }

    /**
     * Evaluate and add an event in a single operation.
     *
     * @param array<string, mixed> $event The event data
     *
     * @return array<string, AlertEvent> Triggered alerts
     */
    public function processEvent(array $event): array
    {
        $this->addEvent($event);

        return $this->evaluate($event);
    }

    /**
     * Get all loaded rules.
     *
     * @return array<string, AlertRule>
     */
    public function getRules(): array
    {
        $this->initialize();

        return $this->rules;
    }

    /**
     * Get a rule by name.
     */
    public function getRule(string $name): ?AlertRule
    {
        $this->initialize();

        return $this->rules[$name] ?? null;
    }

    /**
     * Check if the engine is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? true;
    }

    /**
     * Reload rules from configuration.
     */
    public function reload(): void
    {
        $this->initialized = false;
        $this->parser->clearCache();
        $this->initialize();
    }

    /**
     * Evaluate a pattern-based rule.
     *
     * @param AlertRule            $rule       The rule to evaluate
     * @param array<string, mixed> $event      The event data
     * @param AlertEvent           $alertEvent The alert event to populate
     */
    private function evaluatePatternRule(AlertRule $rule, array $event, AlertEvent $alertEvent): bool
    {
        $result = $this->patternMatcher->matches($rule, $event);

        if (!$result->isMatch()) {
            return false;
        }

        $alertEvent->setTriggerMessage($result->getFullMatch());
        $alertEvent->setEventCount(1);

        return true;
    }

    /**
     * Evaluate a threshold-based rule.
     *
     * @param AlertRule            $rule       The rule to evaluate
     * @param array<string, mixed> $event      The event data
     * @param AlertEvent           $alertEvent The alert event to populate
     */
    private function evaluateThresholdRule(AlertRule $rule, array $event, AlertEvent $alertEvent): bool
    {
        $windowSeconds = $rule->getThresholdConfig()['window_seconds'] ?? 300;
        $events = $this->aggregator->getEventsInWindow($rule->getName(), $windowSeconds);

        $result = $this->thresholdEvaluator->evaluate($rule, $events);

        if (!$result->isExceeded()) {
            return false;
        }

        $alertEvent->setAggregatedValue($result->getAggregatedValue());
        $alertEvent->setThresholdValue($result->getThreshold());
        $alertEvent->setEventCount($result->getEventCount());

        if ($result->getGroupKey() !== null) {
            $context = $alertEvent->getContext();
            $context['exceeded_group'] = $result->getGroupKey();
            $context['grouped_results'] = $result->getGroupedResults();
            $alertEvent->setContext($context);
        }

        return true;
    }

    /**
     * Execute actions for a triggered alert.
     *
     * @param AlertRule  $rule       The rule that triggered
     * @param AlertEvent $alertEvent The triggered alert event
     */
    private function executeActions(AlertRule $rule, AlertEvent $alertEvent): void
    {
        $actions = $rule->getActions();

        foreach ($actions as $action) {
            $type = $action['type'] ?? 'log';

            try {
                match ($type) {
                    'log' => $this->executeLogAction($rule, $alertEvent, $action),
                    default => $this->logger->warning('Unknown action type', [
                        'type' => $type,
                        'rule' => $rule->getName(),
                    ]),
                };
            } catch (\Throwable $e) {
                $this->logger->error('Action execution failed', [
                    'action' => $type,
                    'rule' => $rule->getName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Execute a log action.
     *
     * @param AlertRule             $rule       The rule
     * @param AlertEvent            $alertEvent The alert event
     * @param array<string, string> $action     The action configuration
     */
    private function executeLogAction(AlertRule $rule, AlertEvent $alertEvent, array $action): void
    {
        $channel = $action['target'] ?? 'alert';

        $this->logger->warning("[Alert:{$channel}] {$rule->getName()} triggered", [
            'rule' => $rule->getName(),
            'priority' => $alertEvent->getPriority(),
            'message' => $alertEvent->getTriggerMessage(),
            'aggregated_value' => $alertEvent->getAggregatedValue(),
            'threshold' => $alertEvent->getThresholdValue(),
            'event_count' => $alertEvent->getEventCount(),
        ]);
    }
}
