<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ThemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Theme Entity
 *
 * UI Theme configuration for users
 * Allows customization of admin interface appearance
 */
#[ORM\Entity(repositoryClass: ThemeRepository::class)]
#[ORM\Table(name: 'resymf_themes')]
#[ORM\UniqueConstraint(name: 'UNIQ_THEME_NAME', columns: ['name'])]
#[UniqueEntity(fields: ['name'], message: 'This theme name is already taken.')]
#[ORM\HasLifecycleCallbacks]
class Theme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Theme name cannot be blank.')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Theme name must be at least {{ limit }} characters long.',
        maxMessage: 'Theme name cannot be longer than {{ limit }} characters.'
    )]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $description = null;

    /**
     * Primary color in hex format (e.g., #3498db)
     */
    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'Primary color must be a valid hex color (e.g., #3498db).'
    )]
    private ?string $primaryColor = null;

    /**
     * Secondary color in hex format (e.g., #2ecc71)
     */
    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'Secondary color must be a valid hex color (e.g., #2ecc71).'
    )]
    private ?string $secondaryColor = null;

    /**
     * CSS stylesheet path (relative to public/themes/)
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $stylesheet = null;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(name: 'is_default', type: Types::BOOLEAN)]
    private bool $isDefault = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Users using this theme
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'theme')]
    private Collection $users;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->users = new ArrayCollection();
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

    public function getPrimaryColor(): ?string
    {
        return $this->primaryColor;
    }

    public function setPrimaryColor(?string $primaryColor): static
    {
        $this->primaryColor = $primaryColor;

        return $this;
    }

    public function getSecondaryColor(): ?string
    {
        return $this->secondaryColor;
    }

    public function setSecondaryColor(?string $secondaryColor): static
    {
        $this->secondaryColor = $secondaryColor;

        return $this;
    }

    public function getStylesheet(): ?string
    {
        return $this->stylesheet;
    }

    public function setStylesheet(?string $stylesheet): static
    {
        $this->stylesheet = $stylesheet;

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

    public function getIsDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

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
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setTheme($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getTheme() === $this) {
                $user->setTheme(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
