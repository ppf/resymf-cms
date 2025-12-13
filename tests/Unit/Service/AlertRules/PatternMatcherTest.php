<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\AlertRules;

use App\Entity\AlertRule;
use App\Service\AlertRules\PatternMatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for PatternMatcher service.
 */
class PatternMatcherTest extends TestCase
{
    private PatternMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new PatternMatcher(new NullLogger());
    }

    public function testMatchesSimplePattern(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_PATTERN);
        $rule->setPatternConfig([
            'regex' => 'error',
            'field' => 'message',
            'case_sensitive' => false,
        ]);

        $event = ['message' => 'An error occurred'];
        $result = $this->matcher->matches($rule, $event);

        $this->assertTrue($result->isMatch());
        $this->assertEquals('error', $result->getFullMatch());
    }

    public function testMatchesWithRegex(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_PATTERN);
        $rule->setPatternConfig([
            'regex' => 'error code: (\d+)',
            'field' => 'message',
        ]);

        $event = ['message' => 'Error code: 500 occurred'];
        $result = $this->matcher->matches($rule, $event);

        $this->assertTrue($result->isMatch());
        $this->assertEquals('500', $result->getCaptures()[1]);
    }

    public function testNoMatchWhenPatternNotFound(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_PATTERN);
        $rule->setPatternConfig([
            'regex' => 'fatal',
            'field' => 'message',
        ]);

        $event = ['message' => 'An error occurred'];
        $result = $this->matcher->matches($rule, $event);

        $this->assertFalse($result->isMatch());
    }

    public function testNoMatchWhenFieldNotFound(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_PATTERN);
        $rule->setPatternConfig([
            'regex' => 'error',
            'field' => 'nonexistent',
        ]);

        $event = ['message' => 'An error occurred'];
        $result = $this->matcher->matches($rule, $event);

        $this->assertFalse($result->isMatch());
        $this->assertStringContainsString('not found', $result->getReason());
    }

    public function testCaseSensitiveMatch(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_PATTERN);
        $rule->setPatternConfig([
            'regex' => 'Error',
            'field' => 'message',
            'case_sensitive' => true,
        ]);

        $event1 = ['message' => 'Error occurred'];
        $event2 = ['message' => 'error occurred'];

        $result1 = $this->matcher->matches($rule, $event1);
        $result2 = $this->matcher->matches($rule, $event2);

        $this->assertTrue($result1->isMatch());
        $this->assertFalse($result2->isMatch());
    }

    public function testCaseInsensitiveMatch(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_PATTERN);
        $rule->setPatternConfig([
            'regex' => 'error',
            'field' => 'message',
            'case_sensitive' => false,
        ]);

        $event1 = ['message' => 'Error occurred'];
        $event2 = ['message' => 'ERROR OCCURRED'];

        $result1 = $this->matcher->matches($rule, $event1);
        $result2 = $this->matcher->matches($rule, $event2);

        $this->assertTrue($result1->isMatch());
        $this->assertTrue($result2->isMatch());
    }

    public function testReturnsNoMatchForNonPatternRule(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_THRESHOLD);

        $event = ['message' => 'An error occurred'];
        $result = $this->matcher->matches($rule, $event);

        $this->assertFalse($result->isMatch());
        $this->assertStringContainsString('not a pattern rule', $result->getReason());
    }

    public function testMatchesNestedField(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_PATTERN);
        $rule->setPatternConfig([
            'regex' => 'critical',
            'field' => 'context.level',
        ]);

        $event = [
            'message' => 'Something happened',
            'context' => ['level' => 'critical'],
        ];
        $result = $this->matcher->matches($rule, $event);

        $this->assertTrue($result->isMatch());
    }

    public function testMatchAll(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_PATTERN);
        $rule->setPatternConfig([
            'regex' => 'error',
            'field' => 'message',
        ]);

        $events = [
            ['message' => 'An error occurred'],
            ['message' => 'Everything is fine'],
            ['message' => 'Another error'],
        ];

        $results = $this->matcher->matchAll($rule, $events);

        $this->assertCount(3, $results);
        $this->assertTrue($results[0]->isMatch());
        $this->assertFalse($results[1]->isMatch());
        $this->assertTrue($results[2]->isMatch());
    }

    public function testFindMatching(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_PATTERN);
        $rule->setPatternConfig([
            'regex' => 'error',
            'field' => 'message',
        ]);

        $events = [
            ['message' => 'An error occurred'],
            ['message' => 'Everything is fine'],
            ['message' => 'Another error'],
        ];

        $matching = $this->matcher->findMatching($rule, $events);

        $this->assertCount(2, $matching);
        $this->assertEquals('An error occurred', $matching[0]['message']);
        $this->assertEquals('Another error', $matching[1]['message']);
    }

    public function testIsValidPattern(): void
    {
        $this->assertTrue($this->matcher->isValidPattern('error.*'));
        $this->assertTrue($this->matcher->isValidPattern('\\d+'));
        $this->assertTrue($this->matcher->isValidPattern('(a|b)'));
    }

    public function testPatternWithDelimiters(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_PATTERN);
        $rule->setPatternConfig([
            'regex' => '/error/i',
            'field' => 'message',
        ]);

        $event = ['message' => 'ERROR occurred'];
        $result = $this->matcher->matches($rule, $event);

        $this->assertTrue($result->isMatch());
    }

    public function testComplexRegexPattern(): void
    {
        $rule = new AlertRule();
        $rule->setName('test_rule');
        $rule->setType(AlertRule::TYPE_PATTERN);
        $rule->setPatternConfig([
            'regex' => '(?:fatal|critical|emergency)',
            'field' => 'message',
        ]);

        $events = [
            ['message' => 'fatal error'],
            ['message' => 'critical warning'],
            ['message' => 'emergency shutdown'],
            ['message' => 'info message'],
        ];

        $matching = $this->matcher->findMatching($rule, $events);

        $this->assertCount(3, $matching);
    }
}
