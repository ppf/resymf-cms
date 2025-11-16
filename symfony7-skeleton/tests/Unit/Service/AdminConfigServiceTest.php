<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Settings;
use App\Repository\SettingsRepository;
use App\Service\AdminConfigService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AdminConfigService.
 */
class AdminConfigServiceTest extends TestCase
{
    private AdminConfigService $adminConfigService;
    private SettingsRepository $settingsRepository;

    protected function setUp(): void
    {
        $this->settingsRepository = $this->createMock(SettingsRepository::class);
        $this->adminConfigService = new AdminConfigService($this->settingsRepository);
    }

    public function testGetAdminMenu(): void
    {
        $menu = $this->adminConfigService->getAdminMenu();

        $this->assertIsArray($menu);
        $this->assertArrayHasKey('content', $menu);
        $this->assertArrayHasKey('users', $menu);
        $this->assertArrayHasKey('system', $menu);
    }

    public function testGetMenuSection(): void
    {
        $section = $this->adminConfigService->getMenuSection('content');

        $this->assertIsArray($section);
        $this->assertArrayHasKey('label', $section);
        $this->assertArrayHasKey('icon', $section);
        $this->assertArrayHasKey('items', $section);
        $this->assertSame('Content Management', $section['label']);
    }

    public function testGetMenuSectionReturnsNullForInvalidSection(): void
    {
        $section = $this->adminConfigService->getMenuSection('nonexistent');
        $this->assertNull($section);
    }

    public function testGetEntityConfig(): void
    {
        $config = $this->adminConfigService->getEntityConfig('page');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('label', $config);
        $this->assertArrayHasKey('icon', $config);
        $this->assertArrayHasKey('entity', $config);
        $this->assertSame('App\Entity\Page', $config['entity']);
    }

    public function testGetEntityConfigReturnsNullForInvalidEntity(): void
    {
        $config = $this->adminConfigService->getEntityConfig('nonexistent');
        $this->assertNull($config);
    }

    public function testGetEntityClass(): void
    {
        $class = $this->adminConfigService->getEntityClass('page');
        $this->assertSame('App\Entity\Page', $class);
    }

    public function testGetEntityDefaults(): void
    {
        $defaults = $this->adminConfigService->getEntityDefaults();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('items_per_page', $defaults);
        $this->assertArrayHasKey('enable_search', $defaults);
        $this->assertSame(20, $defaults['items_per_page']);
    }

    public function testGetEntityDefaultsWithKey(): void
    {
        $value = $this->adminConfigService->getEntityDefaults('items_per_page');
        $this->assertSame(20, $value);
    }

    public function testHasAccessWithAdminRole(): void
    {
        $hasAccess = $this->adminConfigService->hasAccess('user', ['ROLE_ADMIN']);
        $this->assertTrue($hasAccess);
    }

    public function testHasAccessWithInsufficientRole(): void
    {
        $hasAccess = $this->adminConfigService->hasAccess('user', ['ROLE_USER']);
        $this->assertFalse($hasAccess);
    }

    public function testHasAccessForNonexistentEntity(): void
    {
        $hasAccess = $this->adminConfigService->hasAccess('nonexistent', ['ROLE_ADMIN']);
        $this->assertFalse($hasAccess);
    }

    public function testGetFilteredMenu(): void
    {
        $menu = $this->adminConfigService->getFilteredMenu(['ROLE_USER']);

        $this->assertIsArray($menu);
        $this->assertArrayHasKey('content', $menu);
        // Users section should not be visible to ROLE_USER
        $this->assertArrayNotHasKey('users', $menu);
    }

    public function testGetFilteredMenuForAdmin(): void
    {
        $menu = $this->adminConfigService->getFilteredMenu(['ROLE_ADMIN', 'ROLE_USER']);

        $this->assertIsArray($menu);
        $this->assertArrayHasKey('content', $menu);
        $this->assertArrayHasKey('users', $menu);
        $this->assertArrayHasKey('system', $menu);
    }

    public function testGetBreadcrumbs(): void
    {
        $breadcrumbs = $this->adminConfigService->getBreadcrumbs('page', 'index');

        $this->assertIsArray($breadcrumbs);
        $this->assertCount(2, $breadcrumbs);
        $this->assertSame('Dashboard', $breadcrumbs[0]['label']);
        $this->assertSame('Pages', $breadcrumbs[1]['label']);
    }

    public function testGetBreadcrumbsWithAction(): void
    {
        $breadcrumbs = $this->adminConfigService->getBreadcrumbs('page', 'edit', 'Edit Page');

        $this->assertIsArray($breadcrumbs);
        $this->assertCount(3, $breadcrumbs);
        $this->assertSame('Dashboard', $breadcrumbs[0]['label']);
        $this->assertSame('Pages', $breadcrumbs[1]['label']);
        $this->assertSame('Edit Page', $breadcrumbs[2]['label']);
    }
}
