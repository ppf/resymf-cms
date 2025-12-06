<?php

declare(strict_types=1);

namespace App\Validator;

use Cron\CronExpression;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates cron expression using the dragonmantank/cron-expression library.
 */
class ValidCronExpressionValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidCronExpression) {
            throw new UnexpectedTypeException($constraint, ValidCronExpression::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        try {
            new CronExpression($value);
        } catch (\InvalidArgumentException) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
