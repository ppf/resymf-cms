<?php

declare(strict_types=1);

namespace App\Service\AlertRules;

use App\Entity\AlertRule;
use Psr\Log\LoggerInterface;

/**
 * Service for pattern-based alert detection using regular expressions.
 *
 * Evaluates log events against pattern rules and determines
 * if they match the defined regex patterns.
 */
class PatternMatcher
{
    /**
     * Compiled regex cache to avoid recompilation.
     *
     * @var array<string, string>
     */
    private array $compiledPatterns = [];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Check if an event matches a pattern rule.
     *
     * @param AlertRule            $rule  The pattern rule to evaluate
     * @param array<string, mixed> $event The event data to match against
     *
     * @return PatternMatchResult The result of the pattern match
     */
    public function matches(AlertRule $rule, array $event): PatternMatchResult
    {
        if (!$rule->isPatternRule()) {
            return PatternMatchResult::noMatch('Rule is not a pattern rule');
        }

        $pattern = $rule->getRegexPattern();
        if ($pattern === null) {
            return PatternMatchResult::noMatch('No regex pattern defined');
        }

        $field = $rule->getPatternField();
        $value = $this->getFieldValue($event, $field);

        if ($value === null) {
            return PatternMatchResult::noMatch("Field '{$field}' not found in event");
        }

        $compiledPattern = $this->compilePattern($pattern, $rule->isCaseSensitive());

        try {
            $result = @preg_match($compiledPattern, (string) $value, $matches);

            if ($result === false) {
                $error = preg_last_error_msg();
                $this->logger->error('Pattern matching failed', [
                    'rule' => $rule->getName(),
                    'pattern' => $pattern,
                    'error' => $error,
                ]);

                return PatternMatchResult::error("Regex error: {$error}");
            }

            if ($result === 1) {
                return PatternMatchResult::match($matches, $value);
            }

            return PatternMatchResult::noMatch('Pattern did not match');
        } catch (\Throwable $e) {
            $this->logger->error('Pattern matching exception', [
                'rule' => $rule->getName(),
                'pattern' => $pattern,
                'exception' => $e->getMessage(),
            ]);

            return PatternMatchResult::error($e->getMessage());
        }
    }

    /**
     * Evaluate multiple events against a pattern rule.
     *
     * @param AlertRule                   $rule   The pattern rule to evaluate
     * @param array<int, array<string, mixed>> $events The events to match against
     *
     * @return array<int, PatternMatchResult> Results indexed by event index
     */
    public function matchAll(AlertRule $rule, array $events): array
    {
        $results = [];

        foreach ($events as $index => $event) {
            $results[$index] = $this->matches($rule, $event);
        }

        return $results;
    }

    /**
     * Find all events that match a pattern rule.
     *
     * @param AlertRule                   $rule   The pattern rule to evaluate
     * @param array<int, array<string, mixed>> $events The events to match against
     *
     * @return array<int, array<string, mixed>> Matching events
     */
    public function findMatching(AlertRule $rule, array $events): array
    {
        $matching = [];

        foreach ($events as $event) {
            $result = $this->matches($rule, $event);
            if ($result->isMatch()) {
                $matching[] = $event;
            }
        }

        return $matching;
    }

    /**
     * Validate a regex pattern.
     *
     * @return bool True if the pattern is valid
     */
    public function isValidPattern(string $pattern): bool
    {
        $compiled = $this->compilePattern($pattern, false);

        // Use error suppression and check result
        $result = @preg_match($compiled, '');

        return $result !== false;
    }

    /**
     * Compile a pattern into a valid PCRE regex.
     *
     * @param string $pattern       The regex pattern
     * @param bool   $caseSensitive Whether matching should be case-sensitive
     */
    private function compilePattern(string $pattern, bool $caseSensitive): string
    {
        $cacheKey = $pattern . ($caseSensitive ? ':cs' : ':ci');

        if (isset($this->compiledPatterns[$cacheKey])) {
            return $this->compiledPatterns[$cacheKey];
        }

        // Check if pattern already has delimiters
        if (preg_match('#^[/#~@%].*[/#~@%][imsxADSUXJu]*$#', $pattern)) {
            // Pattern already has delimiters - add case flag if needed
            if (!$caseSensitive && !str_contains(substr($pattern, strrpos($pattern, '/') ?: 0), 'i')) {
                $compiled = $pattern . 'i';
            } else {
                $compiled = $pattern;
            }
        } else {
            // Add delimiters and flags
            $delimiter = '#';
            $flags = $caseSensitive ? '' : 'i';
            $compiled = $delimiter . $pattern . $delimiter . $flags;
        }

        $this->compiledPatterns[$cacheKey] = $compiled;

        return $compiled;
    }

    /**
     * Get a field value from an event, supporting nested fields with dot notation.
     *
     * @param array<string, mixed> $event The event data
     * @param string               $field The field name (supports dot notation)
     */
    private function getFieldValue(array $event, string $field): mixed
    {
        // Handle dot notation for nested fields
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
     * Clear the compiled pattern cache.
     */
    public function clearCache(): void
    {
        $this->compiledPatterns = [];
    }
}
