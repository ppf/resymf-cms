<?php

declare(strict_types=1);

namespace App\Service\AlertRules;

use App\Entity\AlertRule;
use Psr\Log\LoggerInterface;

/**
 * Service for threshold-based alert detection.
 *
 * Evaluates event counts within time windows against configured thresholds.
 */
class ThresholdEvaluator
{
    public function __construct(
        private readonly SlidingWindowAggregator $aggregator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Evaluate if events exceed the threshold defined in a rule.
     *
     * @param AlertRule                   $rule   The threshold rule to evaluate
     * @param array<int, array<string, mixed>> $events The events within the time window
     *
     * @return ThresholdEvalResult The result of the threshold evaluation
     */
    public function evaluate(AlertRule $rule, array $events): ThresholdEvalResult
    {
        if (!$rule->isThresholdRule()) {
            return ThresholdEvalResult::notApplicable('Rule is not a threshold rule');
        }

        $threshold = $rule->getThresholdCount();
        $operator = $rule->getThresholdOperator();

        // Get aggregated value
        $aggregationType = $rule->getAggregationType() ?? AlertRule::AGGREGATION_COUNT;
        $aggregationField = $rule->getAggregationField();
        $groupBy = $rule->getAggregationGroupBy();

        // If grouping, evaluate each group
        if ($groupBy !== null) {
            return $this->evaluateGrouped($rule, $events, $threshold, $operator, $aggregationType, $aggregationField, $groupBy);
        }

        // Single aggregation
        $aggregatedValue = $this->aggregator->aggregate($events, $aggregationType, $aggregationField);

        $exceeded = $this->compare($aggregatedValue, $threshold, $operator);

        if ($exceeded) {
            $this->logger->info('Threshold exceeded', [
                'rule' => $rule->getName(),
                'value' => $aggregatedValue,
                'threshold' => $threshold,
                'operator' => $operator,
                'event_count' => count($events),
            ]);

            return ThresholdEvalResult::exceeded(
                aggregatedValue: $aggregatedValue,
                threshold: (float) $threshold,
                eventCount: count($events),
            );
        }

        return ThresholdEvalResult::notExceeded(
            aggregatedValue: $aggregatedValue,
            threshold: (float) $threshold,
            eventCount: count($events),
        );
    }

    /**
     * Evaluate threshold with grouping.
     *
     * @param AlertRule                   $rule
     * @param array<int, array<string, mixed>> $events
     * @param int                         $threshold
     * @param string                      $operator
     * @param string                      $aggregationType
     * @param string|null                 $aggregationField
     * @param string                      $groupBy
     */
    private function evaluateGrouped(
        AlertRule $rule,
        array $events,
        int $threshold,
        string $operator,
        string $aggregationType,
        ?string $aggregationField,
        string $groupBy,
    ): ThresholdEvalResult {
        $grouped = $this->aggregator->aggregateGrouped($events, $aggregationType, $aggregationField, $groupBy);

        $exceededGroups = [];
        $maxValue = 0.0;
        $maxGroup = null;

        foreach ($grouped as $group => $value) {
            if ($value > $maxValue) {
                $maxValue = $value;
                $maxGroup = $group;
            }

            if ($this->compare($value, $threshold, $operator)) {
                $exceededGroups[$group] = $value;
            }
        }

        if (!empty($exceededGroups)) {
            $this->logger->info('Grouped threshold exceeded', [
                'rule' => $rule->getName(),
                'exceeded_groups' => $exceededGroups,
                'threshold' => $threshold,
                'operator' => $operator,
            ]);

            return ThresholdEvalResult::exceeded(
                aggregatedValue: $maxValue,
                threshold: (float) $threshold,
                eventCount: count($events),
                groupKey: $maxGroup,
                groupedResults: $exceededGroups,
            );
        }

        return ThresholdEvalResult::notExceeded(
            aggregatedValue: $maxValue,
            threshold: (float) $threshold,
            eventCount: count($events),
        );
    }

    /**
     * Compare a value against a threshold using the specified operator.
     *
     * @param float  $value     The value to compare
     * @param int    $threshold The threshold to compare against
     * @param string $operator  The comparison operator
     */
    private function compare(float $value, int $threshold, string $operator): bool
    {
        return match ($operator) {
            AlertRule::OPERATOR_GT => $value > $threshold,
            AlertRule::OPERATOR_GTE => $value >= $threshold,
            AlertRule::OPERATOR_LT => $value < $threshold,
            AlertRule::OPERATOR_LTE => $value <= $threshold,
            AlertRule::OPERATOR_EQ => abs($value - $threshold) < PHP_FLOAT_EPSILON,
            default => $value >= $threshold,
        };
    }

    /**
     * Get events within a time window.
     *
     * @param array<int, array<string, mixed>> $events          All events
     * @param int                              $windowSeconds   The time window in seconds
     * @param string                           $timestampField  The field containing the timestamp
     *
     * @return array<int, array<string, mixed>> Events within the window
     */
    public function filterByWindow(array $events, int $windowSeconds, string $timestampField = 'timestamp'): array
    {
        $cutoff = time() - $windowSeconds;

        return array_filter($events, function (array $event) use ($timestampField, $cutoff): bool {
            $timestamp = $this->extractTimestamp($event, $timestampField);

            return $timestamp !== null && $timestamp >= $cutoff;
        });
    }

    /**
     * Extract a timestamp from an event.
     *
     * @param array<string, mixed> $event
     * @param string               $field
     */
    private function extractTimestamp(array $event, string $field): ?int
    {
        $value = $event[$field] ?? null;

        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_string($value)) {
            $timestamp = strtotime($value);

            return $timestamp !== false ? $timestamp : null;
        }

        return null;
    }
}
