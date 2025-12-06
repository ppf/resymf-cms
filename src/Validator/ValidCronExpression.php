<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates cron expression syntax using dragonmantank/cron-expression library.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class ValidCronExpression extends Constraint
{
    public string $message = 'The cron expression "{{ value }}" is not valid. Use format: minute hour day month weekday (e.g., "0 * * * *" for hourly).';
}
