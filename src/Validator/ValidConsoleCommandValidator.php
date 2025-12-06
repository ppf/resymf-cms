<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a console command exists in the Symfony application.
 */
class ValidConsoleCommandValidator extends ConstraintValidator
{
    private Application $application;

    public function __construct(KernelInterface $kernel)
    {
        $this->application = new Application($kernel);
        $this->application->setAutoExit(false);
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidConsoleCommand) {
            throw new UnexpectedTypeException($constraint, ValidConsoleCommand::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // Extract command name (first word before any space/arguments)
        $commandName = explode(' ', trim($value), 2)[0];

        try {
            $this->application->find($commandName);
        } catch (CommandNotFoundException) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ command }}', $commandName)
                ->addViolation();
        }
    }
}
