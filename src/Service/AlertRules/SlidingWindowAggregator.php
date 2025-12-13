<?php

declare(strict_types=1);

namespace App\Service\AlertRules;

use App\Entity\AlertRule;
use Psr\Log\LoggerInterface;

/**
 * Service for sliding window aggregation of events.
 *
 * Provides aggregation functions (count, sum, avg, min, max)
 * over events within configurable time windows.
 */
class SlidingWindowAggregator
{
    /**
     * In-memory event storage for sliding window calculations.
     *
     * @var array<string, array<int, array{timestamp: int, data: array<string, mixed>}>>
     */
    private array $eventWindows = [];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Add an event to the sliding window.
     *
     * @param string               $windowKey The window identifier (usually rule name)
     * @param array<string, mixed> $event     The event data
     * @param int|null             $timestamp Event timestamp (defaults to current time)
     */
    public function addEvent(string $windowKey, array $event, ?int $timestamp = null): void
    {
        $timestamp ??= time();

        if (!isset($this->eventWindows[$windowKey])) {
            $this->eventWindows[$windowKey] = [];
        }

        $this->eventWindows[$windowKey][] = [
            'timestamp' => $timestamp,
            'data' => $event,
        ];
    }

    /**
     * Get events within a time window.
     *
     * @param string $windowKey     The window identifier
     * @param int    $windowSeconds The time window in seconds
     *
     * @return array<int, array<string, mixed>> Events within the window
     */
    public function getEventsInWindow(string $windowKey, int $windowSeconds): array
    {
        $this->pruneExpiredEvents($windowKey, $windowSeconds);

        if (!isset($this->eventWindows[$windowKey])) {
            return [];
        }

        return array_map(
            fn (array $item) => $item['data'],
            $this->eventWindows[$windowKey]
        );
    }

    /**
     * Aggregate events using a specific function.
     *
     * @param array<int, array<string, mixed>> $events          Events to aggregate
     * @param string                           $aggregationType The aggregation type (count, sum, avg, min, max)
     * @param string|null                      $field           The field to aggregate (not needed for count)
     */
    public function aggregate(array $events, string $aggregationType, ?string $field = null): float
    {
        if (empty($events)) {
            return 0.0;
        }

        return match ($aggregationType) {
            AlertRule::AGGREGATION_COUNT => (float) count($events),
            AlertRule::AGGREGATION_SUM => $this->sum($events, $field),
            AlertRule::AGGREGATION_AVG => $this->average($events, $field),
            AlertRule::AGGREGATION_MIN => $this->min($events, $field),
            AlertRule::AGGREGATION_MAX => $this->max($events, $field),
            default => (float) count($events),
        };
    }

    /**
     * Aggregate events grouped by a field.
     *
     * @param array<int, array<string, mixed>> $events          Events to aggregate
     * @param string                           $aggregationType The aggregation type
     * @param string|null                      $aggregateField  The field to aggregate
     * @param string                           $groupByField    The field to group by
     *
     * @return array<string, float> Aggregated values keyed by group
     */
    public function aggregateGrouped(
        array $events,
        string $aggregationType,
        ?string $aggregateField,
        string $groupByField,
    ): array {
        $groups = $this->groupEvents($events, $groupByField);
        $results = [];

        foreach ($groups as $groupKey => $groupEvents) {
            $results[$groupKey] = $this->aggregate($groupEvents, $aggregationType, $aggregateField);
        }

        return $results;
    }

    /**
     * Get count of events in window.
     *
     * @param string $windowKey     The window identifier
     * @param int    $windowSeconds The time window in seconds
     */
    public function countInWindow(string $windowKey, int $windowSeconds): int
    {
        return count($this->getEventsInWindow($windowKey, $windowSeconds));
    }

    /**
     * Group events by a field value.
     *
     * @param array<int, array<string, mixed>> $events The events to group
     * @param string                           $field  The field to group by
     *
     * @return array<string, array<int, array<string, mixed>>> Grouped events
     */
    public function groupEvents(array $events, string $field): array
    {
        $groups = [];

        foreach ($events as $event) {
            $key = $this->getFieldValue($event, $field);
            $groupKey = $key !== null ? (string) $key : '__null__';

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [];
            }

            $groups[$groupKey][] = $event;
        }

        return $groups;
    }

    /**
     * Clear a specific window.
     */
    public function clearWindow(string $windowKey): void
    {
        unset($this->eventWindows[$windowKey]);
    }

    /**
     * Clear all windows.
     */
    public function clearAll(): void
    {
        $this->eventWindows = [];
    }

    /**
     * Calculate the sum of a field across events.
     *
     * @param array<int, array<string, mixed>> $events
     */
    private function sum(array $events, ?string $field): float
    {
        if ($field === null) {
            return (float) count($events);
        }

        $sum = 0.0;
        foreach ($events as $event) {
            $value = $this->getNumericFieldValue($event, $field);
            if ($value !== null) {
                $sum += $value;
            }
        }

        return $sum;
    }

    /**
     * Calculate the average of a field across events.
     *
     * @param array<int, array<string, mixed>> $events
     */
    private function average(array $events, ?string $field): float
    {
        if ($field === null || empty($events)) {
            return 0.0;
        }

        $values = [];
        foreach ($events as $event) {
            $value = $this->getNumericFieldValue($event, $field);
            if ($value !== null) {
                $values[] = $value;
            }
        }

        if (empty($values)) {
            return 0.0;
        }

        return array_sum($values) / count($values);
    }

    /**
     * Find the minimum value of a field across events.
     *
     * @param array<int, array<string, mixed>> $events
     */
    private function min(array $events, ?string $field): float
    {
        if ($field === null || empty($events)) {
            return 0.0;
        }

        $values = [];
        foreach ($events as $event) {
            $value = $this->getNumericFieldValue($event, $field);
            if ($value !== null) {
                $values[] = $value;
            }
        }

        return !empty($values) ? min($values) : 0.0;
    }

    /**
     * Find the maximum value of a field across events.
     *
     * @param array<int, array<string, mixed>> $events
     */
    private function max(array $events, ?string $field): float
    {
        if ($field === null || empty($events)) {
            return 0.0;
        }

        $values = [];
        foreach ($events as $event) {
            $value = $this->getNumericFieldValue($event, $field);
            if ($value !== null) {
                $values[] = $value;
            }
        }

        return !empty($values) ? max($values) : 0.0;
    }

    /**
     * Get a field value from an event, supporting dot notation.
     *
     * @param array<string, mixed> $event
     */
    private function getFieldValue(array $event, string $field): mixed
    {
        $keys = explode('.', $field);
        $value = $event;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Get a numeric field value from an event.
     *
     * @param array<string, mixed> $event
     */
    private function getNumericFieldValue(array $event, string $field): ?float
    {
        $value = $this->getFieldValue($event, $field);

        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * Remove expired events from a window.
     */
    private function pruneExpiredEvents(string $windowKey, int $windowSeconds): void
    {
        if (!isset($this->eventWindows[$windowKey])) {
            return;
        }

        $cutoff = time() - $windowSeconds;

        $this->eventWindows[$windowKey] = array_filter(
            $this->eventWindows[$windowKey],
            fn (array $item) => $item['timestamp'] >= $cutoff
        );

        // Re-index array
        $this->eventWindows[$windowKey] = array_values($this->eventWindows[$windowKey]);
    }
}
