<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke tests to verify the application boots correctly.
 *
 * These are quick sanity checks that the application is functional.
 */
class ApplicationAvailabilityTest extends WebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful(string $url, int $expectedCode): void
    {
        $client = self::createClient();
        $client->request('GET', $url);

        $this->assertEquals($expectedCode, $client->getResponse()->getStatusCode());
    }

    public function urlProvider(): \Generator
    {
        // Public pages (should be accessible)
        yield 'homepage' => ['/', 200];
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
