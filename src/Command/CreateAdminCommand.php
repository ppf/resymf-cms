<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create a new admin user for the CMS',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::OPTIONAL, 'Username for the admin user')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email address for the admin user')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password for the admin user')
            ->addOption('inactive', null, InputOption::VALUE_NONE, 'Create user as inactive')
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command creates a new admin user:

                        <info>php %command.full_name%</info>

                    You can also pass the username, email and password as arguments:

                        <info>php %command.full_name% admin admin@example.com secret123</info>

                    If you omit any arguments, the command will ask you to provide them interactively.

                    To create an inactive admin user:

                        <info>php %command.full_name% admin admin@example.com secret123 --inactive</info>
                    HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Create Admin User');

        // Get username
        $username = $input->getArgument('username');
        if (!$username) {
            $question = new Question('Please enter the username');
            $question->setValidator(function ($value) {
                if (empty($value)) {
                    throw new \RuntimeException('Username cannot be empty');
                }
                if (strlen($value) < 3) {
                    throw new \RuntimeException('Username must be at least 3 characters long');
                }

                return $value;
            });
            $username = $io->askQuestion($question);
        }

        // Check if username already exists
        if ($this->userRepository->findOneBy(['username' => $username])) {
            $io->error(sprintf('A user with username "%s" already exists', $username));

            return Command::FAILURE;
        }

        // Get email
        $email = $input->getArgument('email');
        if (!$email) {
            $question = new Question('Please enter the email address');
            $question->setValidator(function ($value) {
                if (empty($value)) {
                    throw new \RuntimeException('Email cannot be empty');
                }
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Invalid email format');
                }

                return $value;
            });
            $email = $io->askQuestion($question);
        }

        // Check if email already exists
        if ($this->userRepository->findOneBy(['email' => $email])) {
            $io->error(sprintf('A user with email "%s" already exists', $email));

            return Command::FAILURE;
        }

        // Get password
        $password = $input->getArgument('password');
        if (!$password) {
            $question = new Question('Please enter the password');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $question->setValidator(function ($value) {
                if (empty($value)) {
                    throw new \RuntimeException('Password cannot be empty');
                }
                if (strlen($value) < 6) {
                    throw new \RuntimeException('Password must be at least 6 characters long');
                }

                return $value;
            });
            $password = $io->askQuestion($question);

            // Confirm password
            $confirmQuestion = new Question('Please confirm the password');
            $confirmQuestion->setHidden(true);
            $confirmQuestion->setHiddenFallback(false);
            $confirmPassword = $io->askQuestion($confirmQuestion);

            if ($password !== $confirmPassword) {
                $io->error('Passwords do not match');

                return Command::FAILURE;
            }
        }

        // Create the user
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setIsActive(!$input->getOption('inactive'));

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Validate the user
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $io->error('Validation failed:');
            foreach ($errors as $error) {
                $io->writeln('  - ' . $error->getMessage());
            }

            return Command::FAILURE;
        }

        // Persist the user
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error('Failed to create admin user: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $io->success([
            sprintf('Admin user "%s" created successfully!', $username),
            sprintf('Email: %s', $email),
            sprintf('Role: ROLE_ADMIN'),
            sprintf('Status: %s', $user->isEnabled() ? 'Active' : 'Inactive'),
        ]);

        return Command::SUCCESS;
    }
}
