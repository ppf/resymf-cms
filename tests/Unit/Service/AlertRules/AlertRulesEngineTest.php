<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AlertRules;

use App\Entity\AlertEvent;
use App\Entity\AlertRule;
use App\Service\AlertRules\AlertCooldownManager;
use App\Service\AlertRules\AlertRuleParser;
use App\Service\AlertRules\AlertRulesEngine;
use App\Service\AlertRules\PatternMatcher;
use App\Service\AlertRules\SlidingWindowAggregator;
use App\Service\AlertRules\ThresholdEvaluator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for AlertRulesEngine service.
 */
class AlertRulesEngineTest extends TestCase
{
    private AlertRulesEngine $engine;
    private AlertCooldownManager $cooldownManager;

    protected function setUp(): void
    {
        $logger = new NullLogger();
        $parser = new AlertRuleParser($logger);
        $patternMatcher = new PatternMatcher($logger);
        $aggregator = new SlidingWindowAggregator($logger);
        $thresholdEvaluator = new ThresholdEvaluator($aggregator, $logger);
        $this->cooldownManager = new AlertCooldownManager(null, null, $logger);

        $config = [
            'enabled' => true,
            'rules' => [
                [
                    'name' => 'error_pattern',
                    'type' => 'pattern',
                    'priority' => 'high',
                    'pattern' => [
                        'regex' => 'error',
                        'field' => 'message',
                    ],
                    'cooldown' => '5 minutes',
                    'actions' => [
                        ['type' => 'log', 'target' => 'alert'],
                    ],
                ],
                [
                    'name' => 'threshold_rule',
                    'type' => 'threshold',
                    'priority' => 'critical',
                    'threshold' => [
                        'count' => 3,
                        'window' => '1 minute',
                        'operator' => 'gte',
                    ],
                    'aggregation' => [
                        'type' => 'count',
                    ],
                    'cooldown' => '5 minutes',
                ],
            ],
        ];

        $this->engine = new AlertRulesEngine(
            $parser,
            $patternMatcher,
            $thresholdEvaluator,
            $aggregator,
            $this->cooldownManager,
            null,
            null,
            $logger,
            $config
        );
    }

    public function testEngineInitializes(): void
    {
        $this->engine->initialize();

        $this->assertTrue($this->engine->isEnabled());
        $this->assertCount(2, $this->engine->getRules());
    }

    public function testGetRules(): void
    {
        $rules = $this->engine->getRules();

        $this->assertArrayHasKey('error_pattern', $rules);
        $this->assertArrayHasKey('threshold_rule', $rules);
    }

    public function testGetRule(): void
    {
        $rule = $this->engine->getRule('error_pattern');

        $this->assertInstanceOf(AlertRule::class, $rule);
        $this->assertEquals('error_pattern', $rule->getName());
    }

    public function testGetRuleReturnsNullForUnknown(): void
    {
        $rule = $this->engine->getRule('nonexistent');

        $this->assertNull($rule);
    }

    public function testEvaluatePatternMatch(): void
    {
        $event = ['message' => 'An error occurred'];

        $alerts = $this->engine->evaluate($event);

        $this->assertCount(1, $alerts);
        $this->assertArrayHasKey('error_pattern', $alerts);
        $this->assertInstanceOf(AlertEvent::class, $alerts['error_pattern']);
        $this->assertEquals('error_pattern', $alerts['error_pattern']->getRuleName());
        $this->assertEquals(AlertRule::PRIORITY_HIGH, $alerts['error_pattern']->getPriority());
    }

    public function testEvaluatePatternNoMatch(): void
    {
        $event = ['message' => 'Everything is fine'];

        $alerts = $this->engine->evaluate($event);

        $this->assertEmpty($alerts);
    }

    public function testEvaluateRespectsCoold(): void
    {
        $event = ['message' => 'An error occurred'];

        // First evaluation triggers alert
        $alerts1 = $this->engine->evaluate($event);
        $this->assertCount(1, $alerts1);

        // Second evaluation should be suppressed due to cooldown
        $alerts2 = $this->engine->evaluate($event);
        $this->assertEmpty($alerts2);
    }

    public function testEvaluateThresholdRule(): void
    {
        // Add enough events to trigger threshold
        $this->engine->addEvent(['level' => 'error']);
        $this->engine->addEvent(['level' => 'error']);
        $this->engine->addEvent(['level' => 'error']);

        $alerts = $this->engine->evaluate(['level' => 'error']);

        $this->assertArrayHasKey('threshold_rule', $alerts);
    }

    public function testEvaluateThresholdRuleNotExceeded(): void
    {
        // Not enough events to trigger
        $this->engine->addEvent(['level' => 'error']);

        $alerts = $this->engine->evaluate(['level' => 'info']);

        $this->assertArrayNotHasKey('threshold_rule', $alerts);
    }

    public function testProcessEventAddsAndEvaluates(): void
    {
        // Process events until threshold is reached
        $this->engine->processEvent(['level' => 'error', 'message' => 'test1']);
        $this->engine->processEvent(['level' => 'error', 'message' => 'test2']);
        $alerts = $this->engine->processEvent(['level' => 'error', 'message' => 'test3']);

        $this->assertArrayHasKey('threshold_rule', $alerts);
    }

    public function testAlertEventHasCooldownSet(): void
    {
        $event = ['message' => 'An error occurred'];
        $alerts = $this->engine->evaluate($event);

        $alertEvent = $alerts['error_pattern'];

        $this->assertNotNull($alertEvent->getCooldownUntil());
        $this->assertTrue($alertEvent->getCooldownUntil() > new \DateTimeImmutable());
    }

    public function testAlertEventHasDedupeHash(): void
    {
        $event = ['message' => 'An error occurred'];
        $alerts = $this->engine->evaluate($event);

        $alertEvent = $alerts['error_pattern'];

        $this->assertNotNull($alertEvent->getDedupeHash());
        $this->assertEquals(64, strlen($alertEvent->getDedupeHash()));
    }

    public function testDisabledRuleNotEvaluated(): void
    {
        $logger = new NullLogger();
        $parser = new AlertRuleParser($logger);
        $patternMatcher = new PatternMatcher($logger);
        $aggregator = new SlidingWindowAggregator($logger);
        $thresholdEvaluator = new ThresholdEvaluator($aggregator, $logger);
        $cooldownManager = new AlertCooldownManager(null, null, $logger);

        $config = [
            'enabled' => true,
            'rules' => [
                [
                    'name' => 'disabled_rule',
                    'type' => 'pattern',
                    'pattern' => ['regex' => 'error'],
                    'enabled' => false,
                ],
            ],
        ];

        $engine = new AlertRulesEngine(
            $parser,
            $patternMatcher,
            $thresholdEvaluator,
            $aggregator,
            $cooldownManager,
            null,
            null,
            $logger,
            $config
        );

        $alerts = $engine->evaluate(['message' => 'An error occurred']);

        $this->assertEmpty($alerts);
    }

    public function testDisabledEngineDoesNotEvaluate(): void
    {
        $logger = new NullLogger();
        $parser = new AlertRuleParser($logger);
        $patternMatcher = new PatternMatcher($logger);
        $aggregator = new SlidingWindowAggregator($logger);
        $thresholdEvaluator = new ThresholdEvaluator($aggregator, $logger);
        $cooldownManager = new AlertCooldownManager(null, null, $logger);

        $config = [
            'enabled' => false,
            'rules' => [
                [
                    'name' => 'error_pattern',
                    'type' => 'pattern',
                    'pattern' => ['regex' => 'error'],
                ],
            ],
        ];

        $engine = new AlertRulesEngine(
            $parser,
            $patternMatcher,
            $thresholdEvaluator,
            $aggregator,
            $cooldownManager,
            null,
            null,
            $logger,
            $config
        );

        $this->assertFalse($engine->isEnabled());

        $alerts = $engine->evaluate(['message' => 'An error occurred']);

        $this->assertEmpty($alerts);
    }

    public function testReload(): void
    {
        $this->engine->initialize();
        $rules1 = $this->engine->getRules();

        $this->engine->reload();
        $rules2 = $this->engine->getRules();

        $this->assertEquals(count($rules1), count($rules2));
    }

    public function testEvaluateRuleDirectly(): void
    {
        $rule = $this->engine->getRule('error_pattern');
        $event = ['message' => 'An error occurred'];

        $alert = $this->engine->evaluateRule($rule, $event);

        $this->assertInstanceOf(AlertEvent::class, $alert);
        $this->assertEquals('error_pattern', $alert->getRuleName());
    }

    public function testMultipleMatchingRules(): void
    {
        $logger = new NullLogger();
        $parser = new AlertRuleParser($logger);
        $patternMatcher = new PatternMatcher($logger);
        $aggregator = new SlidingWindowAggregator($logger);
        $thresholdEvaluator = new ThresholdEvaluator($aggregator, $logger);
        $cooldownManager = new AlertCooldownManager(null, null, $logger);

        $config = [
            'enabled' => true,
            'rules' => [
                [
                    'name' => 'rule1',
                    'type' => 'pattern',
                    'pattern' => ['regex' => 'error'],
                    'cooldown' => '5 minutes',
                ],
                [
                    'name' => 'rule2',
                    'type' => 'pattern',
                    'pattern' => ['regex' => 'critical'],
                    'cooldown' => '5 minutes',
                ],
            ],
        ];

        $engine = new AlertRulesEngine(
            $parser,
            $patternMatcher,
            $thresholdEvaluator,
            $aggregator,
            $cooldownManager,
            null,
            null,
            $logger,
            $config
        );

        $event = ['message' => 'critical error detected'];
        $alerts = $engine->evaluate($event);

        $this->assertCount(2, $alerts);
        $this->assertArrayHasKey('rule1', $alerts);
        $this->assertArrayHasKey('rule2', $alerts);
    }
}
