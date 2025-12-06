<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a console command exists in the application.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class ValidConsoleCommand extends Constraint
{
    public string $message = 'The command "{{ command }}" does not exist.';
}
