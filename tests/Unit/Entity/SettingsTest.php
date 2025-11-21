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
        $this->assertEquals('en', $settings->getDefaultLocale());
        $this->assertEquals('UTC', $settings->getTimezone());
        $this->assertEquals(10, $settings->getItemsPerPage());
        $this->assertFalse($settings->isMaintenanceMode());
        $this->assertTrue($settings->isRegistrationEnabled());
        $this->assertFalse($settings->isEmailVerificationRequired());
        $this->assertInstanceOf(\DateTimeImmutable::class, $settings->getCreatedAt());
        $this->assertNull($settings->getUpdatedAt());
    }

    public function testSiteNameGetterAndSetter(): void
    {
        $settings = new Settings();
        $settings->setSiteName('My CMS');

        $this->assertEquals('My CMS', $settings->getSiteName());
    }

    public function testSiteTaglineGetterAndSetter(): void
    {
        $settings = new Settings();

        $this->assertNull($settings->getSiteTagline());

        $settings->setSiteTagline('The best CMS');

        $this->assertEquals('The best CMS', $settings->getSiteTagline());
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

    public function testAdminEmailGetterAndSetter(): void
    {
        $settings = new Settings();

        $this->assertNull($settings->getAdminEmail());

        $settings->setAdminEmail('admin@example.com');

        $this->assertEquals('admin@example.com', $settings->getAdminEmail());
    }

    public function testGoogleAnalyticsIdGetterAndSetter(): void
    {
        $settings = new Settings();

        $this->assertNull($settings->getGoogleAnalyticsId());

        $settings->setGoogleAnalyticsId('UA-123456-1');

        $this->assertEquals('UA-123456-1', $settings->getGoogleAnalyticsId());
    }

    public function testGoogleTagManagerKeyGetterAndSetter(): void
    {
        $settings = new Settings();

        $this->assertNull($settings->getGoogleTagManagerKey());

        $settings->setGoogleTagManagerKey('GTM-XXXXX');

        $this->assertEquals('GTM-XXXXX', $settings->getGoogleTagManagerKey());
    }

    public function testMaintenanceModeGetterAndSetter(): void
    {
        $settings = new Settings();

        $this->assertFalse($settings->isMaintenanceMode());

        $settings->setMaintenanceMode(true);

        $this->assertTrue($settings->isMaintenanceMode());
    }

    public function testMaintenanceMessageGetterAndSetter(): void
    {
        $settings = new Settings();

        $this->assertNull($settings->getMaintenanceMessage());

        $settings->setMaintenanceMessage('Site under maintenance');

        $this->assertEquals('Site under maintenance', $settings->getMaintenanceMessage());
    }

    public function testDefaultLocaleGetterAndSetter(): void
    {
        $settings = new Settings();
        $settings->setDefaultLocale('fr');

        $this->assertEquals('fr', $settings->getDefaultLocale());
    }

    public function testTimezoneGetterAndSetter(): void
    {
        $settings = new Settings();
        $settings->setTimezone('America/New_York');

        $this->assertEquals('America/New_York', $settings->getTimezone());
    }

    public function testItemsPerPageGetterAndSetter(): void
    {
        $settings = new Settings();
        $settings->setItemsPerPage(20);

        $this->assertEquals(20, $settings->getItemsPerPage());
    }

    public function testRegistrationEnabledGetterAndSetter(): void
    {
        $settings = new Settings();

        $this->assertTrue($settings->isRegistrationEnabled());

        $settings->setRegistrationEnabled(false);

        $this->assertFalse($settings->isRegistrationEnabled());
    }

    public function testEmailVerificationRequiredGetterAndSetter(): void
    {
        $settings = new Settings();

        $this->assertFalse($settings->isEmailVerificationRequired());

        $settings->setEmailVerificationRequired(true);

        $this->assertTrue($settings->isEmailVerificationRequired());
    }

    public function testSocialUrlGetters(): void
    {
        $settings = new Settings();

        $this->assertNull($settings->getFacebookUrl());
        $this->assertNull($settings->getTwitterUrl());
        $this->assertNull($settings->getLinkedinUrl());
        $this->assertNull($settings->getGithubUrl());
    }

    public function testSocialUrlSetters(): void
    {
        $settings = new Settings();

        $settings->setFacebookUrl('facebook.com/example');
        $settings->setTwitterUrl('twitter.com/example');
        $settings->setLinkedinUrl('linkedin.com/company/example');
        $settings->setGithubUrl('github.com/example');

        $this->assertEquals('facebook.com/example', $settings->getFacebookUrl());
        $this->assertEquals('twitter.com/example', $settings->getTwitterUrl());
        $this->assertEquals('linkedin.com/company/example', $settings->getLinkedinUrl());
        $this->assertEquals('github.com/example', $settings->getGithubUrl());
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
            ->setSiteTagline('Tagline')
            ->setDefaultLocale('en')
            ->setTimezone('UTC')
            ->setItemsPerPage(15);

        $this->assertSame($settings, $result);
    }
}
