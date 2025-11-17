<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Smoke tests to verify the application boots correctly
 */
class ApplicationSmokeTest extends KernelTestCase
{
    public function testKernelBoots(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        // Kernel is booted if bootKernel() succeeded
        $this->assertInstanceOf(\Symfony\Component\HttpKernel\KernelInterface::class, $kernel);
    }

    public function testServiceContainerIsAvailable(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->assertNotNull($container);
        $this->assertTrue($container->has('doctrine'));
        $this->assertTrue($container->has('twig'));
        $this->assertTrue($container->has('router'));
        $this->assertTrue($container->has('security.authorization_checker'));
    }

    public function testCoreServicesAreAccessible(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // Test Doctrine Entity Manager
        $this->assertTrue($container->has('doctrine.orm.entity_manager'));
        $em = $container->get('doctrine.orm.entity_manager');
        $this->assertInstanceOf(\Doctrine\ORM\EntityManagerInterface::class, $em);

        // Test Router
        $this->assertTrue($container->has('router'));
        $router = $container->get('router');
        $this->assertInstanceOf(\Symfony\Component\Routing\RouterInterface::class, $router);

        // Test Twig
        $this->assertTrue($container->has('twig'));
        $twig = $container->get('twig');
        $this->assertInstanceOf(\Twig\Environment::class, $twig);
    }

    public function testParametersAreLoaded(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->assertTrue($container->hasParameter('kernel.project_dir'));
        $this->assertTrue($container->hasParameter('kernel.environment'));
        $this->assertTrue($container->hasParameter('kernel.debug'));

        $this->assertSame('test', $container->getParameter('kernel.environment'));
    }

    public function testCustomServicesAreRegistered(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // Test our custom services
        $this->assertTrue($container->has(\App\Service\SlugGenerator::class));
        $this->assertTrue($container->has(\App\Service\FileUploadService::class));
        $this->assertTrue($container->has(\App\Service\AdminConfigService::class));
        $this->assertTrue($container->has(\App\Service\EmailService::class));
        $this->assertTrue($container->has(\App\Service\PasswordResetService::class));
        $this->assertTrue($container->has(\App\Service\Paginator::class));
    }
}
