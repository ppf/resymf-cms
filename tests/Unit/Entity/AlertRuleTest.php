<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\AlertRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AlertRule entity.
 */
class AlertRuleTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $rule = new AlertRule();

        $this->assertNull($rule->getId());
        $this->assertTrue($rule->isEnabled());
        $this->assertEquals(AlertRule::TYPE_PATTERN, $rule->getType());
        $this->assertEquals(AlertRule::PRIORITY_MEDIUM, $rule->getPriority());
        $this->assertEquals(300, $rule->getCooldownSeconds());
        $this->assertEmpty($rule->getActions());
        $this->assertInstanceOf(\DateTimeImmutable::class, $rule->getCreatedAt());
        $this->assertNull($rule->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $rule = new AlertRule();
        $result = $rule->setName('test_rule');

        $this->assertSame($rule, $result);
        $this->assertEquals('test_rule', $rule->getName());
    }

    public function testSetDescription(): void
    {
        $rule = new AlertRule();
        $result = $rule->setDescription('Test description');

        $this->assertSame($rule, $result);
        $this->assertEquals('Test description', $rule->getDescription());
    }

    public function testSetType(): void
    {
        $rule = new AlertRule();
        $rule->setType(AlertRule::TYPE_THRESHOLD);

        $this->assertEquals(AlertRule::TYPE_THRESHOLD, $rule->getType());
    }

    public function testSetPriority(): void
    {
        $rule = new AlertRule();
        $rule->setPriority(AlertRule::PRIORITY_CRITICAL);

        $this->assertEquals(AlertRule::PRIORITY_CRITICAL, $rule->getPriority());
    }

    public function testSetPatternConfig(): void
    {
        $rule = new AlertRule();
        $config = ['regex' => 'error.*', 'field' => 'message', 'case_sensitive' => false];
        $result = $rule->setPatternConfig($config);

        $this->assertSame($rule, $result);
        $this->assertEquals($config, $rule->getPatternConfig());
    }

    public function testSetThresholdConfig(): void
    {
        $rule = new AlertRule();
        $config = ['count' => 100, 'window' => '5 minutes', 'operator' => 'gte'];
        $result = $rule->setThresholdConfig($config);

        $this->assertSame($rule, $result);
        $this->assertEquals($config, $rule->getThresholdConfig());
    }

    public function testSetAggregationConfig(): void
    {
        $rule = new AlertRule();
        $config = ['type' => 'count', 'field' => 'level', 'group_by' => 'ip'];
        $result = $rule->setAggregationConfig($config);

        $this->assertSame($rule, $result);
        $this->assertEquals($config, $rule->getAggregationConfig());
    }

    public function testIsPatternRule(): void
    {
        $rule = new AlertRule();
        $rule->setType(AlertRule::TYPE_PATTERN);

        $this->assertTrue($rule->isPatternRule());
        $this->assertFalse($rule->isThresholdRule());
    }

    public function testIsThresholdRule(): void
    {
        $rule = new AlertRule();
        $rule->setType(AlertRule::TYPE_THRESHOLD);

        $this->assertTrue($rule->isThresholdRule());
        $this->assertFalse($rule->isPatternRule());
    }

    public function testGetRegexPattern(): void
    {
        $rule = new AlertRule();
        $rule->setPatternConfig(['regex' => 'error.*', 'field' => 'message']);

        $this->assertEquals('error.*', $rule->getRegexPattern());
    }

    public function testGetRegexPatternReturnsNullWhenNotSet(): void
    {
        $rule = new AlertRule();

        $this->assertNull($rule->getRegexPattern());
    }

    public function testGetPatternField(): void
    {
        $rule = new AlertRule();
        $rule->setPatternConfig(['regex' => 'error.*', 'field' => 'level']);

        $this->assertEquals('level', $rule->getPatternField());
    }

    public function testGetPatternFieldDefaultsToMessage(): void
    {
        $rule = new AlertRule();
        $rule->setPatternConfig(['regex' => 'error.*']);

        $this->assertEquals('message', $rule->getPatternField());
    }

    public function testIsCaseSensitive(): void
    {
        $rule = new AlertRule();
        $rule->setPatternConfig(['regex' => 'error.*', 'case_sensitive' => true]);

        $this->assertTrue($rule->isCaseSensitive());
    }

    public function testIsCaseSensitiveDefaultsFalse(): void
    {
        $rule = new AlertRule();
        $rule->setPatternConfig(['regex' => 'error.*']);

        $this->assertFalse($rule->isCaseSensitive());
    }

    public function testGetThresholdCount(): void
    {
        $rule = new AlertRule();
        $rule->setThresholdConfig(['count' => 50]);

        $this->assertEquals(50, $rule->getThresholdCount());
    }

    public function testGetThresholdCountDefaultsZero(): void
    {
        $rule = new AlertRule();

        $this->assertEquals(0, $rule->getThresholdCount());
    }

    public function testGetThresholdWindow(): void
    {
        $rule = new AlertRule();
        $rule->setThresholdConfig(['window' => '10 minutes']);

        $this->assertEquals('10 minutes', $rule->getThresholdWindow());
    }

    public function testGetThresholdOperator(): void
    {
        $rule = new AlertRule();
        $rule->setThresholdConfig(['operator' => AlertRule::OPERATOR_GT]);

        $this->assertEquals(AlertRule::OPERATOR_GT, $rule->getThresholdOperator());
    }

    public function testGetThresholdOperatorDefaultsGte(): void
    {
        $rule = new AlertRule();

        $this->assertEquals(AlertRule::OPERATOR_GTE, $rule->getThresholdOperator());
    }

    public function testGetAggregationType(): void
    {
        $rule = new AlertRule();
        $rule->setAggregationConfig(['type' => AlertRule::AGGREGATION_AVG]);

        $this->assertEquals(AlertRule::AGGREGATION_AVG, $rule->getAggregationType());
    }

    public function testGetAggregationField(): void
    {
        $rule = new AlertRule();
        $rule->setAggregationConfig(['field' => 'response_time']);

        $this->assertEquals('response_time', $rule->getAggregationField());
    }

    public function testGetAggregationGroupBy(): void
    {
        $rule = new AlertRule();
        $rule->setAggregationConfig(['group_by' => 'ip_address']);

        $this->assertEquals('ip_address', $rule->getAggregationGroupBy());
    }

    public function testSetDedupeKey(): void
    {
        $rule = new AlertRule();
        $result = $rule->setDedupeKey('message');

        $this->assertSame($rule, $result);
        $this->assertEquals('message', $rule->getDedupeKey());
    }

    public function testSetActions(): void
    {
        $rule = new AlertRule();
        $actions = [
            ['type' => 'log', 'target' => 'alert'],
            ['type' => 'email', 'target' => 'admin@example.com'],
        ];
        $result = $rule->setActions($actions);

        $this->assertSame($rule, $result);
        $this->assertEquals($actions, $rule->getActions());
    }

    public function testToString(): void
    {
        $rule = new AlertRule();
        $rule->setName('my_rule');

        $this->assertEquals('my_rule', (string) $rule);
    }

    public function testConstants(): void
    {
        $this->assertEquals('pattern', AlertRule::TYPE_PATTERN);
        $this->assertEquals('threshold', AlertRule::TYPE_THRESHOLD);
        $this->assertEquals('low', AlertRule::PRIORITY_LOW);
        $this->assertEquals('medium', AlertRule::PRIORITY_MEDIUM);
        $this->assertEquals('high', AlertRule::PRIORITY_HIGH);
        $this->assertEquals('critical', AlertRule::PRIORITY_CRITICAL);
        $this->assertEquals('gt', AlertRule::OPERATOR_GT);
        $this->assertEquals('gte', AlertRule::OPERATOR_GTE);
        $this->assertEquals('lt', AlertRule::OPERATOR_LT);
        $this->assertEquals('lte', AlertRule::OPERATOR_LTE);
        $this->assertEquals('eq', AlertRule::OPERATOR_EQ);
    }
}
