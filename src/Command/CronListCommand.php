<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\CronJobRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cron:list',
    description: 'List all configured cron jobs',
)]
class CronListCommand extends Command
{
    public function __construct(
        private readonly CronJobRepository $cronJobRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('active-only', null, InputOption::VALUE_NONE, 'Only show active jobs')
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command lists all configured cron jobs:

                        <info>php %command.full_name%</info>

                    To show only active jobs:

                        <info>php %command.full_name% --active-only</info>
                    HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Cron Jobs');

        $jobs = $input->getOption('active-only')
            ? $this->cronJobRepository->findActive()
            : $this->cronJobRepository->findAllOrdered();

        if (empty($jobs)) {
            $io->info('No cron jobs configured');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($jobs as $job) {
            $status = $job->getLastStatus();
            $statusDisplay = match ($status) {
                'success' => '<fg=green>✓ success</>',
                'failed' => '<fg=red>✗ failed</>',
                'running' => '<fg=yellow>⟳ running</>',
                'locked' => '<fg=cyan>⊘ locked</>',
                default => '<fg=gray>- never</>',
            };

            $rows[] = [
                $job->getId(),
                $job->getName(),
                $job->getCronExpression(),
                mb_strlen($job->getCommand()) > 40
                    ? mb_substr($job->getCommand(), 0, 37) . '...'
                    : $job->getCommand(),
                $job->getIsActive() ? '<fg=green>Yes</>' : '<fg=gray>No</>',
                $statusDisplay,
                $job->getLastRunAt()?->format('Y-m-d H:i:s') ?? '-',
            ];
        }

        $io->table(
            ['ID', 'Name', 'Expression', 'Command', 'Active', 'Last Status', 'Last Run'],
            $rows,
        );

        $io->note(sprintf('%d job(s) configured', count($jobs)));

        return Command::SUCCESS;
    }
}
