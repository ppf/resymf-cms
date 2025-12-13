<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AlertRules;

use App\Entity\AlertRule;
use App\Service\AlertRules\SlidingWindowAggregator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for SlidingWindowAggregator service.
 */
class SlidingWindowAggregatorTest extends TestCase
{
    private SlidingWindowAggregator $aggregator;

    protected function setUp(): void
    {
        $this->aggregator = new SlidingWindowAggregator(new NullLogger());
    }

    public function testAddAndGetEvents(): void
    {
        $this->aggregator->addEvent('test_window', ['message' => 'event1']);
        $this->aggregator->addEvent('test_window', ['message' => 'event2']);

        $events = $this->aggregator->getEventsInWindow('test_window', 60);

        $this->assertCount(2, $events);
        $this->assertEquals('event1', $events[0]['message']);
        $this->assertEquals('event2', $events[1]['message']);
    }

    public function testCountInWindow(): void
    {
        $this->aggregator->addEvent('test_window', ['message' => 'event1']);
        $this->aggregator->addEvent('test_window', ['message' => 'event2']);
        $this->aggregator->addEvent('test_window', ['message' => 'event3']);

        $count = $this->aggregator->countInWindow('test_window', 60);

        $this->assertEquals(3, $count);
    }

    public function testCountInWindowExcludesExpiredEvents(): void
    {
        // Add events with old timestamps
        $this->aggregator->addEvent('test_window', ['message' => 'old'], time() - 120);
        $this->aggregator->addEvent('test_window', ['message' => 'recent']);

        $count = $this->aggregator->countInWindow('test_window', 60);

        $this->assertEquals(1, $count);
    }

    public function testAggregateCount(): void
    {
        $events = [
            ['value' => 10],
            ['value' => 20],
            ['value' => 30],
        ];

        $result = $this->aggregator->aggregate($events, AlertRule::AGGREGATION_COUNT, 'value');

        $this->assertEquals(3.0, $result);
    }

    public function testAggregateSum(): void
    {
        $events = [
            ['value' => 10],
            ['value' => 20],
            ['value' => 30],
        ];

        $result = $this->aggregator->aggregate($events, AlertRule::AGGREGATION_SUM, 'value');

        $this->assertEquals(60.0, $result);
    }

    public function testAggregateAvg(): void
    {
        $events = [
            ['value' => 10],
            ['value' => 20],
            ['value' => 30],
        ];

        $result = $this->aggregator->aggregate($events, AlertRule::AGGREGATION_AVG, 'value');

        $this->assertEquals(20.0, $result);
    }

    public function testAggregateMin(): void
    {
        $events = [
            ['value' => 10],
            ['value' => 5],
            ['value' => 30],
        ];

        $result = $this->aggregator->aggregate($events, AlertRule::AGGREGATION_MIN, 'value');

        $this->assertEquals(5.0, $result);
    }

    public function testAggregateMax(): void
    {
        $events = [
            ['value' => 10],
            ['value' => 5],
            ['value' => 30],
        ];

        $result = $this->aggregator->aggregate($events, AlertRule::AGGREGATION_MAX, 'value');

        $this->assertEquals(30.0, $result);
    }

    public function testAggregateEmptyEvents(): void
    {
        $result = $this->aggregator->aggregate([], AlertRule::AGGREGATION_SUM, 'value');

        $this->assertEquals(0.0, $result);
    }

    public function testAggregateWithMissingField(): void
    {
        $events = [
            ['value' => 10],
            ['other' => 20],
            ['value' => 30],
        ];

        $result = $this->aggregator->aggregate($events, AlertRule::AGGREGATION_SUM, 'value');

        $this->assertEquals(40.0, $result);
    }

    public function testAggregateGrouped(): void
    {
        $events = [
            ['ip' => '192.168.1.1', 'count' => 5],
            ['ip' => '192.168.1.1', 'count' => 3],
            ['ip' => '192.168.1.2', 'count' => 10],
        ];

        $result = $this->aggregator->aggregateGrouped(
            $events,
            AlertRule::AGGREGATION_SUM,
            'count',
            'ip'
        );

        $this->assertEquals(8.0, $result['192.168.1.1']);
        $this->assertEquals(10.0, $result['192.168.1.2']);
    }

    public function testAggregateGroupedCount(): void
    {
        $events = [
            ['ip' => '192.168.1.1'],
            ['ip' => '192.168.1.1'],
            ['ip' => '192.168.1.2'],
            ['ip' => '192.168.1.1'],
        ];

        $result = $this->aggregator->aggregateGrouped(
            $events,
            AlertRule::AGGREGATION_COUNT,
            null,
            'ip'
        );

        $this->assertEquals(3.0, $result['192.168.1.1']);
        $this->assertEquals(1.0, $result['192.168.1.2']);
    }

    public function testGroupEvents(): void
    {
        $events = [
            ['ip' => '192.168.1.1', 'message' => 'event1'],
            ['ip' => '192.168.1.1', 'message' => 'event2'],
            ['ip' => '192.168.1.2', 'message' => 'event3'],
        ];

        $groups = $this->aggregator->groupEvents($events, 'ip');

        $this->assertCount(2, $groups);
        $this->assertCount(2, $groups['192.168.1.1']);
        $this->assertCount(1, $groups['192.168.1.2']);
    }

    public function testGroupEventsWithNullValues(): void
    {
        $events = [
            ['ip' => '192.168.1.1'],
            ['ip' => null],
            ['message' => 'no ip'],
        ];

        $groups = $this->aggregator->groupEvents($events, 'ip');

        $this->assertCount(2, $groups);
        $this->assertCount(1, $groups['192.168.1.1']);
        $this->assertCount(2, $groups['__null__']);
    }

    public function testClearWindow(): void
    {
        $this->aggregator->addEvent('test_window', ['message' => 'event1']);
        $this->aggregator->clearWindow('test_window');

        $count = $this->aggregator->countInWindow('test_window', 60);

        $this->assertEquals(0, $count);
    }

    public function testClearAll(): void
    {
        $this->aggregator->addEvent('window1', ['message' => 'event1']);
        $this->aggregator->addEvent('window2', ['message' => 'event2']);
        $this->aggregator->clearAll();

        $this->assertEquals(0, $this->aggregator->countInWindow('window1', 60));
        $this->assertEquals(0, $this->aggregator->countInWindow('window2', 60));
    }

    public function testNestedFieldAccess(): void
    {
        $events = [
            ['context' => ['response_time' => 100]],
            ['context' => ['response_time' => 200]],
            ['context' => ['response_time' => 300]],
        ];

        $result = $this->aggregator->aggregate($events, AlertRule::AGGREGATION_AVG, 'context.response_time');

        $this->assertEquals(200.0, $result);
    }
}
