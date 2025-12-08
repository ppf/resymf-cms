# Creating Entities

This guide explains how to create new Doctrine entities in ReSymf-CMS.

## Entity Pattern

All entities follow this pattern (from `src/Entity/Category.php`):

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\YourEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: YourEntityRepository::class)]
#[ORM\Table(name: 'resymf_your_entities')]
#[ORM\UniqueConstraint(name: 'UNIQ_ENTITY_NAME', columns: ['name'])]
#[UniqueEntity(fields: ['name'], message: 'This name is already taken.')]
#[ORM\HasLifecycleCallbacks]
class YourEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    #[Assert\Length(min: 2, max: 100)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and setters with fluent interface...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    // ... more getters/setters
}
```

---

## Conventions

### Table Naming
- Prefix: `resymf_`
- Plural: `resymf_categories`, `resymf_users`
- Snake case: `resymf_cron_jobs`

### Column Types
| PHP Type | Doctrine Type |
|----------|---------------|
| `int` | `Types::INTEGER` |
| `string` | `Types::STRING` |
| `?string` | `Types::TEXT` (nullable) |
| `bool` | `Types::BOOLEAN` |
| `\DateTimeImmutable` | `Types::DATETIME_IMMUTABLE` |

### Boolean Fields
Use `is` prefix: `isActive`, `isPublished`, `isEnabled`

### Timestamps
Always include:
- `createdAt` - Set in constructor, immutable
- `updatedAt` - Set via `#[ORM\PreUpdate]`, nullable

---

## Validation

Use Symfony Validator constraints:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\NotBlank(message: 'Field cannot be blank.')]
#[Assert\Length(min: 2, max: 100)]
private string $name;

#[Assert\Email(message: 'Invalid email.')]
private string $email;

#[Assert\Regex(
    pattern: '/^[a-z0-9-]+$/',
    message: 'Only lowercase letters, numbers, and hyphens.'
)]
private string $slug;

#[Assert\Range(min: 0, max: 1000)]
private int $displayOrder = 0;
```

### Unique Constraints

```php
#[ORM\UniqueConstraint(name: 'UNIQ_NAME', columns: ['name'])]
#[UniqueEntity(fields: ['name'], message: 'Already taken.')]
class YourEntity
```

---

## Relationships

### One-to-Many

```php
// Parent side
#[ORM\OneToMany(targetEntity: Child::class, mappedBy: 'parent')]
private Collection $children;

public function __construct()
{
    $this->children = new ArrayCollection();
}

public function addChild(Child $child): static
{
    if (!$this->children->contains($child)) {
        $this->children->add($child);
        $child->setParent($this);
    }
    return $this;
}
```

```php
// Child side
#[ORM\ManyToOne(targetEntity: Parent::class, inversedBy: 'children')]
#[ORM\JoinColumn(nullable: false)]
private Parent $parent;
```

### Many-to-Many

```php
// Owning side
#[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'posts')]
#[ORM\JoinTable(name: 'resymf_post_tags')]
private Collection $tags;
```

```php
// Inverse side
#[ORM\ManyToMany(targetEntity: Post::class, mappedBy: 'tags')]
private Collection $posts;
```

---

## Repository

Create a repository class:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\YourEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<YourEntity>
 */
class YourEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, YourEntity::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $query): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
}
```

---

## Migration

After creating/modifying an entity:

```bash
# Generate migration
php bin/console make:migration

# Review the generated file in migrations/

# Run migration
php bin/console doctrine:migrations:migrate
```

### Docker
```bash
docker-compose exec php php bin/console make:migration
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

---

## Checklist

When creating a new entity:

1. [ ] Create entity in `src/Entity/`
2. [ ] Use `resymf_` table prefix
3. [ ] Add timestamps (`createdAt`, `updatedAt`)
4. [ ] Add validation constraints
5. [ ] Create repository in `src/Repository/`
6. [ ] Generate and run migration
7. [ ] Write unit tests in `tests/Unit/Entity/`
