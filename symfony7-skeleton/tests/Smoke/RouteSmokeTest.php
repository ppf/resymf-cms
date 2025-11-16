<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Smoke tests to verify basic routes are accessible
 */
class RouteSmokeTest extends WebTestCase
{
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Login');
    }

    public function testAdminAreaRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $this->assertResponseRedirects('/login');
    }

    public function testHomePageResponds(): void
    {
        $client = static::createClient();

        // Try to access home page (might be 404 if no homepage is set, but should respond)
        $client->request('GET', '/');

        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]),
            sprintf('Expected 200 or 404, got %d', $statusCode)
        );
    }

    public function testRouterIsWorking(): void
    {
        $client = static::createClient();
        $router = static::getContainer()->get('router');

        $this->assertNotNull($router);

        // Test that core routes exist
        $routes = $router->getRouteCollection();
        $this->assertGreaterThan(0, $routes->count());

        // Check for critical routes
        $this->assertNotNull($routes->get('app_login'));
        $this->assertNotNull($routes->get('app_logout'));
        $this->assertNotNull($routes->get('admin_dashboard'));
    }

    public function testCriticalRoutesExist(): void
    {
        $client = static::createClient();
        $router = static::getContainer()->get('router');
        $routes = $router->getRouteCollection();

        $criticalRoutes = [
            'app_login',
            'app_logout',
            'admin_dashboard',
            'admin_user_index',
            'admin_user_new',
            'admin_page_index',
            'admin_page_new',
            'admin_category_index',
            'admin_category_new',
            'admin_theme_index',
            'admin_theme_new',
            'cms_page',
        ];

        foreach ($criticalRoutes as $routeName) {
            $this->assertNotNull(
                $routes->get($routeName),
                sprintf('Critical route "%s" not found', $routeName)
            );
        }
    }

    public function testApiEndpointsAreAccessible(): void
    {
        $client = static::createClient();

        // Test file upload endpoint (should require authentication)
        $client->request('POST', '/admin/upload');

        // Should redirect to login or return 403 (not 500 error)
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_FOUND, Response::HTTP_FORBIDDEN, Response::HTTP_UNAUTHORIZED]),
            sprintf('Expected redirect or 403/401, got %d', $statusCode)
        );
    }

    public function test404PageWorks(): void
    {
        $client = static::createClient();
        $client->request('GET', '/this-page-definitely-does-not-exist-' . uniqid());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
