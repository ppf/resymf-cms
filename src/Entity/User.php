<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User Entity.
 *
 * Migrated from legacy ReSymf\Bundle\CmsBundle\Entity\User
 * Modern Symfony 7 implementation with PHP 8.5 features
 *
 * Changes from legacy:
 * - Implements UserInterface instead of AdvancedUserInterface (removed in Symfony 6+)
 * - Uses PasswordAuthenticatedUserInterface for password handling
 * - Roles stored as JSON array instead of ManyToMany for simplicity (Symfony best practice)
 * - Salt removed (modern password hashers don't need it)
 * - PHP 8.5 typed properties, pipe operator, and constructor property promotion
 * - Doctrine attributes instead of annotations
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'resymf_users')]
#[ORM\UniqueConstraint(name: 'UNIQ_USERNAME', columns: ['username'])]
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL', columns: ['email'])]
#[UniqueEntity(fields: ['username'], message: 'This username is already taken.')]
#[UniqueEntity(fields: ['email'], message: 'This email address is already registered.')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 25, unique: true)]
    #[Assert\NotBlank(message: 'Username cannot be blank.')]
    #[Assert\Length(
        min: 3,
        max: 25,
        minMessage: 'Username must be at least {{ limit }} characters long.',
        maxMessage: 'Username cannot be longer than {{ limit }} characters.',
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_-]+$/',
        message: 'Username can only contain letters, numbers, underscores and hyphens.',
    )]
    private string $username;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Email cannot be blank.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\Length(max: 180)]
    private string $email;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $password;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Plain password (not persisted to database)
     * Used for form handling.
     */
    private ?string $plainPassword = null;

    /**
     * Pages authored by this user
     * Lazy loaded - not fetched unless explicitly needed.
     */
    #[ORM\OneToMany(targetEntity: Page::class, mappedBy: 'author')]
    private Collection $authoredPages;

    /**
     * TOTP secret for two-factor authentication.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $totpSecret = null;

    /**
     * Whether TOTP 2FA is enabled for this user.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isTotpEnabled = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->authoredPages = new ArrayCollection();
        $this->roles = ['ROLE_USER']; // Default role
    }

    /**
     * String representation for debugging.
     */
    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->username, $this->email);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Add a role to the user.
     */
    public function addRole(string $role): static
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $role): static
    {
        $key = array_search($role, $this->roles, true);
        if ($key !== false) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles); // Re-index array
        }

        return $this;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get plain password (for form handling).
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * Set plain password (for form handling)
     * Will be hashed and set as password by UserPasswordHasher.
     */
    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Clear sensitive data after authentication
        $this->plainPassword = null;
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

    /**
     * Check if account is enabled (for security).
     */
    public function isEnabled(): bool
    {
        return $this->isActive;
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
    public function getAuthoredPages(): Collection
    {
        return $this->authoredPages;
    }

    public function addAuthoredPage(Page $page): static
    {
        if (!$this->authoredPages->contains($page)) {
            $this->authoredPages->add($page);
            $page->setAuthor($this);
        }

        return $this;
    }

    public function removeAuthoredPage(Page $page): static
    {
        if ($this->authoredPages->removeElement($page)) {
            // set the owning side to null (unless already changed)
            if ($page->getAuthor() === $this) {
                $page->setAuthor(null);
            }
        }

        return $this;
    }

    // ========================================
    // Two-Factor Authentication (TwoFactorInterface)
    // ========================================

    /**
     * Check if TOTP authentication is enabled and configured.
     */
    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->isTotpEnabled && $this->totpSecret !== null;
    }

    /**
     * Get the username shown in authenticator apps.
     */
    public function getTotpAuthenticationUsername(): string
    {
        return $this->username;
    }

    /**
     * Get TOTP configuration for the authenticator.
     */
    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        if ($this->totpSecret === null) {
            return null;
        }

        return new TotpConfiguration(
            $this->totpSecret,
            TotpConfiguration::ALGORITHM_SHA1,
            30,  // period in seconds
            6,    // digits
        );
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret): static
    {
        $this->totpSecret = $totpSecret;

        return $this;
    }

    public function isTotpEnabled(): bool
    {
        return $this->isTotpEnabled;
    }

    public function setIsTotpEnabled(bool $isTotpEnabled): static
    {
        $this->isTotpEnabled = $isTotpEnabled;

        return $this;
    }
}
