<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Authentication Functional Tests
 *
 * Tests the authentication flow including:
 * - Login page rendering
 * - Login form submission
 * - Authentication success/failure
 * - Access control
 * - Logout functionality
 */
class AuthenticationTest extends WebTestCase
{
    /**
     * Test that login page is accessible
     */
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Sign In');
        $this->assertSelectorExists('form[action="/login"]');
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
        $this->assertSelectorExists('input[name="_csrf_token"]');
    }

    /**
     * Test successful login with valid credentials
     */
    public function testSuccessfulLogin(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Submit login form with admin credentials
        $form = $crawler->selectButton('Sign In')->form([
            '_username' => 'admin',
            '_password' => 'admin123',
        ]);

        $client->submit($form);

        // Should redirect to admin dashboard after successful login
        $this->assertResponseRedirects('/admin');

        // Follow redirect
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    /**
     * Test login failure with invalid credentials
     */
    public function testLoginFailureWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Submit login form with invalid credentials
        $form = $crawler->selectButton('Sign In')->form([
            '_username' => 'nonexistent',
            '_password' => 'wrongpassword',
        ]);

        $client->submit($form);

        // Should redirect back to login page with error
        $this->assertResponseRedirects('/login');

        // Follow redirect and check for error message
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-danger');
    }

    /**
     * Test that admin area requires authentication
     */
    public function testAdminAreaRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        // Should redirect to login page
        $this->assertResponseRedirects('/login');
    }

    /**
     * Test that authenticated users can access admin area
     */
    public function testAuthenticatedUserCanAccessAdminArea(): void
    {
        $client = static::createClient();

        // Login first
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Sign In')->form([
            '_username' => 'admin',
            '_password' => 'admin123',
        ]);
        $client->submit($form);

        // Now try to access admin area
        $client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Dashboard');
    }

    /**
     * Test logout functionality
     */
    public function testLogout(): void
    {
        $client = static::createClient();

        // Login first
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Sign In')->form([
            '_username' => 'admin',
            '_password' => 'admin123',
        ]);
        $client->submit($form);

        // Verify we're logged in
        $client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();

        // Logout
        $client->request('GET', '/logout');

        // After logout, should not be able to access admin area
        $client->request('GET', '/admin');
        $this->assertResponseRedirects('/login');
    }

    /**
     * Test that inactive users cannot login
     */
    public function testInactiveUserCannotLogin(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Try to login with inactive user
        $form = $crawler->selectButton('Sign In')->form([
            '_username' => 'inactive',
            '_password' => 'inactive123',
        ]);

        $client->submit($form);

        // Should redirect back to login with error
        $this->assertResponseRedirects('/login');

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-danger');
    }

    /**
     * Test remember me functionality
     */
    public function testRememberMeFunctionality(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Login with remember me checkbox
        $form = $crawler->selectButton('Sign In')->form([
            '_username' => 'admin',
            '_password' => 'admin123',
            '_remember_me' => true,
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/admin');

        // Check that remember me cookie is set
        $cookies = $client->getCookieJar()->all();
        $hasRememberMeCookie = false;

        foreach ($cookies as $cookie) {
            if (str_contains($cookie->getName(), 'REMEMBERME')) {
                $hasRememberMeCookie = true;
                break;
            }
        }

        $this->assertTrue($hasRememberMeCookie, 'Remember me cookie should be set');
    }

    /**
     * Test CSRF protection on login form
     */
    public function testCsrfProtectionOnLoginForm(): void
    {
        $client = static::createClient();
        $client->request('POST', '/login', [
            '_username' => 'admin',
            '_password' => 'admin123',
            '_csrf_token' => 'invalid_token',
        ]);

        // Should fail due to invalid CSRF token
        $this->assertResponseRedirects('/login');
    }
}
