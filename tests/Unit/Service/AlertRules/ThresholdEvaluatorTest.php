<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AlertRules;

use App\Entity\AlertRule;
use App\Service\AlertRules\SlidingWindowAggregator;
use App\Service\AlertRules\ThresholdEvaluator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for ThresholdEvaluator service.
 */
class ThresholdEvaluatorTest extends TestCase
{
    private ThresholdEvaluator $evaluator;
    private SlidingWindowAggregator $aggregator;

    protected function setUp(): void
    {
        $this->aggregator = new SlidingWindowAggregator(new NullLogger());
        $this->evaluator = new ThresholdEvaluator($this->aggregator, new NullLogger());
    }

    public function testEvaluateThresholdExceeded(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_THRESHOLD);
        $rule->setThresholdConfig(['count' => 3, 'operator' => AlertRule::OPERATOR_GTE]);
        $rule->setAggregationConfig(['type' => AlertRule::AGGREGATION_COUNT]);

        $events = [
            ['message' => 'event1'],
            ['message' => 'event2'],
            ['message' => 'event3'],
        ];

        $result = $this->evaluator->evaluate($rule, $events);

        $this->assertTrue($result->isExceeded());
        $this->assertEquals(3.0, $result->getAggregatedValue());
        $this->assertEquals(3.0, $result->getThreshold());
        $this->assertEquals(3, $result->getEventCount());
    }

    public function testEvaluateThresholdNotExceeded(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_THRESHOLD);
        $rule->setThresholdConfig(['count' => 5, 'operator' => AlertRule::OPERATOR_GTE]);
        $rule->setAggregationConfig(['type' => AlertRule::AGGREGATION_COUNT]);

        $events = [
            ['message' => 'event1'],
            ['message' => 'event2'],
        ];

        $result = $this->evaluator->evaluate($rule, $events);

        $this->assertFalse($result->isExceeded());
        $this->assertEquals(2.0, $result->getAggregatedValue());
    }

    public function testEvaluateWithGreaterThanOperator(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_THRESHOLD);
        $rule->setThresholdConfig(['count' => 3, 'operator' => AlertRule::OPERATOR_GT]);
        $rule->setAggregationConfig(['type' => AlertRule::AGGREGATION_COUNT]);

        $eventsEqual = [
            ['message' => 'event1'],
            ['message' => 'event2'],
            ['message' => 'event3'],
        ];

        $eventsGreater = [
            ['message' => 'event1'],
            ['message' => 'event2'],
            ['message' => 'event3'],
            ['message' => 'event4'],
        ];

        $resultEqual = $this->evaluator->evaluate($rule, $eventsEqual);
        $resultGreater = $this->evaluator->evaluate($rule, $eventsGreater);

        $this->assertFalse($resultEqual->isExceeded());
        $this->assertTrue($resultGreater->isExceeded());
    }

    public function testEvaluateWithLessThanOperator(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_THRESHOLD);
        $rule->setThresholdConfig(['count' => 5, 'operator' => AlertRule::OPERATOR_LT]);
        $rule->setAggregationConfig(['type' => AlertRule::AGGREGATION_COUNT]);

        $events = [
            ['message' => 'event1'],
            ['message' => 'event2'],
        ];

        $result = $this->evaluator->evaluate($rule, $events);

        $this->assertTrue($result->isExceeded());
    }

    public function testEvaluateWithSumAggregation(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_THRESHOLD);
        $rule->setThresholdConfig(['count' => 50, 'operator' => AlertRule::OPERATOR_GTE]);
        $rule->setAggregationConfig(['type' => AlertRule::AGGREGATION_SUM, 'field' => 'value']);

        $events = [
            ['value' => 20],
            ['value' => 15],
            ['value' => 20],
        ];

        $result = $this->evaluator->evaluate($rule, $events);

        $this->assertTrue($result->isExceeded());
        $this->assertEquals(55.0, $result->getAggregatedValue());
    }

    public function testEvaluateWithAvgAggregation(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_THRESHOLD);
        $rule->setThresholdConfig(['count' => 100, 'operator' => AlertRule::OPERATOR_GTE]);
        $rule->setAggregationConfig(['type' => AlertRule::AGGREGATION_AVG, 'field' => 'response_time']);

        $events = [
            ['response_time' => 80],
            ['response_time' => 120],
            ['response_time' => 110],
        ];

        $result = $this->evaluator->evaluate($rule, $events);

        $this->assertTrue($result->isExceeded());
        $this->assertEqualsWithDelta(103.33, $result->getAggregatedValue(), 0.01);
    }

    public function testEvaluateGroupedThreshold(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_THRESHOLD);
        $rule->setThresholdConfig(['count' => 3, 'operator' => AlertRule::OPERATOR_GTE]);
        $rule->setAggregationConfig([
            'type' => AlertRule::AGGREGATION_COUNT,
            'group_by' => 'ip',
        ]);

        $events = [
            ['ip' => '192.168.1.1'],
            ['ip' => '192.168.1.1'],
            ['ip' => '192.168.1.1'],
            ['ip' => '192.168.1.2'],
        ];

        $result = $this->evaluator->evaluate($rule, $events);

        $this->assertTrue($result->isExceeded());
        $this->assertTrue($result->isGrouped());
        $this->assertEquals('192.168.1.1', $result->getGroupKey());
        $this->assertArrayHasKey('192.168.1.1', $result->getGroupedResults());
    }

    public function testEvaluateGroupedThresholdNotExceeded(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_THRESHOLD);
        $rule->setThresholdConfig(['count' => 5, 'operator' => AlertRule::OPERATOR_GTE]);
        $rule->setAggregationConfig([
            'type' => AlertRule::AGGREGATION_COUNT,
            'group_by' => 'ip',
        ]);

        $events = [
            ['ip' => '192.168.1.1'],
            ['ip' => '192.168.1.1'],
            ['ip' => '192.168.1.2'],
        ];

        $result = $this->evaluator->evaluate($rule, $events);

        $this->assertFalse($result->isExceeded());
    }

    public function testEvaluateNotApplicableForPatternRule(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_PATTERN);

        $events = [['message' => 'event1']];

        $result = $this->evaluator->evaluate($rule, $events);

        $this->assertFalse($result->isExceeded());
        $this->assertStringContainsString('not a threshold rule', $result->getReason());
    }

    public function testFilterByWindow(): void
    {
        $now = time();
        $events = [
            ['message' => 'old', 'timestamp' => $now - 120],
            ['message' => 'recent1', 'timestamp' => $now - 30],
            ['message' => 'recent2', 'timestamp' => $now - 10],
        ];

        $filtered = $this->evaluator->filterByWindow($events, 60);

        $this->assertCount(2, $filtered);
    }

    public function testFilterByWindowWithDateTimeTimestamp(): void
    {
        $now = new \DateTimeImmutable();
        $events = [
            ['message' => 'old', 'timestamp' => $now->modify('-2 minutes')],
            ['message' => 'recent', 'timestamp' => $now->modify('-30 seconds')],
        ];

        $filtered = $this->evaluator->filterByWindow($events, 60);

        $this->assertCount(1, $filtered);
    }

    public function testFilterByWindowWithStringTimestamp(): void
    {
        $events = [
            ['message' => 'old', 'timestamp' => date('Y-m-d H:i:s', time() - 120)],
            ['message' => 'recent', 'timestamp' => date('Y-m-d H:i:s', time() - 30)],
        ];

        $filtered = $this->evaluator->filterByWindow($events, 60);

        $this->assertCount(1, $filtered);
    }

    public function testPercentageOfThreshold(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_THRESHOLD);
        $rule->setThresholdConfig(['count' => 100, 'operator' => AlertRule::OPERATOR_GTE]);
        $rule->setAggregationConfig(['type' => AlertRule::AGGREGATION_COUNT]);

        $events = array_fill(0, 150, ['message' => 'event']);

        $result = $this->evaluator->evaluate($rule, $events);

        $this->assertEquals(150.0, $result->getPercentageOfThreshold());
    }
}
