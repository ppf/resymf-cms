<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Settings entity.
 */
class SettingsTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $settings = new Settings();

        $this->assertNull($settings->getId());
        $this->assertEquals('ReSymf CMS', $settings->getSiteName());
        $this->assertEquals('ReSymf CMS Admin', $settings->getAdminPanelTitle());
        $this->assertEquals('en', $settings->getDefaultLocale());
        $this->assertFalse($settings->isHeadlessMode());
        $this->assertFalse($settings->isEnforce2fa());
        $this->assertNull($settings->getSeoDescription());
        $this->assertNull($settings->getSeoKeywords());
        $this->assertInstanceOf(\DateTimeImmutable::class, $settings->getCreatedAt());
        $this->assertNull($settings->getUpdatedAt());
    }

    public function testSiteNameGetterAndSetter(): void
    {
        $settings = new Settings();
        $settings->setSiteName('My CMS');

        $this->assertEquals('My CMS', $settings->getSiteName());
    }

    public function testAdminPanelTitleGetterAndSetter(): void
    {
        $settings = new Settings();
        $settings->setAdminPanelTitle('Custom Admin Panel');

        $this->assertEquals('Custom Admin Panel', $settings->getAdminPanelTitle());
    }

    public function testSeoDescriptionGetterAndSetter(): void
    {
        $settings = new Settings();

        $this->assertNull($settings->getSeoDescription());

        $settings->setSeoDescription('A powerful content management system');

        $this->assertEquals('A powerful content management system', $settings->getSeoDescription());
    }

    public function testSeoKeywordsGetterAndSetter(): void
    {
        $settings = new Settings();

        $this->assertNull($settings->getSeoKeywords());

        $settings->setSeoKeywords('cms, content, management');

        $this->assertEquals('cms, content, management', $settings->getSeoKeywords());
    }

    public function testDefaultLocaleGetterAndSetter(): void
    {
        $settings = new Settings();
        $settings->setDefaultLocale('fr');

        $this->assertEquals('fr', $settings->getDefaultLocale());
    }

    public function testHeadlessModeGetterAndSetter(): void
    {
        $settings = new Settings();

        $this->assertFalse($settings->isHeadlessMode());

        $settings->setHeadlessMode(true);

        $this->assertTrue($settings->isHeadlessMode());
    }

    public function testEnforce2faGetterAndSetter(): void
    {
        $settings = new Settings();

        $this->assertFalse($settings->isEnforce2fa());

        $settings->setEnforce2fa(true);

        $this->assertTrue($settings->isEnforce2fa());
    }

    public function testCreatedAtGetterAndSetter(): void
    {
        $settings = new Settings();
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');
        $settings->setCreatedAt($date);

        $this->assertEquals($date, $settings->getCreatedAt());
    }

    public function testUpdatedAtInitiallyNull(): void
    {
        $settings = new Settings();

        $this->assertNull($settings->getUpdatedAt());
    }

    public function testSetUpdatedAt(): void
    {
        $settings = new Settings();
        $before = new \DateTimeImmutable();

        $settings->setUpdatedAt();

        $after = new \DateTimeImmutable();

        $this->assertNotNull($settings->getUpdatedAt());
        $this->assertGreaterThanOrEqual($before, $settings->getUpdatedAt());
        $this->assertLessThanOrEqual($after, $settings->getUpdatedAt());
    }

    public function testToString(): void
    {
        $settings = new Settings();
        $settings->setSiteName('My Site');

        $this->assertEquals('My Site', (string) $settings);
    }

    public function testFluentInterface(): void
    {
        $settings = new Settings();

        $result = $settings
            ->setSiteName('Test')
            ->setAdminPanelTitle('Admin')
            ->setDefaultLocale('en')
            ->setHeadlessMode(false)
            ->setEnforce2fa(false);

        $this->assertSame($settings, $result);
    }
}
