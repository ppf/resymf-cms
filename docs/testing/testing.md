# Testing Guide

This guide covers testing practices for ReSymf-CMS using PHPUnit.

## Running Tests

### Docker (Recommended)
```bash
# All tests
docker-compose exec php bin/phpunit

# Specific test file
docker-compose exec php bin/phpunit tests/Unit/Entity/UserTest.php

# Specific test method
docker-compose exec php bin/phpunit --filter testConstructorSetsDefaults

# With coverage
docker-compose exec php bash -c "XDEBUG_MODE=coverage bin/phpunit --coverage-html var/coverage"
```

### Local
```bash
# All tests
bin/phpunit

# With coverage
XDEBUG_MODE=coverage bin/phpunit --coverage-html var/coverage
```

---

## Test Directory Structure

```
tests/
├── Unit/
│   ├── Entity/           # Entity unit tests
│   ├── Service/          # Service unit tests
│   └── Form/             # Form type tests
├── Functional/
│   └── Controller/       # Controller tests
└── bootstrap.php         # Test bootstrap
```

---

## Entity Tests

Test entity behavior in `tests/Unit/Entity/`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $category = new Category();

        $this->assertNull($category->getId());
        $this->assertTrue($category->getIsActive());
        $this->assertInstanceOf(\DateTimeImmutable::class, $category->getCreatedAt());
        $this->assertNull($category->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $category = new Category();
        $result = $category->setName('Test Category');

        $this->assertSame('Test Category', $category->getName());
        $this->assertSame($category, $result); // Fluent interface
    }

    public function testSetSlug(): void
    {
        $category = new Category();
        $category->setSlug('test-category');

        $this->assertSame('test-category', $category->getSlug());
    }

    public function testToggleActive(): void
    {
        $category = new Category();

        $this->assertTrue($category->getIsActive());

        $category->setIsActive(false);
        $this->assertFalse($category->getIsActive());

        $category->setIsActive(true);
        $this->assertTrue($category->getIsActive());
    }

    public function testToString(): void
    {
        $category = new Category();
        $category->setName('My Category');

        $this->assertSame('My Category', (string) $category);
    }
}
```

---

## Controller Tests

Test controllers in `tests/Functional/Controller/`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminDashboardControllerTest extends WebTestCase
{
    public function testDashboardRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/dashboard');

        $this->assertResponseRedirects('/login');
    }

    public function testDashboardAccessibleWhenLoggedIn(): void
    {
        $client = static::createClient();

        // Get admin user
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['username' => 'admin']);

        // Login
        $client->loginUser($adminUser);

        $client->request('GET', '/admin/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Dashboard');
    }
}
```

---

## Repository Tests

Test repositories with database:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategoryRepositoryTest extends KernelTestCase
{
    private CategoryRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(CategoryRepository::class);
    }

    public function testFindActive(): void
    {
        $categories = $this->repository->findActive();

        foreach ($categories as $category) {
            $this->assertTrue($category->getIsActive());
        }
    }
}
```

---

## Mocking

Use PHPUnit mocks for isolated unit tests:

```php
public function testServiceWithMockedDependency(): void
{
    $repository = $this->createMock(CategoryRepository::class);
    $repository->expects($this->once())
        ->method('findAll')
        ->willReturn([new Category()]);

    $service = new CategoryService($repository);
    $result = $service->getAllCategories();

    $this->assertCount(1, $result);
}
```

---

## Test Database

Configure a test database in `.env.test`:

```dotenv
DATABASE_URL="mysql://root:password@127.0.0.1:3306/resymf_test?serverVersion=8.0"
```

Setup test database:
```bash
# Create test database
php bin/console doctrine:database:create --env=test

# Run migrations
php bin/console doctrine:migrations:migrate --env=test --no-interaction

# Load fixtures
php bin/console doctrine:fixtures:load --env=test --no-interaction
```

---

## Code Quality

### PHPStan
```bash
docker-compose exec php vendor/bin/phpstan analyse
```

### PHP-CS-Fixer
```bash
# Check style
docker-compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix style
docker-compose exec php vendor/bin/php-cs-fixer fix
```

---

## CI Integration

Tests run automatically in GitHub Actions (`.github/workflows/symfony-ci.yml`):

1. **code-quality** - PHPStan, PHP-CS-Fixer
2. **phpunit-tests** - All PHPUnit tests
3. **security-audit** - Composer audit
4. **doctrine-validation** - Schema validation
5. **lint** - PHP, Twig, YAML linting

---

## Checklist

When writing tests:

1. [ ] Test entity constructors and defaults
2. [ ] Test getters/setters with fluent interface
3. [ ] Test relationships (add/remove)
4. [ ] Test controller routes require auth
5. [ ] Test form validation
6. [ ] Run full test suite before commit
