<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:cron:install',
    description: 'Install crontab entry for the scheduler',
)]
class CronInstallCommand extends Command
{
    private const CRON_MARKER = 'resymf-cms-scheduler';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('show-only', null, InputOption::VALUE_NONE, 'Only show the crontab entry without installing')
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command installs the crontab entry for the scheduler:

                        <info>php %command.full_name%</info>

                    To only show what would be installed:

                        <info>php %command.full_name% --show-only</info>

                    For Docker environments, add a scheduler service to docker-compose.yml instead.
                    HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Cron Scheduler Installation');

        // Detect Docker environment
        if (file_exists('/.dockerenv') || getenv('DOCKER_CONTAINER')) {
            $io->warning('Docker environment detected. For Docker, add a scheduler service to docker-compose.yml:');
            $io->text([
                'scheduler:',
                '  build:',
                '    context: .',
                '    target: app_dev',
                '  command: php bin/console messenger:consume scheduler_cron --time-limit=60',
                '  restart: unless-stopped',
                '  # ... (copy volumes, env_file, networks from php service)',
            ]);

            return Command::SUCCESS;
        }

        $phpBin = PHP_BINARY;
        $consolePath = $this->projectDir . '/bin/console';
        $logPath = $this->projectDir . '/var/log/cron.log';

        $cronEntry = sprintf(
            '* * * * * cd %s && %s %s messenger:consume scheduler_cron --time-limit=60 >> %s 2>&1 # %s',
            $this->projectDir,
            $phpBin,
            $consolePath,
            $logPath,
            self::CRON_MARKER,
        );

        if ($input->getOption('show-only')) {
            $io->section('Crontab Entry');
            $io->text($cronEntry);
            $io->note('Use this entry in your crontab (run `crontab -e` to edit)');

            return Command::SUCCESS;
        }

        // Get existing crontab
        $existingCrontab = shell_exec('crontab -l 2>/dev/null') ?: '';

        // Check if already installed
        if (str_contains($existingCrontab, self::CRON_MARKER)) {
            $io->warning('Crontab entry already exists. Use app:cron:remove to remove it first.');

            return Command::SUCCESS;
        }

        // Add new entry
        $newCrontab = trim($existingCrontab) . "\n" . $cronEntry . "\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'cron');
        file_put_contents($tempFile, $newCrontab);
        exec('crontab ' . $tempFile, $execOutput, $returnCode);
        unlink($tempFile);

        if ($returnCode !== 0) {
            $io->error('Failed to install crontab entry');

            return Command::FAILURE;
        }

        $io->success('Crontab entry installed successfully');
        $io->text($cronEntry);
        $io->note('The scheduler will check for jobs every minute');

        return Command::SUCCESS;
    }
}
