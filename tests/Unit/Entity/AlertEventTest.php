<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\AlertEvent;
use App\Entity\AlertRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AlertEvent entity.
 */
class AlertEventTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $event = new AlertEvent();

        $this->assertNull($event->getId());
        $this->assertEquals(AlertRule::PRIORITY_MEDIUM, $event->getPriority());
        $this->assertEquals(AlertEvent::STATUS_TRIGGERED, $event->getStatus());
        $this->assertNull($event->getDedupeHash());
        $this->assertNull($event->getTriggerMessage());
        $this->assertEmpty($event->getContext());
        $this->assertEquals(1, $event->getEventCount());
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getTriggeredAt());
        $this->assertNull($event->getCooldownUntil());
        $this->assertNull($event->getAcknowledgedAt());
        $this->assertNull($event->getResolvedAt());
    }

    public function testSetRuleName(): void
    {
        $event = new AlertEvent();
        $result = $event->setRuleName('test_rule');

        $this->assertSame($event, $result);
        $this->assertEquals('test_rule', $event->getRuleName());
    }

    public function testSetPriority(): void
    {
        $event = new AlertEvent();
        $event->setPriority(AlertRule::PRIORITY_CRITICAL);

        $this->assertEquals(AlertRule::PRIORITY_CRITICAL, $event->getPriority());
    }

    public function testSetStatus(): void
    {
        $event = new AlertEvent();
        $event->setStatus(AlertEvent::STATUS_ACKNOWLEDGED);

        $this->assertEquals(AlertEvent::STATUS_ACKNOWLEDGED, $event->getStatus());
    }

    public function testSetDedupeHash(): void
    {
        $event = new AlertEvent();
        $result = $event->setDedupeHash('abc123');

        $this->assertSame($event, $result);
        $this->assertEquals('abc123', $event->getDedupeHash());
    }

    public function testSetTriggerMessage(): void
    {
        $event = new AlertEvent();
        $result = $event->setTriggerMessage('Error detected');

        $this->assertSame($event, $result);
        $this->assertEquals('Error detected', $event->getTriggerMessage());
    }

    public function testSetContext(): void
    {
        $event = new AlertEvent();
        $context = ['ip' => '192.168.1.1', 'user' => 'admin'];
        $result = $event->setContext($context);

        $this->assertSame($event, $result);
        $this->assertEquals($context, $event->getContext());
    }

    public function testSetAggregatedValue(): void
    {
        $event = new AlertEvent();
        $result = $event->setAggregatedValue(150.5);

        $this->assertSame($event, $result);
        $this->assertEquals(150.5, $event->getAggregatedValue());
    }

    public function testSetThresholdValue(): void
    {
        $event = new AlertEvent();
        $result = $event->setThresholdValue(100.0);

        $this->assertSame($event, $result);
        $this->assertEquals(100.0, $event->getThresholdValue());
    }

    public function testSetEventCount(): void
    {
        $event = new AlertEvent();
        $result = $event->setEventCount(50);

        $this->assertSame($event, $result);
        $this->assertEquals(50, $event->getEventCount());
    }

    public function testSetCooldownUntil(): void
    {
        $event = new AlertEvent();
        $cooldown = new \DateTimeImmutable('+5 minutes');
        $result = $event->setCooldownUntil($cooldown);

        $this->assertSame($event, $result);
        $this->assertEquals($cooldown, $event->getCooldownUntil());
    }

    public function testIsInCooldownWhenNotSet(): void
    {
        $event = new AlertEvent();

        $this->assertFalse($event->isInCooldown());
    }

    public function testIsInCooldownWhenActive(): void
    {
        $event = new AlertEvent();
        $event->setCooldownUntil(new \DateTimeImmutable('+5 minutes'));

        $this->assertTrue($event->isInCooldown());
    }

    public function testIsInCooldownWhenExpired(): void
    {
        $event = new AlertEvent();
        $event->setCooldownUntil(new \DateTimeImmutable('-5 minutes'));

        $this->assertFalse($event->isInCooldown());
    }

    public function testAcknowledge(): void
    {
        $event = new AlertEvent();
        $result = $event->acknowledge('admin@example.com');

        $this->assertSame($event, $result);
        $this->assertEquals(AlertEvent::STATUS_ACKNOWLEDGED, $event->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getAcknowledgedAt());
        $this->assertEquals('admin@example.com', $event->getAcknowledgedBy());
    }

    public function testAcknowledgeWithoutUser(): void
    {
        $event = new AlertEvent();
        $event->acknowledge();

        $this->assertEquals(AlertEvent::STATUS_ACKNOWLEDGED, $event->getStatus());
        $this->assertNull($event->getAcknowledgedBy());
    }

    public function testResolve(): void
    {
        $event = new AlertEvent();
        $result = $event->resolve();

        $this->assertSame($event, $result);
        $this->assertEquals(AlertEvent::STATUS_RESOLVED, $event->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getResolvedAt());
    }

    public function testSuppress(): void
    {
        $event = new AlertEvent();
        $result = $event->suppress();

        $this->assertSame($event, $result);
        $this->assertEquals(AlertEvent::STATUS_SUPPRESSED, $event->getStatus());
    }

    public function testGenerateDedupeHash(): void
    {
        $hash1 = AlertEvent::generateDedupeHash('rule1', 'value1');
        $hash2 = AlertEvent::generateDedupeHash('rule1', 'value1');
        $hash3 = AlertEvent::generateDedupeHash('rule1', 'value2');
        $hash4 = AlertEvent::generateDedupeHash('rule2', 'value1');

        $this->assertEquals($hash1, $hash2);
        $this->assertNotEquals($hash1, $hash3);
        $this->assertNotEquals($hash1, $hash4);
        $this->assertEquals(64, strlen($hash1)); // SHA-256 hex length
    }

    public function testGenerateDedupeHashWithNull(): void
    {
        $hash1 = AlertEvent::generateDedupeHash('rule1', null);
        $hash2 = AlertEvent::generateDedupeHash('rule1', null);

        $this->assertEquals($hash1, $hash2);
    }

    public function testStatusConstants(): void
    {
        $this->assertEquals('triggered', AlertEvent::STATUS_TRIGGERED);
        $this->assertEquals('acknowledged', AlertEvent::STATUS_ACKNOWLEDGED);
        $this->assertEquals('resolved', AlertEvent::STATUS_RESOLVED);
        $this->assertEquals('suppressed', AlertEvent::STATUS_SUPPRESSED);
    }
}
