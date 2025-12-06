<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CronJobRepository;
use App\Validator\ValidConsoleCommand;
use App\Validator\ValidCronExpression;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CronJob Entity.
 *
 * Scheduled task configuration for console commands
 * Managed via admin panel and executed by Symfony Scheduler
 */
#[ORM\Entity(repositoryClass: CronJobRepository::class)]
#[ORM\Table(name: 'resymf_cron_jobs')]
#[ORM\UniqueConstraint(name: 'UNIQ_CRONJOB_NAME', columns: ['name'])]
#[ORM\Index(name: 'IDX_CRONJOB_ACTIVE', columns: ['is_active'])]
#[UniqueEntity(fields: ['name'], message: 'A cron job with this name already exists.')]
#[ORM\HasLifecycleCallbacks]
class CronJob
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RUNNING = 'running';
    public const STATUS_LOCKED = 'locked';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Job name cannot be blank.')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Name must be at least {{ limit }} characters long.',
        maxMessage: 'Name cannot be longer than {{ limit }} characters.',
    )]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Console command with arguments (e.g., "app:cleanup --days=30").
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Command cannot be blank.')]
    #[Assert\Length(max: 255)]
    #[ValidConsoleCommand]
    private string $command;

    /**
     * Cron expression (e.g., "0 * * * *" for hourly).
     */
    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: 'Cron expression cannot be blank.')]
    #[ValidCronExpression]
    private string $cronExpression = '* * * * *';

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastRunAt = null;

    /**
     * Last execution status: success, failed, running, locked.
     */
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $lastStatus = null;

    /**
     * Last execution output (truncated to 10KB).
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastOutput = null;

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

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): static
    {
        $this->command = $command;

        return $this;
    }

    public function getCronExpression(): string
    {
        return $this->cronExpression;
    }

    public function setCronExpression(string $cronExpression): static
    {
        $this->cronExpression = $cronExpression;

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

    public function getLastRunAt(): ?\DateTimeImmutable
    {
        return $this->lastRunAt;
    }

    public function setLastRunAt(?\DateTimeImmutable $lastRunAt): static
    {
        $this->lastRunAt = $lastRunAt;

        return $this;
    }

    public function getLastStatus(): ?string
    {
        return $this->lastStatus;
    }

    public function setLastStatus(?string $lastStatus): static
    {
        $this->lastStatus = $lastStatus;

        return $this;
    }

    public function getLastOutput(): ?string
    {
        return $this->lastOutput;
    }

    public function setLastOutput(?string $lastOutput): static
    {
        // Truncate to 10KB to prevent excessive storage
        if ($lastOutput !== null && mb_strlen($lastOutput) > 10240) {
            $lastOutput = mb_substr($lastOutput, 0, 10240) . "\n... [truncated]";
        }
        $this->lastOutput = $lastOutput;

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
     * Get the command name without arguments.
     */
    public function getCommandName(): string
    {
        $parts = explode(' ', $this->command, 2);

        return $parts[0];
    }

    /**
     * Check if job is currently running.
     */
    public function isRunning(): bool
    {
        return $this->lastStatus === self::STATUS_RUNNING;
    }

    /**
     * Check if last execution was successful.
     */
    public function wasSuccessful(): bool
    {
        return $this->lastStatus === self::STATUS_SUCCESS;
    }
}
