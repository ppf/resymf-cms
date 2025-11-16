<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-fixtures',
    description: 'Load database fixtures (sample data) into the database',
)]
class LoadFixturesCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append data instead of purging the database first')
            ->addOption('group', 'g', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Load specific fixture groups')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirmation prompt')
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command loads database fixtures:

    <info>php %command.full_name%</info>

By default, this will purge the database and load all fixtures. To append data:

    <info>php %command.full_name% --append</info>

To load specific fixture groups:

    <info>php %command.full_name% --group=user --group=settings</info>

To skip the confirmation prompt:

    <info>php %command.full_name% --yes</info>

This is a convenience wrapper around the Doctrine fixtures bundle command.
It replaces the legacy "resymf:populate" command.
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Load Database Fixtures');

        // Check if doctrine fixtures bundle is installed
        $application = $this->getApplication();
        if (!$application || !$application->has('doctrine:fixtures:load')) {
            $io->error([
                'The doctrine/doctrine-fixtures-bundle is not installed.',
                'Please install it with: composer require --dev doctrine/doctrine-fixtures-bundle',
            ]);
            return Command::FAILURE;
        }

        $append = $input->getOption('append');
        $groups = $input->getOption('group');
        $skipConfirmation = $input->getOption('yes');

        // Show warning about data loss
        if (!$append && !$skipConfirmation) {
            $io->warning([
                'This operation will purge all data from the database!',
                'All existing data will be permanently deleted.',
            ]);

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'Are you sure you want to continue? (yes/no) ',
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                $io->note('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Prepare arguments for doctrine:fixtures:load
        $arguments = [
            'command' => 'doctrine:fixtures:load',
        ];

        if ($append) {
            $arguments['--append'] = true;
            $io->note('Fixtures will be appended to existing data');
        } else {
            $io->note('Database will be purged before loading fixtures');
        }

        if (!empty($groups)) {
            $arguments['--group'] = $groups;
            $io->note('Loading fixture groups: ' . implode(', ', $groups));
        }

        // Don't ask for confirmation again, we already did
        $arguments['--no-interaction'] = true;

        // Execute the doctrine:fixtures:load command
        $fixturesInput = new ArrayInput($arguments);
        $returnCode = $application->doRun($fixturesInput, $output);

        if ($returnCode === Command::SUCCESS) {
            $io->success([
                'Fixtures loaded successfully!',
                'Your database now contains sample data for development and testing.',
            ]);

            $io->table(
                ['Available Fixture Classes'],
                [
                    ['UserFixtures - Sample users (admin, testuser, inactive)'],
                    ['SettingsFixtures - Default site configuration'],
                    ['ThemeFixtures - Sample themes'],
                    ['CategoryFixtures - Content categories'],
                    ['PageFixtures - Sample CMS pages'],
                ]
            );

            $io->note([
                'You can now log in with:',
                '  Username: admin',
                '  Password: admin123',
            ]);
        } else {
            $io->error('Failed to load fixtures. Please check the error messages above.');
        }

        return $returnCode;
    }
}
