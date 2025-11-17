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
 * Migrated from legacy ReSymf\Bundle\CmsBundle\Entity\Settings
 * Modern Symfony 7 implementation with PHP 8.3 features
 *
 * Design Pattern: Single-row table (only one Settings record should exist)
 * - Use ID=1 as singleton
 * - Repository ensures only one row exists
 * - Provides global site configuration
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

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $siteTagline = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $seoDescription = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $seoKeywords = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    private ?string $adminEmail = null;

    #[ORM\Column(name: 'google_analytics_key', type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $googleAnalyticsId = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $googleTagManagerKey = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $maintenanceMode = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $maintenanceMessage = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    #[Assert\NotBlank]
    #[Assert\Locale]
    private string $defaultLocale = 'en';

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    #[Assert\Timezone]
    private string $timezone = 'UTC';

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Range(min: 1, max: 100)]
    private int $itemsPerPage = 10;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $registrationEnabled = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $emailVerificationRequired = false;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $facebookUrl = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $twitterUrl = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $linkedinUrl = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $githubUrl = null;

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

    public function getSiteTagline(): ?string
    {
        return $this->siteTagline;
    }

    public function setSiteTagline(?string $siteTagline): static
    {
        $this->siteTagline = $siteTagline;

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

    public function getAdminEmail(): ?string
    {
        return $this->adminEmail;
    }

    public function setAdminEmail(?string $adminEmail): static
    {
        $this->adminEmail = $adminEmail;

        return $this;
    }

    public function getGoogleAnalyticsId(): ?string
    {
        return $this->googleAnalyticsId;
    }

    public function setGoogleAnalyticsId(?string $googleAnalyticsId): static
    {
        $this->googleAnalyticsId = $googleAnalyticsId;

        return $this;
    }

    public function getGoogleTagManagerKey(): ?string
    {
        return $this->googleTagManagerKey;
    }

    public function setGoogleTagManagerKey(?string $googleTagManagerKey): static
    {
        $this->googleTagManagerKey = $googleTagManagerKey;

        return $this;
    }

    public function isMaintenanceMode(): bool
    {
        return $this->maintenanceMode;
    }

    public function setMaintenanceMode(bool $maintenanceMode): static
    {
        $this->maintenanceMode = $maintenanceMode;

        return $this;
    }

    public function getMaintenanceMessage(): ?string
    {
        return $this->maintenanceMessage;
    }

    public function setMaintenanceMessage(?string $maintenanceMessage): static
    {
        $this->maintenanceMessage = $maintenanceMessage;

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

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function setItemsPerPage(int $itemsPerPage): static
    {
        $this->itemsPerPage = $itemsPerPage;

        return $this;
    }

    public function isRegistrationEnabled(): bool
    {
        return $this->registrationEnabled;
    }

    public function setRegistrationEnabled(bool $registrationEnabled): static
    {
        $this->registrationEnabled = $registrationEnabled;

        return $this;
    }

    public function isEmailVerificationRequired(): bool
    {
        return $this->emailVerificationRequired;
    }

    public function setEmailVerificationRequired(bool $emailVerificationRequired): static
    {
        $this->emailVerificationRequired = $emailVerificationRequired;

        return $this;
    }

    public function getFacebookUrl(): ?string
    {
        return $this->facebookUrl;
    }

    public function setFacebookUrl(?string $facebookUrl): static
    {
        $this->facebookUrl = $facebookUrl;

        return $this;
    }

    public function getTwitterUrl(): ?string
    {
        return $this->twitterUrl;
    }

    public function setTwitterUrl(?string $twitterUrl): static
    {
        $this->twitterUrl = $twitterUrl;

        return $this;
    }

    public function getLinkedinUrl(): ?string
    {
        return $this->linkedinUrl;
    }

    public function setLinkedinUrl(?string $linkedinUrl): static
    {
        $this->linkedinUrl = $linkedinUrl;

        return $this;
    }

    public function getGithubUrl(): ?string
    {
        return $this->githubUrl;
    }

    public function setGithubUrl(?string $githubUrl): static
    {
        $this->githubUrl = $githubUrl;

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
