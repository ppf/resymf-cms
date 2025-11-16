<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use App\Entity\User;
use App\Entity\Settings;
use App\Entity\Page;
use App\Entity\Category;
use App\Entity\Theme;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Smoke tests to verify database connectivity and schema
 */
class DatabaseSmokeTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
    }

    public function testDatabaseConnection(): void
    {
        $connection = $this->entityManager->getConnection();

        $this->assertTrue($connection->isConnected() || $connection->connect());
        $this->assertNotNull($connection->getDatabase());
    }

    public function testSchemaIsValid(): void
    {
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $allMetadata = $metadataFactory->getAllMetadata();

        $this->assertNotEmpty($allMetadata, 'No entity metadata found');
        $this->assertGreaterThanOrEqual(5, count($allMetadata), 'Expected at least 5 entities');
    }

    public function testEntityMetadataIsLoaded(): void
    {
        $metadataFactory = $this->entityManager->getMetadataFactory();

        // Test that core entities have metadata
        $this->assertTrue($metadataFactory->hasMetadataFor(User::class));
        $this->assertTrue($metadataFactory->hasMetadataFor(Settings::class));
        $this->assertTrue($metadataFactory->hasMetadataFor(Page::class));
        $this->assertTrue($metadataFactory->hasMetadataFor(Category::class));
        $this->assertTrue($metadataFactory->hasMetadataFor(Theme::class));
    }

    public function testRepositoriesAreAccessible(): void
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        $this->assertInstanceOf(\App\Repository\UserRepository::class, $userRepo);

        $settingsRepo = $this->entityManager->getRepository(Settings::class);
        $this->assertInstanceOf(\App\Repository\SettingsRepository::class, $settingsRepo);

        $pageRepo = $this->entityManager->getRepository(Page::class);
        $this->assertInstanceOf(\App\Repository\PageRepository::class, $pageRepo);

        $categoryRepo = $this->entityManager->getRepository(Category::class);
        $this->assertInstanceOf(\App\Repository\CategoryRepository::class, $categoryRepo);

        $themeRepo = $this->entityManager->getRepository(Theme::class);
        $this->assertInstanceOf(\App\Repository\ThemeRepository::class, $themeRepo);
    }

    public function testCanQueryDatabase(): void
    {
        $connection = $this->entityManager->getConnection();

        // Simple query to test database is working
        $result = $connection->executeQuery('SELECT 1 as test')->fetchAssociative();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test', $result);
        $this->assertEquals(1, $result['test']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
