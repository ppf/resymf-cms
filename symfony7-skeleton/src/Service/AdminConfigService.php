<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Settings;
use App\Repository\SettingsRepository;

/**
 * Service for managing admin panel configuration.
 *
 * Provides centralized access to admin menu structure, entity configuration,
 * and site-wide settings for the admin interface.
 */
class AdminConfigService
{
    /**
     * Admin menu configuration.
     *
     * Defines the structure of the admin navigation menu with entities,
     * labels, icons, and access control.
     */
    private const ADMIN_MENU = [
        'content' => [
            'label' => 'Content Management',
            'icon' => 'bi-file-text',
            'items' => [
                'page' => [
                    'label' => 'Pages',
                    'icon' => 'bi-file-earmark-text',
                    'entity' => 'App\Entity\Page',
                    'route' => 'admin_page',
                    'roles' => ['ROLE_USER'],
                ],
                'category' => [
                    'label' => 'Categories',
                    'icon' => 'bi-folder',
                    'entity' => 'App\Entity\Category',
                    'route' => 'admin_category',
                    'roles' => ['ROLE_USER'],
                ],
            ],
        ],
        'appearance' => [
            'label' => 'Appearance',
            'icon' => 'bi-palette',
            'items' => [
                'theme' => [
                    'label' => 'Themes',
                    'icon' => 'bi-brush',
                    'entity' => 'App\Entity\Theme',
                    'route' => 'admin_theme',
                    'roles' => ['ROLE_ADMIN'],
                ],
            ],
        ],
        'users' => [
            'label' => 'Users & Security',
            'icon' => 'bi-people',
            'items' => [
                'user' => [
                    'label' => 'Users',
                    'icon' => 'bi-person',
                    'entity' => 'App\Entity\User',
                    'route' => 'admin_user',
                    'roles' => ['ROLE_ADMIN'],
                ],
            ],
        ],
        'system' => [
            'label' => 'System',
            'icon' => 'bi-gear',
            'items' => [
                'settings' => [
                    'label' => 'Settings',
                    'icon' => 'bi-sliders',
                    'route' => 'admin_settings',
                    'roles' => ['ROLE_ADMIN'],
                ],
            ],
        ],
    ];

    /**
     * Entity configuration defaults.
     *
     * Default settings for CRUD operations on entities.
     */
    private const ENTITY_DEFAULTS = [
        'items_per_page' => 20,
        'enable_search' => true,
        'enable_sorting' => true,
        'enable_filters' => true,
        'show_id_column' => false,
        'date_format' => 'Y-m-d H:i',
        'short_date_format' => 'Y-m-d',
    ];

    public function __construct(
        private readonly SettingsRepository $settingsRepository
    ) {
    }

    /**
     * Get the complete admin menu structure.
     *
     * @return array<string, mixed> The menu configuration
     */
    public function getAdminMenu(): array
    {
        return self::ADMIN_MENU;
    }

    /**
     * Get menu items for a specific section.
     *
     * @param string $section The section name (e.g., 'content', 'users')
     *
     * @return array<string, mixed>|null The section configuration or null if not found
     */
    public function getMenuSection(string $section): ?array
    {
        return self::ADMIN_MENU[$section] ?? null;
    }

    /**
     * Get configuration for a specific entity.
     *
     * @param string $entityName The entity name (e.g., 'page', 'user')
     *
     * @return array<string, mixed>|null The entity configuration or null if not found
     */
    public function getEntityConfig(string $entityName): ?array
    {
        foreach (self::ADMIN_MENU as $section) {
            if (isset($section['items'][$entityName])) {
                return $section['items'][$entityName];
            }
        }

        return null;
    }

    /**
     * Get the entity class name for a given entity name.
     *
     * @param string $entityName The entity name (e.g., 'page')
     *
     * @return string|null The full entity class name or null if not found
     */
    public function getEntityClass(string $entityName): ?string
    {
        $config = $this->getEntityConfig($entityName);

        return $config['entity'] ?? null;
    }

    /**
     * Get default configuration for entity CRUD operations.
     *
     * @param string|null $key Optional specific configuration key
     *
     * @return mixed The configuration value(s)
     */
    public function getEntityDefaults(?string $key = null): mixed
    {
        if ($key === null) {
            return self::ENTITY_DEFAULTS;
        }

        return self::ENTITY_DEFAULTS[$key] ?? null;
    }

    /**
     * Get site-wide settings from the database.
     *
     * @return Settings The site settings
     */
    public function getSiteSettings(): Settings
    {
        return $this->settingsRepository->getSettings();
    }

    /**
     * Get a specific site setting value.
     *
     * @param string $key The setting key (e.g., 'siteName', 'seoDescription')
     *
     * @return mixed The setting value or null if not found
     */
    public function getSiteSetting(string $key): mixed
    {
        $settings = $this->getSiteSettings();
        $getter = 'get' . ucfirst($key);

        if (method_exists($settings, $getter)) {
            return $settings->$getter();
        }

        return null;
    }

    /**
     * Check if a user has access to a specific menu item.
     *
     * @param string $entityName The entity name
     * @param array<string> $userRoles The user's roles
     *
     * @return bool True if the user has access, false otherwise
     */
    public function hasAccess(string $entityName, array $userRoles): bool
    {
        $config = $this->getEntityConfig($entityName);

        if ($config === null) {
            return false;
        }

        $requiredRoles = $config['roles'] ?? [];

        // Check if user has any of the required roles
        foreach ($requiredRoles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get filtered menu items based on user roles.
     *
     * @param array<string> $userRoles The user's roles
     *
     * @return array<string, mixed> Filtered menu structure
     */
    public function getFilteredMenu(array $userRoles): array
    {
        $filteredMenu = [];

        foreach (self::ADMIN_MENU as $sectionKey => $section) {
            $filteredItems = [];

            foreach ($section['items'] as $itemKey => $item) {
                $requiredRoles = $item['roles'] ?? [];
                $hasAccess = false;

                foreach ($requiredRoles as $role) {
                    if (in_array($role, $userRoles, true)) {
                        $hasAccess = true;
                        break;
                    }
                }

                if ($hasAccess) {
                    $filteredItems[$itemKey] = $item;
                }
            }

            // Only include section if it has visible items
            if (!empty($filteredItems)) {
                $filteredMenu[$sectionKey] = [
                    'label' => $section['label'],
                    'icon' => $section['icon'],
                    'items' => $filteredItems,
                ];
            }
        }

        return $filteredMenu;
    }

    /**
     * Get breadcrumb trail for admin pages.
     *
     * @param string $entityName The current entity
     * @param string|null $action The current action (e.g., 'index', 'edit', 'new')
     * @param string|null $title Optional title for the current page
     *
     * @return array<array{label: string, url: string|null}> Breadcrumb items
     */
    public function getBreadcrumbs(string $entityName, ?string $action = null, ?string $title = null): array
    {
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => 'admin_dashboard'],
        ];

        $config = $this->getEntityConfig($entityName);

        if ($config !== null) {
            $breadcrumbs[] = [
                'label' => $config['label'],
                'url' => $action === 'index' ? null : $config['route'],
            ];

            if ($action !== null && $action !== 'index') {
                $actionLabel = match ($action) {
                    'new' => 'New',
                    'edit' => 'Edit',
                    'show' => 'View',
                    default => ucfirst($action),
                };

                $breadcrumbs[] = [
                    'label' => $title ?? $actionLabel,
                    'url' => null,
                ];
            }
        }

        return $breadcrumbs;
    }
}
