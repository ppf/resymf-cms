<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AlertRuleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AlertRule Entity.
 *
 * Represents an alert rule definition that can be stored in the database.
 * Rules can also be loaded from YAML configuration files.
 */
#[ORM\Entity(repositoryClass: AlertRuleRepository::class)]
#[ORM\Table(name: 'resymf_alert_rules')]
#[ORM\UniqueConstraint(name: 'UNIQ_ALERT_RULE_NAME', columns: ['name'])]
#[UniqueEntity(fields: ['name'], message: 'This alert rule name is already taken.')]
#[ORM\HasLifecycleCallbacks]
class AlertRule
{
    public const TYPE_PATTERN = 'pattern';
    public const TYPE_THRESHOLD = 'threshold';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public const OPERATOR_GT = 'gt';
    public const OPERATOR_GTE = 'gte';
    public const OPERATOR_LT = 'lt';
    public const OPERATOR_LTE = 'lte';
    public const OPERATOR_EQ = 'eq';

    public const AGGREGATION_COUNT = 'count';
    public const AGGREGATION_SUM = 'sum';
    public const AGGREGATION_AVG = 'avg';
    public const AGGREGATION_MIN = 'min';
    public const AGGREGATION_MAX = 'max';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Alert rule name cannot be blank.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Alert rule name must be at least {{ limit }} characters long.',
        maxMessage: 'Alert rule name cannot be longer than {{ limit }} characters.',
    )]
    #[Assert\Regex(
        pattern: '/^[a-z0-9_]+$/',
        message: 'Name can only contain lowercase letters, numbers and underscores.',
    )]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'is_enabled', type: Types::BOOLEAN)]
    private bool $isEnabled = true;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\Choice(choices: [self::TYPE_PATTERN, self::TYPE_THRESHOLD])]
    private string $type = self::TYPE_PATTERN;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\Choice(choices: [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH, self::PRIORITY_CRITICAL])]
    private string $priority = self::PRIORITY_MEDIUM;

    /**
     * Pattern configuration (JSON).
     *
     * For pattern rules: {"regex": "...", "field": "message", "case_sensitive": false}
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $patternConfig = null;

    /**
     * Threshold configuration (JSON).
     *
     * For threshold rules: {"count": 100, "window": "5 minutes", "operator": "gte"}
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $thresholdConfig = null;

    /**
     * Aggregation configuration (JSON).
     *
     * For aggregation: {"type": "count", "field": "level", "group_by": "ip_address"}
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $aggregationConfig = null;

    /**
     * Cooldown period in seconds.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $cooldownSeconds = 300;

    /**
     * Field to use for deduplication.
     */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $dedupeKey = null;

    /**
     * Actions configuration (JSON array).
     *
     * @var array<int, array<string, mixed>>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $actions = [];

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

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): static
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    /**
     * @return array<string, mixed>|null
     */
    public function getPatternConfig(): ?array
    {
        return $this->patternConfig;
    }

    /**
     * @param array<string, mixed>|null $patternConfig
     */
    public function setPatternConfig(?array $patternConfig): static
    {
        $this->patternConfig = $patternConfig;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getThresholdConfig(): ?array
    {
        return $this->thresholdConfig;
    }

    /**
     * @param array<string, mixed>|null $thresholdConfig
     */
    public function setThresholdConfig(?array $thresholdConfig): static
    {
        $this->thresholdConfig = $thresholdConfig;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAggregationConfig(): ?array
    {
        return $this->aggregationConfig;
    }

    /**
     * @param array<string, mixed>|null $aggregationConfig
     */
    public function setAggregationConfig(?array $aggregationConfig): static
    {
        $this->aggregationConfig = $aggregationConfig;

        return $this;
    }

    public function getCooldownSeconds(): int
    {
        return $this->cooldownSeconds;
    }

    public function setCooldownSeconds(int $cooldownSeconds): static
    {
        $this->cooldownSeconds = $cooldownSeconds;

        return $this;
    }

    public function getDedupeKey(): ?string
    {
        return $this->dedupeKey;
    }

    public function setDedupeKey(?string $dedupeKey): static
    {
        $this->dedupeKey = $dedupeKey;

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @param array<int, array<string, mixed>> $actions
     */
    public function setActions(array $actions): static
    {
        $this->actions = $actions;

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
     * Check if this is a pattern-based rule.
     */
    public function isPatternRule(): bool
    {
        return $this->type === self::TYPE_PATTERN;
    }

    /**
     * Check if this is a threshold-based rule.
     */
    public function isThresholdRule(): bool
    {
        return $this->type === self::TYPE_THRESHOLD;
    }

    /**
     * Get the regex pattern for pattern-based rules.
     */
    public function getRegexPattern(): ?string
    {
        return $this->patternConfig['regex'] ?? null;
    }

    /**
     * Get the field to match against for pattern-based rules.
     */
    public function getPatternField(): string
    {
        return $this->patternConfig['field'] ?? 'message';
    }

    /**
     * Check if pattern matching is case sensitive.
     */
    public function isCaseSensitive(): bool
    {
        return $this->patternConfig['case_sensitive'] ?? false;
    }

    /**
     * Get the threshold count.
     */
    public function getThresholdCount(): int
    {
        return $this->thresholdConfig['count'] ?? 0;
    }

    /**
     * Get the time window string.
     */
    public function getThresholdWindow(): string
    {
        return $this->thresholdConfig['window'] ?? '5 minutes';
    }

    /**
     * Get the comparison operator.
     */
    public function getThresholdOperator(): string
    {
        return $this->thresholdConfig['operator'] ?? self::OPERATOR_GTE;
    }

    /**
     * Get the aggregation type.
     */
    public function getAggregationType(): ?string
    {
        return $this->aggregationConfig['type'] ?? null;
    }

    /**
     * Get the aggregation field.
     */
    public function getAggregationField(): ?string
    {
        return $this->aggregationConfig['field'] ?? null;
    }

    /**
     * Get the group by field for aggregation.
     */
    public function getAggregationGroupBy(): ?string
    {
        return $this->aggregationConfig['group_by'] ?? null;
    }
}
