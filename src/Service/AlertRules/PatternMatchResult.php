<?php

declare(strict_types=1);

namespace App\Service\AlertRules;

/**
 * Value object representing the result of a pattern match operation.
 */
final class PatternMatchResult
{
    private function __construct(
        private readonly bool $matched,
        private readonly ?string $reason,
        /** @var array<int|string, string> */
        private readonly array $captures = [],
        private readonly mixed $matchedValue = null,
        private readonly bool $error = false,
    ) {
    }

    /**
     * Create a successful match result.
     *
     * @param array<int|string, string> $captures The regex captures
     * @param mixed                     $value    The matched value
     */
    public static function match(array $captures, mixed $value): self
    {
        return new self(
            matched: true,
            reason: null,
            captures: $captures,
            matchedValue: $value,
        );
    }

    /**
     * Create a no-match result.
     */
    public static function noMatch(string $reason): self
    {
        return new self(
            matched: false,
            reason: $reason,
        );
    }

    /**
     * Create an error result.
     */
    public static function error(string $reason): self
    {
        return new self(
            matched: false,
            reason: $reason,
            error: true,
        );
    }

    /**
     * Check if the pattern matched.
     */
    public function isMatch(): bool
    {
        return $this->matched;
    }

    /**
     * Get the reason for no match or error.
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Get the regex captures from the match.
     *
     * @return array<int|string, string>
     */
    public function getCaptures(): array
    {
        return $this->captures;
    }

    /**
     * Get the full matched string (capture group 0).
     */
    public function getFullMatch(): ?string
    {
        return $this->captures[0] ?? null;
    }

    /**
     * Get the original value that was matched.
     */
    public function getMatchedValue(): mixed
    {
        return $this->matchedValue;
    }

    /**
     * Check if there was an error during matching.
     */
    public function hasError(): bool
    {
        return $this->error;
    }

    /**
     * Convert to array for logging/serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'matched' => $this->matched,
            'reason' => $this->reason,
            'captures' => $this->captures,
            'error' => $this->error,
        ];
    }
}
