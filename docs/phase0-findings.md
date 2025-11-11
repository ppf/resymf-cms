# Phase 0 Findings

## Target Runtime
- **Goal**: Migrate to PHP 8.3+ and the latest stable Symfony release (currently 7.1.x; Symfony 6.4 LTS may be a practical intermediate).
- **Current**: Symfony Standard Edition 2.4.*, PHP ≥ 5.3.3 with Doctrine 2.4, Assetic, SwiftMailer, Sensio Distribution/Generator, jquery components, and legacy directory layout (`app/`, `web/`).

## Current Stack Snapshot
- Composer constraints (`composer.json`) pin every dependency to Symfony 2-era packages and service IDs such as `security.context`, `request`, `form.csrf_provider`, `annotation_reader`.
- Kernel/bootstrap still use `AppKernel`, `bootstrap.php.cache`, and `web/app.php` with `loadClassCache()`.
- Asset pipeline relies on Assetic plus static JS/CSS stored under `components/`; uploads are written directly to `web/uploads/`.
- Authentication, console commands, routing, and admin services depend on APIs removed after Symfony 3 (container-aware controllers/commands, old firewall syntax, manual request fetching).

## Admin Feature Matrix
| Menu Item / Area | Entities / Scope | Routes | Templates | Key Services / Dependencies | Notes |
| --- | --- | --- | --- | --- | --- |
| `page` | `ReSymf\Bundle\CmsBundle\Entity\Page` (+ `BasePage`) | `/admin/page/{list,create,edit/{id},delete/{id}}` via `object_*` routes | `adminmenu:list.html.twig`, `adminmenu:create.html.twig`, `adminmenu:show.html.twig` | `AdminConfigurator`, `ObjectConfigurator`, `ObjectMapper`, Doctrine, custom `@Form`/`@Table` annotations | Multiselect categories, slug auto-fill, author auto-fill, HTML editor fields. |
| `category` | `ReSymf\Bundle\CmsBundle\Entity\Category` | Same CRUD routes as above | Same | Same | Shares slug namespace if `slug` configured in `admin.yml`. |
| `theme` (hidden) | `ReSymf\Bundle\CmsBundle\Entity\Theme` | `/admin/theme/...` | Same | Same | Hidden by default; used for assigning UI themes to users. |
| `project` | `ReSymf\Bundle\ProjectManagerBundle\Entity\Project` | `/admin/project/...` | Same | Same + Project Manager bundle relations (sprints, contacts, docs, terms) | Rich relations require multiselects and annotation-driven displays. |
| `sprint` (hidden) | `ReSymf\Bundle\ProjectManagerBundle\Entity\Sprint` | `/admin/sprint/...` | Same | Same | Hidden entry; ties into tasks. |
| `task` | `ReSymf\Bundle\ProjectManagerBundle\Entity\Task` | `/admin/task/...` | Same | Same | Depends on sprint/documents/issues relations; uses annotation metadata for relation handling. |
| `issue` (hidden) | `ReSymf\Bundle\ProjectManagerBundle\Entity\Issue` | `/admin/issue/...` | Same | Same | Hidden menu item; associated with tasks. |
| `document` (hidden) | `ReSymf\Bundle\ProjectManagerBundle\Entity\Document` | `/admin/document/...` | Same | Same + `FileManagerController` | Uses file upload field storing JSON metadata referencing `/web/uploads`. |
| `term` (hidden) | `ReSymf\Bundle\ProjectManagerBundle\Entity\Term` | `/admin/term/...` | Same | Same | Tagging/terms for projects. |
| `contact` | `ReSymf\Bundle\ProjectManagerBundle\Entity\Contact` | `/admin/contact/...` | Same | Same | Provides CRM-lite contact management (no dedicated CRM bundle exists). |
| `user` | `ReSymf\Bundle\CmsBundle\Entity\User` | `/admin/user/...` and `/admin/profile` (`resymf_admin_profile_show`) | `adminmenu:create.html.twig` (profile & CRUD) | Same services + legacy `security.context` for current user | Profile edit relies on deprecated service IDs and manual relation handling. |
| `settings` | `ReSymf\Bundle\CmsBundle\Entity\Settings` | `/admin/settings` (`resymf_admin_settings`) | `adminmenu:create.html.twig` | `AdminConfigurator`, annotation reader, Doctrine | Only the first row is edited/created; form fields defined via `@Form`. |
| `dashboard` | N/A (config-driven metrics) | `/admin/` (`resymf_admin_dashboard`) | `adminmenu:dashboard.html.twig` | `AdminConfigurator` | Renders YAML-defined menu & site metadata. |
| `custom page` (`my_slug`) | Custom Twig page | `/admin/page/{slug}` (`resymf_admin_custom_page`) | `ReSymfCmsBundle:Default:form.html.twig` | Twig only | Configured via `type: custom_page` entries in `admin.yml`. |
| `upload file` | File manager | `/admin/upload-file` (`resymf_admin_upload`) | AJAX endpoint returning JSON | `FileManagerController`, PHP `move_uploaded_file` | Saves to `<project>/web/uploads`, assumes Referer header for redirect/client handling. |
| Catch-all CMS route | Frontend page view | `/{slug}` handled by `CmsRoutingController::indexAction` | `cms:index.html.twig`/`cms:notfound.html.twig` | Doctrine, Twig | Any slug not matched earlier renders a `Page` entity by slug or shows not-found view. |

## Runtime Assumptions & Risks
- **Slug uniqueness**: `ObjectConfigurator::generateUniqueSlug()` inspects all admin-configured classes sharing a base slug and appends `uniqid()` when collisions exist. There is no DB-level unique constraint, so application logic must replicate this.
- **Auto-input fields**: Custom `@Form(autoInput=…)` tokens set values like current user, timestamps, and slugs (e.g., `currentUserId`, `currentDateTime`, `uniqueSlug`). These are executed in PHP via reflection before persistence—needs equivalent hooks in the upgraded system.
- **Relation widgets**: Multiselect/relations are assembled at runtime using Doctrine metadata and `@Form` options such as `relationType`, `class`, `displayField`, `targetEntityField`. Replacement UI must continue to honor these dynamic definitions or the admin UX must be redesigned.
- **Service usage**: Controllers and services pull `request`, `security.context`, `annotation_reader`, `form.csrf_provider` directly from the container. All of these IDs are gone after Symfony 3, so every dependent class must be rewritten for autowiring and modern interfaces.
- **File storage**: Uploads are placed under `web/uploads` without validation, naming collisions are handled by random prefixes, and filenames are stored as JSON strings. Migrating to Symfony 4+ will require moving to `public/` and adopting a safer storage abstraction (Flysystem/Symfony Filesystem).
- **Config caching**: Admin/site configuration is read once from YAML (`admin.yml`) in the `AdminConfigurator` constructor. Any future customizations may expect runtime reloads; we may need configuration objects or database-backed configs for modern cache layers.

## Data & Infrastructure Notes
- **Entities**: CMS (`Page`, `Category`, `Theme`, `Settings`, `User`, `Role`) plus Project Manager (`Project`, `Sprint`, `Task`, `Issue`, `Term`, `Contact`, `Document`) all use Doctrine annotations supplemented by custom metadata classes in `src/ReSymf/Bundle/CmsBundle/Annotation`.
- **Console commands**: `security:createadmin`, `security:createrole`, `resymf:populate` are container-aware commands that rely on legacy services and Doctrine access. They need to be ported to Symfony Console with dependency injection.
- **Assets**: Assetic configuration in `app/config/config.yml` and static files in `components/` must be replaced with Webpack Encore/Vite. Front controllers (`web/app.php`, `web/app_dev.php`) must move to `public/`.
- **Tests/CI**: PHPUnit config lives at `app/phpunit.xml.dist`, but only a couple of skeleton tests exist (many are commented out). `.travis.yml` still targets PHP 5.3. Continuous integration/testing must be rebuilt for PHP 8.3.

## Next Documentation Steps
1. Record the current database schema (Doctrine mapping dump + actual DB export) and describe upload/storage paths to inform migration scripts.
2. Define the automated verification plan (smoke/UI tests covering login, CRUD for each entity, uploads, slug routing, and project workflows) so we can guard the upgrade effort.
