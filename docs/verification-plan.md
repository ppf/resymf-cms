# Verification Harness Blueprint

This plan defines the automated and manual checks needed to keep CMS + Project Manager features working while upgrading to PHP 8.3/Symfony 7.x.

---

## 1. Goals
- Provide fast **smoke tests** that confirm the app boots, routes render, and major admin flows work (login, CRUD, uploads).
- Capture the **full feature matrix** in regression tests so each bundle (CMS, Project Manager) stays functional after refactors.
- Enable **data safety** checks (migrations, fixtures, uploads) and **UI regression** coverage for the admin panel.

---

## 2. Tooling Stack

| Layer | Tooling | Notes |
| --- | --- | --- |
| Unit / Integration | PHPUnit 10+ (with Symfony PHPUnit bridge) | Port existing tests, add service/controller tests with mocks. |
| Functional API/UI | Symfony Panther for browser-level tests (headless Chrome) or Pest + Panther; alternative: Cypress for full frontend coverage. |
| CLI / Workflows | Symfony Console commands tested via KernelTestCase; use MakerBundle to scaffold tests once on Flex. |
| Fixtures | DoctrineFixturesBundle + Foundry to seed users, roles, sample CMS data. |
| CI | GitHub Actions or GitLab CI running PHP 8.3 matrix (unit), Node 20 (frontend build), and Panther/Cypress suites. |

---

## 3. Test Scenarios

Each scenario below should be represented by at least one automated test (unit, functional, or UI). When possible, also create API-level assertions so regressions show up before hitting Panther/Cypress.

### Authentication & Security
- Login page renders and enforces CSRF; include assertion for translated error messages.
- Valid credentials redirect to `/admin/` and surface the admin menu.
- Invalid credentials show errors without leaking info or timing differences (test both wrong user and wrong password).
- Logout clears session and redirects to `/login`; asserting old session cookie is invalid.
- Remember-me cookie lifecycle (if still required) across PHP sessions and browsers; ensure cookie rotation on logout.
- Access-control smoke: hitting `/admin/*` while anonymous should 302 to `/login`; authenticated non-admin should receive 403 where configured.

### CMS Bundle
- **Page CRUD**: create/edit/delete page with categories and author auto-fill; slug uniqueness verified; page renders via public `/{slug}` route and returns 404 after deletion.
- **Category CRUD**: create/edit/delete; ensure pages reflect category changes.
- **Theme assignment**: assign theme to user and confirm persisted relation (profile edit and bulk admin edit).
- **Settings**: edit SEO fields and assert values reflect in Twig templates/meta tags (assert via HTTP response).
- **Uploads**: upload file through admin form; confirm file exists under `uploads/` and metadata saved to entity.
- **CMS routing**: visiting unknown slugs renders not-found template; known slug respects associated theme/layout data.

### Project Manager Bundle
- **Project CRUD** with contacts, terms, documents, sprints.
- **Sprint/Task hierarchy**: create sprint → add tasks → add issues → ensure relations display in admin UI.
- **Document attachment**: upload doc for project/task, ensure file accessible and recorded in DB.
- **Term scheduling**: create term with date picker and verify ordering.
- **Contact/Company linkage**: create company, attach contacts, ensure contact listing shows company info.
- **Burndown data**: if derived metrics exist in templates, add assertions for computed hour totals.

### Console Workflows
- `security:createadmin` creates user + role; migration to new command validated via unit tests.
- `security:createrole` persists roles with unique names.
- Data seeding commands replaced by Doctrine fixtures and covered via integration tests.

### Routing / Error States
- Not-found slug renders custom template.
- Unauthorized access redirects to login.
- Admin custom page (`custom_page` entries) renders configured Twig template.
- Legacy `/app.php` vs `/app_dev.php` fallbacks: ensure new front controller responds consistently (important when proxy/CDN caches old paths).

### Scenario Matrix

| Feature | Smoke (CI) | Regression Depth | Notes |
| --- | --- | --- | --- |
| Login/Logout | Panther test hitting `/login` + `/admin` | PHPUnit test for authenticator, RememberMe cookie assertions | Blocks every other test; keep extremely fast. |
| Page CRUD + Public View | Panther flow (create/edit/delete + slug visit) | Unit tests for `ObjectConfigurator` slug logic | Covers auto-input + template rendering. |
| Category CRUD | Panther flow | PHPUnit repository test verifying page/category joins | |
| Settings Update | Panther form submit | Functional test asserting service wiring translates settings to Twig globals | |
| Project CRUD | Panther flow covering contacts/terms/document uploads | Unit tests for Doctrine listeners/helpers once migrated | |
| Sprint/Task/Issue hierarchy | Panther multi-step scenario | Functional tests for API endpoints (once exposed) | |
| Upload API | HTTP test verifying JSON response + file existence | Unit test for storage service abstraction | Move uploads outside web root later. |
| Custom Page Rendering | HTTP test hitting `/admin/page/{slug}` | Twig snapshot test | Ensures `type: custom_page` configs remain intact. |

---

## 4. Test Data Strategy
- **Baseline fixtures (shared for unit + UI)**  
  - Admin user + bcrypt password hash.  
  - Two roles (`ROLE_ADMIN`, `ROLE_USER`).  
  - Pages: at least two published pages, one referencing themes/categories, one purposely sharing a slug to exercise uniqueness handling.  
  - Categories/Themes: couple of rows for reference data.  
  - Project hierarchy: project → sprint → task → issue, plus contacts, company, term, and document metadata (pointing to dummy files).  
  - Settings row with recognizable values (used by meta tag assertions).  
  - Uploaded assets: create small placeholder files (PNG/TXT) checked into `tests/Fixtures/files`.
- **Extended fixtures (nightly/regression)**  
  - Stress dataset with ~50 pages, 20 categories, 5 projects w/ tasks to mimic production volumes and catch pagination/ORM regressions.
- **Prod-like dataset**  
  - Export sanitized snapshot before the upgrade, store encrypted, replay in staging to validate migrations and data scripts.
- **CI considerations**  
  - Use SQLite for unit tests where possible; keep MySQL/PostgreSQL service for Panther flows that depend on DB-specific features (transactions, JSON columns).  
  - Reset DB between tests via `doctrine:database:drop --force && doctrine:database:create --env=test`.

---

## 5. Environments & Automation
- **Local**  
  - `make bootstrap` (composer install, yarn install, build assets).  
  - `make test` (PHPUnit with coverage).  
  - `make test-ui` (spawns Symfony CLI server + Panther headless Chrome).  
  - `.env.test` relies on SQLite; `.env.panter` (optional) points to MySQL for flows requiring DB-level locking.  
  - Provide helper script to generate fake uploads into `var/tests/uploads`.
- **CI (GitHub Actions example)**  
  ```yaml
  jobs:
    tests:
      runs-on: ubuntu-latest
      services:
        mysql:
          image: mysql:8.0
          env: { MYSQL_ROOT_PASSWORD: root }
      steps:
        - uses: actions/checkout@v4
        - uses: shivammathur/setup-php@v2
          with: { php-version: '8.3', extensions: mbstring, intl, pdo_mysql }
        - run: composer install --no-interaction --prefer-dist
        - run: npm install && npm run build
        - run: php bin/console doctrine:database:create --env=test
        - run: php bin/console doctrine:migrations:migrate --env=test -n
        - run: php bin/phpunit
        - run: symfony php vendor/bin/panther-bin chromium --no-sandbox &
        - run: php bin/phpunit --testsuite panther
        - run: vendor/bin/phpunit --coverage-xml build/coverage
        - uses: codecov/codecov-action@v4
  ```
- **Staging / Nightly**  
  - Restore sanitized DB backup.  
  - Run migrations + cache warmup.  
  - Execute smoke Panther suite plus API health checks (HTTP 200 on `/admin/`, `/login`, `/healthz`).  
  - Collect Lighthouse/axe accessibility metrics for admin UI weekly.  
  - Archive generated uploads to verify cleanup routines.

---

## 6. Manual QA Checklist
- Visual inspection of admin dashboard, menus, and each CRUD form after major UI refactors.
- File upload/download sanity checks (especially after moving storage locations).
- Accessibility spot checks for forms/table views once Twig templates are modernized.
- Performance regression tests (e.g., Doctrine query counts on `list` pages) after ORM upgrades.
- Verify localization/translation placeholders in forms and flash messages.
- Confirm custom admin menu entries (`type: custom_page`) still appear and respect role-based visibility.
- Validate cron/console jobs (admin creation, role creation, populate) in staging prior to releases.

---

## 7. Next Steps
1. Port the repository to Symfony Flex and set up the new test harness (PHPUnit + Panther scaffolding).
2. Implement fixtures covering every admin menu entry plus associated uploads.
3. Add the first smoke tests (login + page CRUD) to establish the pattern, then expand to Project Manager scenarios.
4. Integrate CI pipeline with coverage + artifact uploads; gate merges on smoke suite success.
5. Schedule quarterly audits of the test suite to add coverage for new features or regression bugs.

---

## 8. Implementation Roadmap

| Milestone | Deliverables | Owner | Target |
| --- | --- | --- | --- |
| M1 – Harness bootstrap | Symfony Flex migration, PHPUnit 10 setup, DoctrineFixtures bundle installed | Backend | Week 1 |
| M2 – Fixtures + smoke tests | Baseline fixtures, login + page CRUD Panther test, upload helper | Backend + QA | Week 2 |
| M3 – Project Manager coverage | Project CRUD + hierarchy tests (Panther + unit) | Backend | Week 3 |
| M4 – Console & routing tests | Kernel-level tests for commands, not-found routing assertions | Backend | Week 4 |
| M5 – CI hardening | GitHub Actions pipeline with cache, coverage, nightly extended fixtures | DevOps | Week 4 |
| M6 – Accessibility & performance checks | Lighthouse/axe scripts, Doctrine query budgets | QA | Week 5 |
