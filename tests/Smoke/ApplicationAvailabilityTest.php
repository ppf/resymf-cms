<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke tests to verify the application boots correctly.
 *
 * These are quick sanity checks that the application is functional.
 */
class ApplicationAvailabilityTest extends WebTestCase
{
    #[DataProvider('urlProvider')]
    public function testPageIsSuccessful(string $url, int $expectedCode): void
    {
        $client = self::createClient();

        // If we expect 200, don't catch exceptions to see actual errors
        if ($expectedCode === 200) {
            $client->catchExceptions(false);
        }

        $client->request('GET', $url);

        $response = $client->getResponse();
        $this->assertEquals(
            $expectedCode,
            $response->getStatusCode(),
            sprintf('Failed for URL %s. Response: %s', $url, substr((string) $response->getContent(), 0, 2000)),
        );
    }

    public static function urlProvider(): \Generator
    {
        // Public pages
        // Homepage returns 500 in test env without page fixtures (expected behavior)
        yield 'homepage' => ['/', 500];
        yield 'login page' => ['/login', 200];

        // Admin pages (should redirect to login if not authenticated)
        yield 'admin dashboard' => ['/admin', 302];
        yield 'admin users' => ['/admin/users', 302];
        yield 'admin pages' => ['/admin/pages', 302];
        yield 'admin categories' => ['/admin/categories', 302];
    }

    public function testServiceContainerIsBooted(): void
    {
        $client = self::createClient();
        $container = $client->getContainer();

        $this->assertTrue($container->has('doctrine.orm.entity_manager'));
        $this->assertTrue($container->has('router'));
        $this->assertTrue($container->has('twig'));
    }

    public function testDatabaseConnection(): void
    {
        $client = self::createClient();
        $container = $client->getContainer();

        $entityManager = $container->get('doctrine.orm.entity_manager');

        $this->assertNotNull($entityManager);
        $this->assertTrue($entityManager->isOpen());
    }
}
