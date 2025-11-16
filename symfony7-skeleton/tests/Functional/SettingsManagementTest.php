<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Settings;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests for Settings management
 */
class SettingsManagementTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        // Create database schema
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createSchema(): void
    {
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function createAdminUser(): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setPassword($hasher->hashPassword($admin, 'admin123'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsActive(true);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        return $admin;
    }

    private function loginAsAdmin($client): void
    {
        $this->createAdminUser();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form([
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $client->submit($form);
        $client->followRedirect();
    }

    public function testSettingsPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/settings');

        $this->assertResponseRedirects('/login');
    }

    public function testSettingsPageRequiresAdminRole(): void
    {
        $client = static::createClient();

        // Create regular user (not admin)
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setUsername('regularuser');
        $user->setEmail('user@example.com');
        $user->setFirstName('Regular');
        $user->setLastName('User');
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Login as regular user
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form([
            'username' => 'regularuser',
            'password' => 'password',
        ]);

        $client->submit($form);
        $client->followRedirect();

        // Try to access settings
        $client->request('GET', '/admin/settings');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testSettingsPageRendersForAdmin(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/admin/settings');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Settings');
    }

    public function testSettingsFormHasExpectedFields(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/admin/settings');

        // Test that form exists
        $this->assertSelectorExists('form[name="settings"]');

        // Test that key fields exist
        $this->assertSelectorExists('input[name="settings[siteName]"]');
        $this->assertSelectorExists('textarea[name="settings[siteDescription]"]');
        $this->assertSelectorExists('input[name="settings[siteKeywords]"]');
        $this->assertSelectorExists('input[name="settings[adminEmail]"]');
        $this->assertSelectorExists('input[name="settings[contactEmail]"]');
        $this->assertSelectorExists('input[name="settings[timezone]"]');
        $this->assertSelectorExists('input[name="settings[locale]"]');
    }

    public function testCanUpdateSettings(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        // Get settings form
        $crawler = $client->request('GET', '/admin/settings');

        // Fill and submit form
        $form = $crawler->selectButton('Save')->form([
            'settings[siteName]' => 'My Test Site',
            'settings[siteDescription]' => 'A test site description',
            'settings[siteKeywords]' => 'test, keywords',
            'settings[adminEmail]' => 'admin@test.com',
            'settings[contactEmail]' => 'contact@test.com',
            'settings[timezone]' => 'America/New_York',
            'settings[locale]' => 'en_US',
        ]);

        $client->submit($form);

        // Should redirect back to settings page
        $this->assertResponseRedirects('/admin/settings');

        $crawler = $client->followRedirect();

        // Check flash message
        $this->assertSelectorTextContains('.alert-success', 'Settings have been updated successfully');

        // Verify settings were saved in database
        $settingsRepo = $this->entityManager->getRepository(Settings::class);
        $settings = $settingsRepo->getOrCreate();

        $this->assertEquals('My Test Site', $settings->getSiteName());
        $this->assertEquals('A test site description', $settings->getSiteDescription());
        $this->assertEquals('test, keywords', $settings->getSiteKeywords());
        $this->assertEquals('admin@test.com', $settings->getAdminEmail());
        $this->assertEquals('contact@test.com', $settings->getContactEmail());
        $this->assertEquals('America/New_York', $settings->getTimezone());
        $this->assertEquals('en_US', $settings->getLocale());
    }

    public function testSettingsValidation(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        // Get settings form
        $crawler = $client->request('GET', '/admin/settings');

        // Submit invalid data
        $form = $crawler->selectButton('Save')->form([
            'settings[siteName]' => '', // Empty site name (should be required)
            'settings[adminEmail]' => 'invalid-email', // Invalid email
        ]);

        $client->submit($form);

        // Should not redirect (form has errors)
        $this->assertResponseIsSuccessful();

        // Should show validation errors
        $this->assertSelectorExists('.invalid-feedback');
    }

    public function testSettingsSingletonPattern(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        // Create first settings
        $crawler = $client->request('GET', '/admin/settings');
        $form = $crawler->selectButton('Save')->form([
            'settings[siteName]' => 'First Site Name',
        ]);
        $client->submit($form);
        $client->followRedirect();

        // Update settings again
        $crawler = $client->request('GET', '/admin/settings');
        $form = $crawler->selectButton('Save')->form([
            'settings[siteName]' => 'Second Site Name',
        ]);
        $client->submit($form);
        $client->followRedirect();

        // Verify only one settings record exists
        $settingsRepo = $this->entityManager->getRepository(Settings::class);
        $allSettings = $settingsRepo->findAll();

        $this->assertCount(1, $allSettings, 'Should only have one Settings record');
        $this->assertEquals('Second Site Name', $allSettings[0]->getSiteName());
    }

    public function testCanUpdateMaintenanceMode(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/admin/settings');

        // Enable maintenance mode
        $form = $crawler->selectButton('Save')->form([
            'settings[maintenanceMode]' => '1',
            'settings[maintenanceMessage]' => 'Site is under maintenance',
        ]);

        $client->submit($form);
        $client->followRedirect();

        // Verify in database
        $settingsRepo = $this->entityManager->getRepository(Settings::class);
        $settings = $settingsRepo->getOrCreate();

        $this->assertTrue($settings->isMaintenanceMode());
        $this->assertEquals('Site is under maintenance', $settings->getMaintenanceMessage());
    }

    public function testCanUpdateSocialMediaSettings(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/admin/settings');

        $form = $crawler->selectButton('Save')->form([
            'settings[facebookUrl]' => 'https://facebook.com/mypage',
            'settings[twitterUrl]' => 'https://twitter.com/mypage',
            'settings[linkedinUrl]' => 'https://linkedin.com/company/mypage',
        ]);

        $client->submit($form);
        $client->followRedirect();

        // Verify in database
        $settingsRepo = $this->entityManager->getRepository(Settings::class);
        $settings = $settingsRepo->getOrCreate();

        $this->assertEquals('https://facebook.com/mypage', $settings->getFacebookUrl());
        $this->assertEquals('https://twitter.com/mypage', $settings->getTwitterUrl());
        $this->assertEquals('https://linkedin.com/company/mypage', $settings->getLinkedinUrl());
    }

    public function testCanUpdateAnalyticsSettings(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/admin/settings');

        $form = $crawler->selectButton('Save')->form([
            'settings[googleAnalyticsId]' => 'UA-123456-1',
        ]);

        $client->submit($form);
        $client->followRedirect();

        // Verify in database
        $settingsRepo = $this->entityManager->getRepository(Settings::class);
        $settings = $settingsRepo->getOrCreate();

        $this->assertEquals('UA-123456-1', $settings->getGoogleAnalyticsId());
    }
}
