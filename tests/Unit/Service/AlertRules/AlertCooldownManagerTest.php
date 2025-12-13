<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AlertRules;

use App\Entity\AlertRule;
use App\Service\AlertRules\AlertCooldownManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for AlertCooldownManager service.
 */
class AlertCooldownManagerTest extends TestCase
{
    private AlertCooldownManager $manager;

    protected function setUp(): void
    {
        // Create manager without database or cache
        $this->manager = new AlertCooldownManager(null, null, new NullLogger());
    }

    public function testIsNotInCooldownInitially(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setCooldownSeconds(300);

        $this->assertFalse($this->manager->isInCooldown($rule));
    }

    public function testStartCooldownMakesRuleInCooldown(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setCooldownSeconds(300);

        $this->manager->startCooldown($rule);

        $this->assertTrue($this->manager->isInCooldown($rule));
    }

    public function testClearCooldownRemovesCooldown(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setCooldownSeconds(300);

        $this->manager->startCooldown($rule);
        $this->assertTrue($this->manager->isInCooldown($rule));

        $this->manager->clearCooldown($rule);
        $this->assertFalse($this->manager->isInCooldown($rule));
    }

    public function testGetRemainingCooldown(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setCooldownSeconds(300);

        $this->manager->startCooldown($rule);

        $remaining = $this->manager->getRemainingCooldown($rule);

        $this->assertGreaterThan(290, $remaining);
        $this->assertLessThanOrEqual(300, $remaining);
    }

    public function testGetRemainingCooldownReturnsZeroWhenNotInCooldown(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setCooldownSeconds(300);

        $remaining = $this->manager->getRemainingCooldown($rule);

        $this->assertEquals(0, $remaining);
    }

    public function testCooldownWithDedupeKey(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setCooldownSeconds(300);
        $rule->setDedupeKey('ip');

        $event1 = ['ip' => '192.168.1.1', 'message' => 'error'];
        $event2 = ['ip' => '192.168.1.2', 'message' => 'error'];

        $this->manager->startCooldown($rule, $event1);

        // Same IP should be in cooldown
        $this->assertTrue($this->manager->isInCooldown($rule, $event1));

        // Different IP should not be in cooldown
        $this->assertFalse($this->manager->isInCooldown($rule, $event2));
    }

    public function testDedupeHashGeneration(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setDedupeKey('message');

        $event1 = ['message' => 'error1'];
        $event2 = ['message' => 'error1'];
        $event3 = ['message' => 'error2'];

        $hash1 = $this->manager->getDedupeHash($rule, $event1);
        $hash2 = $this->manager->getDedupeHash($rule, $event2);
        $hash3 = $this->manager->getDedupeHash($rule, $event3);

        $this->assertEquals($hash1, $hash2);
        $this->assertNotEquals($hash1, $hash3);
    }

    public function testDedupeHashWithoutDedupeKey(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');

        $event1 = ['message' => 'error1'];
        $event2 = ['message' => 'error2'];

        $hash1 = $this->manager->getDedupeHash($rule, $event1);
        $hash2 = $this->manager->getDedupeHash($rule, $event2);

        // Without dedupe key, all events for same rule have same hash
        $this->assertEquals($hash1, $hash2);
    }

    public function testClearAll(): void
    {
        $rule1 = new AlertRule();
        $rule1->setName('rule1');
        $rule1->setCooldownSeconds(300);

        $rule2 = new AlertRule();
        $rule2->setName('rule2');
        $rule2->setCooldownSeconds(300);

        $this->manager->startCooldown($rule1);
        $this->manager->startCooldown($rule2);

        $this->assertTrue($this->manager->isInCooldown($rule1));
        $this->assertTrue($this->manager->isInCooldown($rule2));

        $this->manager->clearAll();

        $this->assertFalse($this->manager->isInCooldown($rule1));
        $this->assertFalse($this->manager->isInCooldown($rule2));
    }

    public function testMultipleRulesIndependentCooldown(): void
    {
        $rule1 = new AlertRule();
        $rule1->setName('rule1');
        $rule1->setCooldownSeconds(300);

        $rule2 = new AlertRule();
        $rule2->setName('rule2');
        $rule2->setCooldownSeconds(300);

        $this->manager->startCooldown($rule1);

        $this->assertTrue($this->manager->isInCooldown($rule1));
        $this->assertFalse($this->manager->isInCooldown($rule2));
    }

    public function testNestedDedupeKey(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setCooldownSeconds(300);
        $rule->setDedupeKey('context.user_id');

        $event1 = ['context' => ['user_id' => '123']];
        $event2 = ['context' => ['user_id' => '456']];

        $this->manager->startCooldown($rule, $event1);

        $this->assertTrue($this->manager->isInCooldown($rule, $event1));
        $this->assertFalse($this->manager->isInCooldown($rule, $event2));
    }

    public function testPruneExpired(): void
    {
        // This test verifies pruneExpired doesn't crash
        // Full testing would require mocking time
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setCooldownSeconds(300);

        $this->manager->startCooldown($rule);
        $this->manager->pruneExpired();

        // Should still be in cooldown after prune
        $this->assertTrue($this->manager->isInCooldown($rule));
    }
}
