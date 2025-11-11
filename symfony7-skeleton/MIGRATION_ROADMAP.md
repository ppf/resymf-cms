# Symfony 7.1 Migration Roadmap

**Project**: ReSymf-CMS Legacy → Symfony 7.1.11 + PHP 8.3
**Branch**: `symfony7-migration`
**Created**: 2025-11-11
**Status**: Phase 1 - Foundation Setup ✅

---

## Migration Strategy

### Parallel Track Approach
- **New Symfony 7 skeleton** in `symfony7-skeleton/` directory
- **Legacy codebase** remains in parent directories (reference only)
- **Progressive migration** - port bundles incrementally
- **Test-first** - verification harness before each component

---

## Phase 1: Foundation Setup ✅ COMPLETE

### 1.1 Symfony 7 Skeleton ✅
- [x] Create `symfony7-migration` branch
- [x] Initialize Symfony 7.1.11 skeleton
- [x] Install webapp pack (includes all major bundles)
- [x] Configure directory structure

### 1.2 Core Bundles Installed ✅
- [x] `doctrine/orm` 3.5.6
- [x] `symfony/security-bundle` 7.1.11
- [x] `symfony/twig-bundle` 7.1.6
- [x] `symfony/form` 7.1.6
- [x] `symfony/maker-bundle` 1.64.0
- [x] `symfony/asset-mapper` 7.1.11
- [x] `phpunit/phpunit` 12.4.2
- [x] `doctrine/doctrine-fixtures-bundle` (via webapp pack)

### 1.3 Directory Structure ✅
```
symfony7-skeleton/
├── bin/                    # Console entry point
├── config/                 # All configuration (no more app/config/)
│   ├── packages/          # Bundle configs
│   └── routes/            # Routing configs
├── public/                # Web root (was web/)
│   └── index.php          # Front controller
├── src/
│   ├── CmsBundle/         # CMS bundle (legacy port)
│   │   ├── Controller/
│   │   ├── Entity/
│   │   ├── Repository/
│   │   ├── Form/
│   │   └── Service/
│   ├── ProjectManagerBundle/  # PM bundle (legacy port)
│   │   ├── Controller/
│   │   ├── Entity/
│   │   ├── Repository/
│   │   ├── Form/
│   │   └── Service/
│   ├── Controller/        # App-level controllers
│   ├── Entity/            # Shared entities
│   └── Repository/        # Shared repositories
├── templates/             # Twig templates
├── tests/                 # PHPUnit tests
├── migrations/            # Doctrine migrations
├── assets/                # Asset Mapper (was components/)
├── translations/          # i18n
└── .env                   # Environment config
```

### 1.4 Environment Configuration ✅
- [x] MySQL database URL configured
- [x] APP_ENV=dev, APP_SECRET generated
- [x] Messenger transport configured (doctrine)
- [x] Mailer DSN placeholder

---

## Phase 2: Database & Entity Migration (Next)

### 2.1 Database Schema Analysis
- [ ] Export legacy schema: `mysqldump --no-data resymf_legacy`
- [ ] Document all 17+ tables and relationships
- [ ] Identify schema differences vs Doctrine conventions
- [ ] Plan data transformation requirements

### 2.2 Entity Migration Strategy

#### Priority 1: CMS Core Entities
- [ ] `User` entity (authentication foundation)
  - Convert to Symfony UserInterface
  - Port password hashing (legacy → bcrypt/sodium)
  - Add PHP 8 attributes for validation
  - Create UserRepository

- [ ] `Role` entity (authorization)
  - Convert to Symfony RoleInterface
  - Update many-to-many with User

- [ ] `Settings` entity
  - Single-row configuration pattern
  - Form type for admin interface

#### Priority 2: CMS Content Entities
- [ ] `Page` entity
  - Slug generation service
  - Category many-to-many
  - Author foreign key to User
  - BasePage inheritance strategy

- [ ] `Category` entity
  - Simple label/description
  - Many-to-many with Page

- [ ] `Theme` entity
  - One-to-many with User

#### Priority 3: Project Manager Entities
- [ ] `Project` entity
  - Complex relations (sprints, contacts, terms, documents)
  - Aggregation fields (cached totals)

- [ ] `Sprint`, `Task`, `Issue` hierarchy
  - Many-to-one relationships
  - Status enums (use PHP 8.1 enums)

- [ ] `Contact`, `Company` entities
  - CRM-lite functionality

- [ ] `Document` entity
  - File upload handling
  - JSON path array → structured model

- [ ] `Term` entity
  - Payment terms/milestones

### 2.3 Custom Annotation → PHP 8 Attributes
**Legacy Pattern**:
```php
/**
 * @Form\Field(type="text", label="Title")
 * @Form\AutoInput(token="currentUserId")
 * @Table\Column(name="title", sortable=true)
 */
```

**Modern Pattern**:
```php
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Column(type: 'string', length: 255)]
#[Assert\NotBlank]
#[Assert\Length(max: 255)]
private string $title;
```

**Conversion Tasks**:
- [ ] Audit all `@Form`, `@Table` annotations in legacy
- [ ] Create attribute classes for custom metadata
- [ ] Implement Doctrine lifecycle events for auto-input (currentUserId, uniqueSlug)
- [ ] Build form type configurators based on attributes

### 2.4 Repository Migration
- [ ] Remove container-aware base repository
- [ ] Use Symfony ServiceEntityRepository
- [ ] Port custom query methods
- [ ] Add type hints (PHP 8.3 strict mode)

### 2.5 Doctrine Migrations
- [ ] Generate baseline migration from legacy schema
- [ ] Create migration for schema normalization
  - Add unique constraints (User.username, Page.slug)
  - Normalize Document.path (JSON array → relation table)
  - Add DB-level indexes
- [ ] Test migrations with legacy data dump

**Commands**:
```bash
# Generate migration from current entities
bin/console doctrine:migrations:diff

# Execute migration
bin/console doctrine:migrations:migrate

# Validate schema
bin/console doctrine:schema:validate
```

---

## Phase 3: Service Layer & Business Logic

### 3.1 Core Services

#### ObjectConfigurator → Modern Service
**Legacy**: Container-aware service with manual YAML parsing
```php
class ObjectConfigurator extends ContainerAware {
    public function __construct() {
        $this->config = Yaml::parse(file_get_contents(__DIR__.'/admin.yml'));
    }
}
```

**Modern**:
```php
#[Autowire]
class AdminConfigService {
    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly EntityManagerInterface $em
    ) {}
}
```

**Tasks**:
- [ ] Convert admin.yml to PHP config files
- [ ] Implement slug generation service
- [ ] Create form configurator service
- [ ] Build table/grid configurator service

#### FileManager → Flysystem
**Legacy**: Direct filesystem writes to `web/uploads`
```php
move_uploaded_file($_FILES['file']['tmp_name'], $targetPath);
```

**Modern**: Flysystem abstraction
```php
$this->storage->write($filename, $stream);
```

**Tasks**:
- [ ] Install `league/flysystem-bundle`
- [ ] Configure local adapter for uploads
- [ ] Migrate existing uploads to `public/uploads`
- [ ] Update Document entity path handling
- [ ] Add file validation (MIME types, size limits)
- [ ] Implement virus scanning (ClamAV optional)

### 3.2 Security Migration

#### Authentication
**Legacy**: `security.context` service
```php
$user = $this->get('security.context')->getToken()->getUser();
```

**Modern**: Security component
```php
public function __construct(private Security $security) {}
$user = $this->security->getUser();
```

**Tasks**:
- [ ] Configure security.yaml firewall
- [ ] Create UserProvider
- [ ] Implement custom authenticator if needed
- [ ] Port login/logout controllers
- [ ] Configure remember-me
- [ ] Add password reset flow

#### Authorization
- [ ] Map legacy roles to Symfony voter pattern
- [ ] Create access control rules in security.yaml
- [ ] Implement menu visibility based on roles
- [ ] Port ACL for admin entities

---

## Phase 4: Controller & Routing Migration

### 4.1 Admin CRUD Controllers

**Legacy Pattern**: Container-aware controllers
```php
class AdminController extends Controller {
    public function listAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        // ...
    }
}
```

**Modern Pattern**: Autowired controllers
```php
#[Route('/admin/{entity}', name: 'admin_crud')]
class AdminCrudController extends AbstractController {
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminConfigService $config
    ) {}
}
```

**Migration Tasks**:
- [ ] Port 12 admin CRUD flows (Page, Category, Theme, Project, Sprint, Task, Issue, Document, Term, Contact, User, Settings)
- [ ] Convert routes from annotations to attributes
- [ ] Remove `getRequest()` calls → inject Request
- [ ] Update form handling (createForm, handleRequest)
- [ ] Port flash messages
- [ ] Update redirects (redirectToRoute)

**Entity CRUD Priority**:
1. User, Role, Settings (auth foundation)
2. Page, Category, Theme (CMS core)
3. Project, Sprint, Task, Issue (PM core)
4. Contact, Company, Document, Term (CRM/support)

### 4.2 Frontend Routes
- [ ] Port CMS routing controller (`/{slug}`)
- [ ] Convert not-found template
- [ ] Implement custom page routes
- [ ] Dashboard route

### 4.3 File Upload Endpoint
- [ ] Port `/admin/upload-file` controller
- [ ] Update to use Flysystem
- [ ] Add CSRF protection
- [ ] Return JSON response (modernize client JS)

---

## Phase 5: Form Types & Validation

### 5.1 Form Type Migration
**Legacy**: Dynamic form building via annotations
**Modern**: Explicit FormType classes

**Tasks**:
- [ ] Create FormType for each entity (12 types minimum)
- [ ] Port field configurations from `@Form` annotations
- [ ] Add validation constraints
- [ ] Implement custom form type extensions for auto-input fields
- [ ] Handle multiselect/relation widgets

**Example**:
```php
class PageType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)]
            ])
            ->add('slug', TextType::class, ['attr' => ['readonly' => true]])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'multiple' => true,
                'choice_label' => 'name'
            ]);
    }
}
```

### 5.2 Validation Migration
- [ ] Port validation rules from annotations to attributes
- [ ] Add custom validators where needed
- [ ] Configure validation groups

---

## Phase 6: Templates & Asset Migration

### 6.1 Twig Template Migration
**Changes**:
- `app/Resources/views/` → `templates/`
- Update template paths in controllers
- Remove deprecated Twig features
- Port custom Twig extensions

**Templates to Migrate**:
- [ ] `adminmenu:list.html.twig` (admin grid)
- [ ] `adminmenu:create.html.twig` (admin form)
- [ ] `adminmenu:show.html.twig` (admin detail)
- [ ] `adminmenu:dashboard.html.twig` (dashboard)
- [ ] `cms:index.html.twig` (frontend page view)
- [ ] `cms:notfound.html.twig` (404 page)
- [ ] Base layout templates

### 6.2 Asset Migration (Assetic → Asset Mapper)
**Legacy**: Assetic + components/
```yaml
assetic:
    filters:
        cssrewrite: ~
    bundles: [ ReSymfCmsBundle ]
```

**Modern**: Asset Mapper (or Webpack Encore)
```bash
bin/console importmap:require jquery
bin/console importmap:require bootstrap
```

**Tasks**:
- [ ] Migrate static assets from `components/` → `assets/`
- [ ] Convert Assetic configs to Asset Mapper
- [ ] Update template asset references
- [ ] Configure importmap.php
- [ ] Optional: Switch to Webpack Encore for complex builds

---

## Phase 7: Console Commands

### 7.1 Command Migration
**Legacy**: Container-aware commands
```php
class CreateAdminCommand extends ContainerAwareCommand {
    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }
}
```

**Modern**: Autowired commands
```php
#[AsCommand(name: 'app:create-admin')]
class CreateAdminCommand extends Command {
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }
}
```

**Commands to Port**:
- [ ] `security:createadmin` → `app:create-admin`
- [ ] `security:createrole` → `app:create-role`
- [ ] `resymf:populate` → Doctrine fixtures

---

## Phase 8: Testing Harness (Critical)

### 8.1 PHPUnit Configuration
- [x] PHPUnit 12 installed
- [ ] Configure `phpunit.xml.dist`
- [ ] Set up test database (SQLite for speed)
- [ ] Create bootstrap file

### 8.2 Doctrine Fixtures
```bash
composer require --dev doctrine/doctrine-fixtures-bundle
```

**Fixtures to Create**:
- [ ] UserFixtures (admin + regular user)
- [ ] RoleFixtures (ROLE_ADMIN, ROLE_USER)
- [ ] SettingsFixtures (default site config)
- [ ] PageFixtures (sample pages)
- [ ] CategoryFixtures
- [ ] ProjectFixtures (with full hierarchy)

### 8.3 Functional Tests (Symfony Panther)
```bash
composer require --dev symfony/panther
```

**Test Scenarios** (from verification-plan.md):
- [ ] Login/logout flow
- [ ] Page CRUD + public view
- [ ] Category CRUD
- [ ] Settings update
- [ ] Project CRUD with relations
- [ ] File upload
- [ ] Custom page rendering
- [ ] 404 handling

### 8.4 Smoke Test Suite
**Minimum viable tests for CI**:
- [ ] Application boots (kernel test)
- [ ] Database connection works
- [ ] Admin login renders
- [ ] Home page responds

---

## Phase 9: CI/CD Setup

### 9.1 GitHub Actions
```yaml
# .github/workflows/symfony.yml
name: Symfony Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env: {MYSQL_ROOT_PASSWORD: root}
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: {php-version: '8.3'}
      - run: composer install
      - run: bin/console doctrine:database:create --env=test
      - run: bin/console doctrine:migrations:migrate --env=test -n
      - run: bin/phpunit
```

**Tasks**:
- [ ] Create GitHub Actions workflow
- [ ] Configure test database service
- [ ] Add cache for Composer dependencies
- [ ] Set up code coverage reporting
- [ ] Add PHP-CS-Fixer check
- [ ] Add PHPStan static analysis

---

## Phase 10: Production Readiness

### 10.1 Performance Optimization
- [ ] Configure OPcache
- [ ] Set up APCu for metadata caching
- [ ] Enable Symfony cache warmer
- [ ] Configure HTTP cache headers
- [ ] Implement lazy loading for relations
- [ ] Add database query optimization (indexes, eager loading)

### 10.2 Security Hardening
- [ ] Configure security headers
- [ ] Enable CSRF protection globally
- [ ] Set up rate limiting
- [ ] Configure secrets management
- [ ] Add security.txt
- [ ] Run security audit: `composer audit`

### 10.3 Deployment
- [ ] Document deployment procedure
- [ ] Create deployment script
- [ ] Configure production .env
- [ ] Set up database backup strategy
- [ ] Plan zero-downtime deployment
- [ ] Rollback procedure

---

## Success Criteria

### Phase Completion Checklist
Each phase is complete when:
- [ ] All tasks marked as done
- [ ] Tests passing for affected components
- [ ] Code reviewed (self-review minimum)
- [ ] Documentation updated
- [ ] No regressions in existing functionality

### Project Completion
- [ ] All 17 entities migrated and working
- [ ] All 12 admin CRUD flows functional
- [ ] Test coverage >80% for critical paths
- [ ] All console commands ported
- [ ] Frontend routing working (CMS pages)
- [ ] File uploads working
- [ ] Authentication/authorization working
- [ ] CI pipeline green
- [ ] Performance acceptable (no >50% slowdown)
- [ ] Legacy data migration tested
- [ ] Production deployment successful

---

## Timeline Estimate

| Phase | Duration | Dependencies |
|-------|----------|--------------|
| Phase 1: Foundation | ✅ 1 day | None |
| Phase 2: Database/Entities | 1-2 weeks | Phase 1 |
| Phase 3: Services | 1 week | Phase 2 |
| Phase 4: Controllers | 1-2 weeks | Phase 2, 3 |
| Phase 5: Forms | 1 week | Phase 2, 4 |
| Phase 6: Templates/Assets | 1 week | Phase 4, 5 |
| Phase 7: Commands | 2-3 days | Phase 2, 3 |
| Phase 8: Testing | 1-2 weeks | All phases |
| Phase 9: CI/CD | 2-3 days | Phase 8 |
| Phase 10: Production | 1 week | Phase 8, 9 |

**Total**: 8-12 weeks (2-3 months)

---

## Risk Mitigation

### High-Risk Areas
1. **Custom annotation system** - Complex port to attributes
   - Mitigation: Phase 2.3 dedicated to this, test coverage

2. **File upload migration** - Data integrity critical
   - Mitigation: Backup all uploads, incremental migration, rollback plan

3. **Slug uniqueness logic** - No DB constraint, app-level only
   - Mitigation: Add unique index in migration, implement validator

4. **Performance regression** - New ORM might be slower
   - Mitigation: Baseline metrics, query profiling, optimization phase

### Blockers & Dependencies
- [ ] Database access credentials
- [ ] Legacy data export
- [ ] Production hosting requirements
- [ ] SSL certificate for staging

---

## Next Steps (Immediate)

**Week 1 Tasks**:
1. [ ] Export legacy database schema
2. [ ] Start User entity migration
3. [ ] Configure security.yaml
4. [ ] Create first fixtures (User, Role)
5. [ ] Write first smoke test (app boots)

**Commands to Run**:
```bash
# Export legacy schema
mysqldump --no-data -u root -p resymf_legacy > legacy_schema.sql

# Start entity migration
bin/console make:entity User

# Create fixtures
bin/console make:fixtures UserFixtures

# Run tests
bin/phpunit
```

---

## References

- **Phase 0 Findings**: `docs/phase0-findings.md`
- **Verification Plan**: `docs/verification-plan.md`
- **Data Storage**: `docs/data-storage.md`
- **Symfony 7 Docs**: https://symfony.com/doc/7.1/
- **Doctrine 3 Docs**: https://www.doctrine-project.org/projects/doctrine-orm/en/3.5/
- **PHP 8.3 Docs**: https://www.php.net/releases/8.3/

---

**Last Updated**: 2025-11-11
**Branch**: `symfony7-migration`
**Status**: Phase 1 Complete ✅ → Phase 2 Ready to Start
