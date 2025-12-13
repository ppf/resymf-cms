<?php

declare(strict_types=1);

namespace App\Service\AlertRules;

/**
 * Value object representing the result of a threshold evaluation.
 */
final class ThresholdEvalResult
{
    private function __construct(
        private readonly bool $exceeded,
        private readonly float $aggregatedValue,
        private readonly float $threshold,
        private readonly int $eventCount,
        private readonly ?string $reason = null,
        private readonly ?string $groupKey = null,
        /** @var array<string, float> */
        private readonly array $groupedResults = [],
    ) {
    }

    /**
     * Create a result where threshold was exceeded.
     *
     * @param array<string, float> $groupedResults
     */
    public static function exceeded(
        float $aggregatedValue,
        float $threshold,
        int $eventCount,
        ?string $groupKey = null,
        array $groupedResults = [],
    ): self {
        return new self(
            exceeded: true,
            aggregatedValue: $aggregatedValue,
            threshold: $threshold,
            eventCount: $eventCount,
            groupKey: $groupKey,
            groupedResults: $groupedResults,
        );
    }

    /**
     * Create a result where threshold was not exceeded.
     */
    public static function notExceeded(
        float $aggregatedValue,
        float $threshold,
        int $eventCount,
    ): self {
        return new self(
            exceeded: false,
            aggregatedValue: $aggregatedValue,
            threshold: $threshold,
            eventCount: $eventCount,
        );
    }

    /**
     * Create a not-applicable result.
     */
    public static function notApplicable(string $reason): self
    {
        return new self(
            exceeded: false,
            aggregatedValue: 0.0,
            threshold: 0.0,
            eventCount: 0,
            reason: $reason,
        );
    }

    /**
     * Check if the threshold was exceeded.
     */
    public function isExceeded(): bool
    {
        return $this->exceeded;
    }

    /**
     * Get the aggregated value.
     */
    public function getAggregatedValue(): float
    {
        return $this->aggregatedValue;
    }

    /**
     * Get the threshold value.
     */
    public function getThreshold(): float
    {
        return $this->threshold;
    }

    /**
     * Get the number of events evaluated.
     */
    public function getEventCount(): int
    {
        return $this->eventCount;
    }

    /**
     * Get the reason (if not applicable).
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Get the group key that exceeded threshold (if grouped).
     */
    public function getGroupKey(): ?string
    {
        return $this->groupKey;
    }

    /**
     * Get all grouped results that exceeded threshold.
     *
     * @return array<string, float>
     */
    public function getGroupedResults(): array
    {
        return $this->groupedResults;
    }

    /**
     * Check if this was a grouped evaluation.
     */
    public function isGrouped(): bool
    {
        return !empty($this->groupedResults);
    }

    /**
     * Calculate the percentage over/under threshold.
     */
    public function getPercentageOfThreshold(): float
    {
        if ($this->threshold == 0) {
            return 0.0;
        }

        return ($this->aggregatedValue / $this->threshold) * 100;
    }

    /**
     * Convert to array for logging/serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'exceeded' => $this->exceeded,
            'aggregated_value' => $this->aggregatedValue,
            'threshold' => $this->threshold,
            'event_count' => $this->eventCount,
            'percentage' => $this->getPercentageOfThreshold(),
            'reason' => $this->reason,
            'group_key' => $this->groupKey,
            'grouped_results' => $this->groupedResults,
        ];
    }
}
