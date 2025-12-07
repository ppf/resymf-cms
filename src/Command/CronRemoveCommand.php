<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cron:remove',
    description: 'Remove crontab entry for the scheduler',
)]
class CronRemoveCommand extends Command
{
    private const CRON_MARKER = 'resymf-cms-scheduler';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Remove Cron Scheduler');

        // Get existing crontab
        $existingCrontab = shell_exec('crontab -l 2>/dev/null') ?: '';

        if (!str_contains($existingCrontab, self::CRON_MARKER)) {
            $io->info('No crontab entry found for the scheduler');

            return Command::SUCCESS;
        }

        // Filter out scheduler lines
        $lines = explode("\n", $existingCrontab);
        $lines = array_filter($lines, fn ($line) => !str_contains($line, self::CRON_MARKER));
        $newCrontab = implode("\n", $lines);

        $tempFile = tempnam(sys_get_temp_dir(), 'cron');
        file_put_contents($tempFile, $newCrontab);
        exec('crontab ' . $tempFile, $output, $returnCode);
        unlink($tempFile);

        if ($returnCode !== 0) {
            $io->error('Failed to update crontab');

            return Command::FAILURE;
        }

        $io->success('Crontab entry removed successfully');

        return Command::SUCCESS;
    }
}
