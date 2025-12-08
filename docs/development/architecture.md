# Architecture Overview

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Symfony 7.3, PHP 8.5 |
| ORM | Doctrine ORM 3 |
| Frontend | Vue 3, Tailwind CSS |
| Build | Vite |
| Database | MariaDB 11 / MySQL 8 |
| Server | Nginx, PHP-FPM |
| Container | Docker |

---

## Directory Structure

```
resymf-cms/
├── assets/                 # Frontend assets
│   ├── app.js             # Public site entry
│   ├── admin.js           # Admin panel entry
│   ├── cms.js             # CMS pages entry
│   ├── styles/            # CSS files
│   └── vue/               # Vue components
│       ├── components/    # Vue SFCs
│       ├── composables/   # Vue composables
│       └── *-app.js       # Vue island entry points
├── config/                # Symfony configuration
│   ├── packages/          # Bundle configs
│   └── routes/            # Route definitions
├── docker/                # Docker configs
│   └── nginx/             # Nginx config
├── docs/                  # Documentation
├── migrations/            # Doctrine migrations
├── public/                # Web root
│   └── build/             # Vite output
├── src/                   # PHP source
│   ├── Controller/        # Controllers
│   │   ├── Admin/         # Admin controllers
│   │   └── Api/           # API controllers
│   ├── Entity/            # Doctrine entities
│   ├── Form/              # Form types
│   ├── Repository/        # Repositories
│   ├── Security/          # Security classes
│   └── Twig/              # Twig extensions
├── templates/             # Twig templates
│   ├── admin/             # Admin templates
│   └── security/          # Auth templates
├── tests/                 # PHPUnit tests
├── docker-compose.yml     # Docker services
├── vite.config.mjs        # Vite configuration
└── Dockerfile             # Multi-stage build
```

---

## Frontend Architecture

### Entry Points

| Entry | Purpose | File |
|-------|---------|------|
| app | Public site base | `assets/app.js` |
| admin | Admin panel base | `assets/admin.js` |
| cms | CMS pages | `assets/cms.js` |
| Vue islands | Interactive components | `assets/vue/*-app.js` |

### Vue Islands

Vue components mount into specific DOM elements:

```html
<!-- Twig template -->
<div data-vue-island="category-form"
     data-api-url="{{ path('api_categories') }}"
     data-category-id="{{ category.id }}">
</div>

{{ vite_entry_script_tags('category-form-app') }}
```

The entry point finds and mounts the component:

```javascript
// assets/vue/category-form-app.js
const target = document.querySelector('[data-vue-island="category-form"]');
createApp(CategoryForm, {
  apiUrl: target.dataset.apiUrl,
  categoryId: target.dataset.categoryId
}).mount(target);
```

### Vite Integration

Twig helpers handle dev/prod modes:
- **Dev:** Loads from Vite dev server (HMR)
- **Prod:** Loads from `public/build/` manifest

```twig
{{ vite_entry_link_tags('admin') }}
{{ vite_entry_script_tags('admin') }}
```

---

## API Endpoints

JSON endpoints for Vue islands:

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/pages` | GET | List pages |
| `/api/pages/{id}` | GET | Get page |
| `/api/pages` | POST | Create page |
| `/api/pages/{id}` | PUT | Update page |
| `/api/categories` | GET | List categories |
| `/api/users` | GET | List users |

All API endpoints require `ROLE_USER` or higher.

---

## Admin Panel

### URL Structure
- `/admin/dashboard` - Dashboard
- `/admin/users` - User management
- `/admin/pages` - Page management
- `/admin/categories` - Category management
- `/admin/settings` - System settings

### Controller Pattern
Each admin feature follows CRUD + toggle pattern:
- `index()` - List view
- `new()` - Create form
- `show()` - Detail view
- `edit()` - Edit form
- `delete()` - Delete action
- `toggleActive()` - Toggle status

---

## Security

### Authentication
- Form login with CSRF protection
- Login throttling (5 attempts/minute)
- Remember me (1 week)
- Two-factor authentication (TOTP)

### Authorization
- `ROLE_USER` - Basic access
- `ROLE_ADMIN` - Admin panel access
- Route-level security with `#[IsGranted]`

### CSRF Protection
All destructive actions require CSRF tokens:
```php
if ($this->isCsrfTokenValid('delete' . $id, $token)) {
    // Process delete
}
```

---

## Database

### Naming Conventions
- Tables: `resymf_*` prefix (e.g., `resymf_users`)
- Columns: snake_case
- Entities: PascalCase

### Entities
- `User` - Authentication and profile
- `Page` - CMS content pages
- `Category` - Content categorization
- `Settings` - System configuration

### Migrations
```bash
# Generate migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate
```
