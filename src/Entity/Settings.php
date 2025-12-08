<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Settings Entity.
 *
 * Single-row configuration pattern for site-wide settings
 * Design Pattern: Single-row table (only one Settings record should exist)
 */
#[ORM\Entity(repositoryClass: SettingsRepository::class)]
#[ORM\Table(name: 'resymf_settings')]
#[ORM\HasLifecycleCallbacks]
class Settings
{
    /**
     * Fixed ID for singleton pattern
     * Only one Settings record should exist with ID=1.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Site name cannot be blank.')]
    #[Assert\Length(
        min: 1,
        max: 255,
        maxMessage: 'Site name cannot be longer than {{ limit }} characters.',
    )]
    private string $siteName = 'ReSymf CMS';

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Admin panel title cannot be blank.')]
    #[Assert\Length(max: 255)]
    private string $adminPanelTitle = 'ReSymf CMS Admin';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $seoDescription = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $seoKeywords = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    #[Assert\NotBlank]
    #[Assert\Locale]
    private string $defaultLocale = 'en';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $headlessMode = false;

    /**
     * Whether two-factor authentication is enforced for all users.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $enforce2fa = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * String representation for debugging.
     */
    public function __toString(): string
    {
        return $this->siteName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiteName(): string
    {
        return $this->siteName;
    }

    public function setSiteName(string $siteName): static
    {
        $this->siteName = $siteName;

        return $this;
    }

    public function getAdminPanelTitle(): string
    {
        return $this->adminPanelTitle;
    }

    public function setAdminPanelTitle(string $adminPanelTitle): static
    {
        $this->adminPanelTitle = $adminPanelTitle;

        return $this;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    public function setSeoDescription(?string $seoDescription): static
    {
        $this->seoDescription = $seoDescription;

        return $this;
    }

    public function getSeoKeywords(): ?string
    {
        return $this->seoKeywords;
    }

    public function setSeoKeywords(?string $seoKeywords): static
    {
        $this->seoKeywords = $seoKeywords;

        return $this;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function setDefaultLocale(string $defaultLocale): static
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    public function isHeadlessMode(): bool
    {
        return $this->headlessMode;
    }

    public function setHeadlessMode(bool $headlessMode): static
    {
        $this->headlessMode = $headlessMode;

        return $this;
    }

    public function isEnforce2fa(): bool
    {
        return $this->enforce2fa;
    }

    public function setEnforce2fa(bool $enforce2fa): static
    {
        $this->enforce2fa = $enforce2fa;

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
}
