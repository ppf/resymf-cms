<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:database:setup',
    description: 'Quick setup of development database (create, migrate, load fixtures)',
)]
class DatabaseSetupCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('skip-drop', null, InputOption::VALUE_NONE, 'Skip dropping existing database')
            ->addOption('skip-fixtures', null, InputOption::VALUE_NONE, 'Skip loading fixtures')
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command sets up your development database:

                        <info>php %command.full_name%</info>

                    This command performs the following steps:
                      1. Drops the existing database (if it exists)
                      2. Creates a new database
                      3. Runs all migrations
                      4. Loads fixtures (sample data)

                    To keep existing database without dropping:

                        <info>php %command.full_name% --skip-drop</info>

                    To skip loading fixtures:

                        <info>php %command.full_name% --skip-fixtures</info>

                    This is perfect for:
                      - Initial development setup
                      - Resetting your development database
                      - Quick testing with fresh data
                    HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $application = $this->getApplication();

        if (!$application) {
            $io->error('Could not access application');

            return Command::FAILURE;
        }

        $io->title('Database Setup');

        $skipDrop = $input->getOption('skip-drop');
        $skipFixtures = $input->getOption('skip-fixtures');

        // Step 1: Drop database (if not skipped)
        if (!$skipDrop) {
            $io->section('Step 1: Dropping existing database');
            $dropCommand = $application->find('doctrine:database:drop');
            $dropArguments = [
                'command' => 'doctrine:database:drop',
                '--force' => true,
                '--if-exists' => true,
            ];
            $dropInput = new ArrayInput($dropArguments);
            $dropInput->setInteractive(false);

            $returnCode = $dropCommand->run($dropInput, $output);
            if ($returnCode !== Command::SUCCESS) {
                $io->warning('Could not drop database (it may not exist)');
            } else {
                $io->success('Database dropped successfully');
            }
        } else {
            $io->note('Skipping database drop');
        }

        // Step 2: Create database
        $io->section('Step 2: Creating database');
        $createCommand = $application->find('doctrine:database:create');
        $createArguments = [
            'command' => 'doctrine:database:create',
            '--if-not-exists' => true,
        ];
        $createInput = new ArrayInput($createArguments);
        $createInput->setInteractive(false);

        $returnCode = $createCommand->run($createInput, $output);
        if ($returnCode !== Command::SUCCESS) {
            $io->error('Failed to create database');

            return Command::FAILURE;
        }
        $io->success('Database created successfully');

        // Step 3: Run migrations
        $io->section('Step 3: Running migrations');
        $migrateCommand = $application->find('doctrine:migrations:migrate');
        $migrateArguments = [
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
        ];
        $migrateInput = new ArrayInput($migrateArguments);
        $migrateInput->setInteractive(false);

        $returnCode = $migrateCommand->run($migrateInput, $output);
        if ($returnCode !== Command::SUCCESS) {
            $io->error('Failed to run migrations');

            return Command::FAILURE;
        }
        $io->success('Migrations executed successfully');

        // Step 4: Load fixtures (if not skipped)
        if (!$skipFixtures) {
            $io->section('Step 4: Loading fixtures');

            if (!$application->has('doctrine:fixtures:load')) {
                $io->warning([
                    'Fixtures bundle not installed. Skipping fixtures.',
                    'Install with: composer require --dev doctrine/doctrine-fixtures-bundle',
                ]);
            } else {
                $fixturesCommand = $application->find('doctrine:fixtures:load');
                $fixturesArguments = [
                    'command' => 'doctrine:fixtures:load',
                    '--no-interaction' => true,
                ];
                $fixturesInput = new ArrayInput($fixturesArguments);
                $fixturesInput->setInteractive(false);

                $returnCode = $fixturesCommand->run($fixturesInput, $output);
                if ($returnCode !== Command::SUCCESS) {
                    $io->error('Failed to load fixtures');

                    return Command::FAILURE;
                }
                $io->success('Fixtures loaded successfully');
            }
        } else {
            $io->note('Skipping fixtures');
        }

        // Final success message
        $io->success([
            'Database setup completed successfully!',
            'Your development environment is ready to use.',
        ]);

        if (!$skipFixtures && $application->has('doctrine:fixtures:load')) {
            $io->note([
                'Default admin credentials:',
                '  Username: admin',
                '  Password: admin123',
                '',
                'You can now start the development server:',
                '  php -S localhost:8000 -t public/',
                '  or: symfony server:start',
            ]);
        }

        return Command::SUCCESS;
    }
}
