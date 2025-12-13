<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AlertEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AlertEvent Entity.
 *
 * Represents a triggered alert event, used for tracking and deduplication.
 */
#[ORM\Entity(repositoryClass: AlertEventRepository::class)]
#[ORM\Table(name: 'resymf_alert_events')]
#[ORM\Index(name: 'IDX_ALERT_EVENT_RULE', columns: ['rule_name'])]
#[ORM\Index(name: 'IDX_ALERT_EVENT_TRIGGERED', columns: ['triggered_at'])]
#[ORM\Index(name: 'IDX_ALERT_EVENT_DEDUPE', columns: ['dedupe_hash'])]
class AlertEvent
{
    public const STATUS_TRIGGERED = 'triggered';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_SUPPRESSED = 'suppressed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Name of the rule that triggered this event.
     */
    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    private string $ruleName;

    /**
     * Priority level of the alert.
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $priority = AlertRule::PRIORITY_MEDIUM;

    /**
     * Current status of the alert event.
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\Choice(choices: [self::STATUS_TRIGGERED, self::STATUS_ACKNOWLEDGED, self::STATUS_RESOLVED, self::STATUS_SUPPRESSED])]
    private string $status = self::STATUS_TRIGGERED;

    /**
     * Hash for deduplication purposes.
     */
    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $dedupeHash = null;

    /**
     * The message or data that triggered the alert.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $triggerMessage = null;

    /**
     * Additional context data (JSON).
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $context = [];

    /**
     * Aggregated value if threshold-based.
     */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $aggregatedValue = null;

    /**
     * Threshold value that was exceeded.
     */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $thresholdValue = null;

    /**
     * Number of events that contributed to this alert.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $eventCount = 1;

    /**
     * When the alert was triggered.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $triggeredAt;

    /**
     * When the cooldown expires.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $cooldownUntil = null;

    /**
     * When the alert was acknowledged.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $acknowledgedAt = null;

    /**
     * When the alert was resolved.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    /**
     * User who acknowledged the alert (if any).
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $acknowledgedBy = null;

    public function __construct()
    {
        $this->triggeredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRuleName(): string
    {
        return $this->ruleName;
    }

    public function setRuleName(string $ruleName): static
    {
        $this->ruleName = $ruleName;

        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDedupeHash(): ?string
    {
        return $this->dedupeHash;
    }

    public function setDedupeHash(?string $dedupeHash): static
    {
        $this->dedupeHash = $dedupeHash;

        return $this;
    }

    public function getTriggerMessage(): ?string
    {
        return $this->triggerMessage;
    }

    public function setTriggerMessage(?string $triggerMessage): static
    {
        $this->triggerMessage = $triggerMessage;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getAggregatedValue(): ?float
    {
        return $this->aggregatedValue;
    }

    public function setAggregatedValue(?float $aggregatedValue): static
    {
        $this->aggregatedValue = $aggregatedValue;

        return $this;
    }

    public function getThresholdValue(): ?float
    {
        return $this->thresholdValue;
    }

    public function setThresholdValue(?float $thresholdValue): static
    {
        $this->thresholdValue = $thresholdValue;

        return $this;
    }

    public function getEventCount(): int
    {
        return $this->eventCount;
    }

    public function setEventCount(int $eventCount): static
    {
        $this->eventCount = $eventCount;

        return $this;
    }

    public function getTriggeredAt(): \DateTimeImmutable
    {
        return $this->triggeredAt;
    }

    public function setTriggeredAt(\DateTimeImmutable $triggeredAt): static
    {
        $this->triggeredAt = $triggeredAt;

        return $this;
    }

    public function getCooldownUntil(): ?\DateTimeImmutable
    {
        return $this->cooldownUntil;
    }

    public function setCooldownUntil(?\DateTimeImmutable $cooldownUntil): static
    {
        $this->cooldownUntil = $cooldownUntil;

        return $this;
    }

    public function getAcknowledgedAt(): ?\DateTimeImmutable
    {
        return $this->acknowledgedAt;
    }

    public function setAcknowledgedAt(?\DateTimeImmutable $acknowledgedAt): static
    {
        $this->acknowledgedAt = $acknowledgedAt;

        return $this;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }

    public function getAcknowledgedBy(): ?string
    {
        return $this->acknowledgedBy;
    }

    public function setAcknowledgedBy(?string $acknowledgedBy): static
    {
        $this->acknowledgedBy = $acknowledgedBy;

        return $this;
    }

    /**
     * Check if the alert is currently in cooldown.
     */
    public function isInCooldown(): bool
    {
        if ($this->cooldownUntil === null) {
            return false;
        }

        return $this->cooldownUntil > new \DateTimeImmutable();
    }

    /**
     * Acknowledge this alert.
     */
    public function acknowledge(?string $acknowledgedBy = null): static
    {
        $this->status = self::STATUS_ACKNOWLEDGED;
        $this->acknowledgedAt = new \DateTimeImmutable();
        $this->acknowledgedBy = $acknowledgedBy;

        return $this;
    }

    /**
     * Resolve this alert.
     */
    public function resolve(): static
    {
        $this->status = self::STATUS_RESOLVED;
        $this->resolvedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Suppress this alert (e.g., due to cooldown).
     */
    public function suppress(): static
    {
        $this->status = self::STATUS_SUPPRESSED;

        return $this;
    }

    /**
     * Generate a deduplication hash from rule name and dedupe key value.
     */
    public static function generateDedupeHash(string $ruleName, ?string $dedupeValue): string
    {
        return hash('sha256', $ruleName . ':' . ($dedupeValue ?? ''));
    }
}
