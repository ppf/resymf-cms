<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Category Entity.
 *
 * Content categorization for Pages
 * Simple label-based tagging system
 */
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'resymf_categories')]
#[ORM\UniqueConstraint(name: 'UNIQ_CATEGORY_NAME', columns: ['name'])]
#[UniqueEntity(fields: ['name'], message: 'This category name is already taken.')]
#[ORM\HasLifecycleCallbacks]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Category name cannot be blank.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Category name must be at least {{ limit }} characters long.',
        maxMessage: 'Category name cannot be longer than {{ limit }} characters.',
    )]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * URL-friendly slug for the category.
     */
    #[ORM\Column(type: Types::STRING, length: 128, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9-]+$/',
        message: 'Slug can only contain lowercase letters, numbers and hyphens.',
    )]
    private string $slug;

    /**
     * Display order (lower numbers appear first).
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $displayOrder = 0;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Pages in this category
     * Many-to-many relationship (inverse side).
     */
    #[ORM\ManyToMany(targetEntity: Page::class, mappedBy: 'categories')]
    private Collection $pages;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->pages = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, Page>
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function addPage(Page $page): static
    {
        if (!$this->pages->contains($page)) {
            $this->pages->add($page);
            $page->addCategory($this);
        }

        return $this;
    }

    public function removePage(Page $page): static
    {
        if ($this->pages->removeElement($page)) {
            $page->removeCategory($this);
        }

        return $this;
    }

    /**
     * Get count of pages in this category.
     */
    public function getPageCount(): int
    {
        return $this->pages->count();
    }
}
