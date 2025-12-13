<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AlertRules;

use App\Entity\AlertRule;
use App\Service\AlertRules\AlertRuleParser;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for AlertRuleParser service.
 */
class AlertRuleParserTest extends TestCase
{
    private AlertRuleParser $parser;

    protected function setUp(): void
    {
        $this->parser = new AlertRuleParser(new NullLogger());
    }

    public function testParseSimplePatternRule(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'error_pattern',
                    'description' => 'Detect errors',
                    'enabled' => true,
                    'type' => 'pattern',
                    'priority' => 'high',
                    'pattern' => [
                        'regex' => 'error.*',
                        'field' => 'message',
                        'case_sensitive' => false,
                    ],
                    'cooldown' => '5 minutes',
                    'actions' => [
                        ['type' => 'log', 'target' => 'alert'],
                    ],
                ],
            ],
        ];

        $rules = $this->parser->parseRules($config);

        $this->assertCount(1, $rules);
        $this->assertArrayHasKey('error_pattern', $rules);

        $rule = $rules['error_pattern'];
        $this->assertEquals('error_pattern', $rule->getName());
        $this->assertEquals('Detect errors', $rule->getDescription());
        $this->assertTrue($rule->isEnabled());
        $this->assertEquals(AlertRule::TYPE_PATTERN, $rule->getType());
        $this->assertEquals(AlertRule::PRIORITY_HIGH, $rule->getPriority());
        $this->assertEquals('error.*', $rule->getRegexPattern());
        $this->assertEquals('message', $rule->getPatternField());
        $this->assertFalse($rule->isCaseSensitive());
        $this->assertEquals(300, $rule->getCooldownSeconds());
    }

    public function testParseThresholdRule(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'high_error_rate',
                    'type' => 'threshold',
                    'priority' => 'critical',
                    'threshold' => [
                        'count' => 100,
                        'window' => '5 minutes',
                        'operator' => 'gte',
                    ],
                    'aggregation' => [
                        'type' => 'count',
                        'field' => 'level',
                    ],
                    'cooldown' => '10 minutes',
                ],
            ],
        ];

        $rules = $this->parser->parseRules($config);

        $this->assertCount(1, $rules);
        $rule = $rules['high_error_rate'];

        $this->assertEquals(AlertRule::TYPE_THRESHOLD, $rule->getType());
        $this->assertEquals(AlertRule::PRIORITY_CRITICAL, $rule->getPriority());
        $this->assertEquals(100, $rule->getThresholdCount());
        $this->assertEquals('5 minutes', $rule->getThresholdWindow());
        $this->assertEquals(AlertRule::OPERATOR_GTE, $rule->getThresholdOperator());
        $this->assertEquals(AlertRule::AGGREGATION_COUNT, $rule->getAggregationType());
        $this->assertEquals('level', $rule->getAggregationField());
        $this->assertEquals(600, $rule->getCooldownSeconds());
    }

    public function testParseMultipleRules(): void
    {
        $config = [
            'rules' => [
                ['name' => 'rule1', 'type' => 'pattern', 'pattern' => ['regex' => 'error']],
                ['name' => 'rule2', 'type' => 'threshold', 'threshold' => ['count' => 10]],
            ],
        ];

        $rules = $this->parser->parseRules($config);

        $this->assertCount(2, $rules);
        $this->assertArrayHasKey('rule1', $rules);
        $this->assertArrayHasKey('rule2', $rules);
    }

    public function testParseRuleWithDedupeKey(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'test_rule',
                    'type' => 'pattern',
                    'pattern' => ['regex' => 'error'],
                    'dedupe_key' => 'ip_address',
                ],
            ],
        ];

        $rules = $this->parser->parseRules($config);
        $rule = $rules['test_rule'];

        $this->assertEquals('ip_address', $rule->getDedupeKey());
    }

    public function testParseTimeToSeconds(): void
    {
        $config = [
            'rules' => [
                ['name' => 'rule_seconds', 'type' => 'pattern', 'pattern' => ['regex' => 'a'], 'cooldown' => '30 seconds'],
                ['name' => 'rule_minutes', 'type' => 'pattern', 'pattern' => ['regex' => 'a'], 'cooldown' => '5 minutes'],
                ['name' => 'rule_hours', 'type' => 'pattern', 'pattern' => ['regex' => 'a'], 'cooldown' => '2 hours'],
                ['name' => 'rule_days', 'type' => 'pattern', 'pattern' => ['regex' => 'a'], 'cooldown' => '1 day'],
            ],
        ];

        $rules = $this->parser->parseRules($config);

        $this->assertEquals(30, $rules['rule_seconds']->getCooldownSeconds());
        $this->assertEquals(300, $rules['rule_minutes']->getCooldownSeconds());
        $this->assertEquals(7200, $rules['rule_hours']->getCooldownSeconds());
        $this->assertEquals(86400, $rules['rule_days']->getCooldownSeconds());
    }

    public function testParseTimeShorthand(): void
    {
        $config = [
            'rules' => [
                ['name' => 'rule_s', 'type' => 'pattern', 'pattern' => ['regex' => 'a'], 'cooldown' => '30s'],
                ['name' => 'rule_m', 'type' => 'pattern', 'pattern' => ['regex' => 'a'], 'cooldown' => '5m'],
                ['name' => 'rule_h', 'type' => 'pattern', 'pattern' => ['regex' => 'a'], 'cooldown' => '2h'],
            ],
        ];

        $rules = $this->parser->parseRules($config);

        $this->assertEquals(30, $rules['rule_s']->getCooldownSeconds());
        $this->assertEquals(300, $rules['rule_m']->getCooldownSeconds());
        $this->assertEquals(7200, $rules['rule_h']->getCooldownSeconds());
    }

    public function testParseDefaultValues(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'minimal_rule',
                    'type' => 'pattern',
                    'pattern' => ['regex' => 'error'],
                ],
            ],
        ];

        $rules = $this->parser->parseRules($config);
        $rule = $rules['minimal_rule'];

        $this->assertTrue($rule->isEnabled());
        $this->assertEquals(AlertRule::PRIORITY_MEDIUM, $rule->getPriority());
        $this->assertEquals(300, $rule->getCooldownSeconds()); // Default 5 minutes
        $this->assertEmpty($rule->getActions());
    }

    public function testParseSkipsInvalidRule(): void
    {
        $config = [
            'rules' => [
                ['name' => 'valid_rule', 'type' => 'pattern', 'pattern' => ['regex' => 'error']],
                ['type' => 'pattern'], // Missing name
                ['name' => 'another_valid', 'type' => 'pattern', 'pattern' => ['regex' => 'warn']],
            ],
        ];

        $rules = $this->parser->parseRules($config);

        $this->assertCount(2, $rules);
        $this->assertArrayHasKey('valid_rule', $rules);
        $this->assertArrayHasKey('another_valid', $rules);
    }

    public function testParseRuleThrowsOnMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must have a name');

        $this->parser->parseRule(['type' => 'pattern']);
    }

    public function testParseRuleThrowsOnMissingType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must have a type');

        $this->parser->parseRule(['name' => 'test']);
    }

    public function testParseRuleThrowsOnInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid alert rule type');

        $this->parser->parseRule(['name' => 'test', 'type' => 'invalid']);
    }

    public function testParseRuleThrowsOnPatternRuleWithoutRegex(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must have a regex pattern');

        $this->parser->parseRule(['name' => 'test', 'type' => 'pattern']);
    }

    public function testParseRuleThrowsOnThresholdRuleWithoutCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must have a count');

        $this->parser->parseRule(['name' => 'test', 'type' => 'threshold']);
    }

    public function testParseRuleThrowsOnInvalidPriority(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid priority');

        $this->parser->parseRule([
            'name' => 'test',
            'type' => 'pattern',
            'pattern' => ['regex' => 'error'],
            'priority' => 'invalid',
        ]);
    }

    public function testClearCache(): void
    {
        $config = [
            'rules' => [
                ['name' => 'rule1', 'type' => 'pattern', 'pattern' => ['regex' => 'error']],
            ],
        ];

        $rules1 = $this->parser->parseRules($config);
        $this->assertCount(1, $rules1);

        // Parse again - should return cached result
        $rules2 = $this->parser->parseRules([]);
        $this->assertCount(1, $rules2);

        // Clear cache and parse empty config
        $this->parser->clearCache();
        $rules3 = $this->parser->parseRules([]);
        $this->assertCount(0, $rules3);
    }

    public function testParseAggregationWithGroupBy(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'grouped_rule',
                    'type' => 'threshold',
                    'threshold' => ['count' => 10],
                    'aggregation' => [
                        'type' => 'count',
                        'field' => 'level',
                        'group_by' => 'ip_address',
                    ],
                ],
            ],
        ];

        $rules = $this->parser->parseRules($config);
        $rule = $rules['grouped_rule'];

        $this->assertEquals('ip_address', $rule->getAggregationGroupBy());
    }

    public function testParseDisabledRule(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'disabled_rule',
                    'type' => 'pattern',
                    'pattern' => ['regex' => 'error'],
                    'enabled' => false,
                ],
            ],
        ];

        $rules = $this->parser->parseRules($config);
        $rule = $rules['disabled_rule'];

        $this->assertFalse($rule->isEnabled());
    }
}
