<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\CronJobMessage;
use App\Repository\CronJobRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[AsCommand(
    name: 'app:cron:run',
    description: 'Manually run a cron job',
)]
class CronRunCommand extends Command
{
    public function __construct(
        private readonly CronJobRepository $cronJobRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'Cron job ID or name')
            ->addOption('sync', null, InputOption::VALUE_NONE, 'Run synchronously (wait for completion)')
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command manually runs a cron job:

                        <info>php %command.full_name% 1</info>

                    You can also use the job name:

                        <info>php %command.full_name% "Daily Cleanup"</info>

                    By default, the job is dispatched asynchronously. Use --sync to wait:

                        <info>php %command.full_name% 1 --sync</info>
                    HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $identifier = $input->getArgument('id');

        // Try to find by ID first, then by name
        $job = is_numeric($identifier)
            ? $this->cronJobRepository->find((int) $identifier)
            : $this->cronJobRepository->findByName($identifier);

        if (!$job) {
            $io->error(sprintf('Cron job "%s" not found', $identifier));

            return Command::FAILURE;
        }

        $io->title(sprintf('Running Cron Job: %s', $job->getName()));
        $io->text([
            sprintf('Command: %s', $job->getCommand()),
            sprintf('Expression: %s', $job->getCronExpression()),
        ]);

        if (!$job->getIsActive()) {
            $io->warning('This job is currently inactive');
        }

        // Dispatch the message
        $envelope = $this->messageBus->dispatch(new CronJobMessage($job->getId()));

        // Check if handled synchronously
        $handledStamps = $envelope->all(HandledStamp::class);
        if (!empty($handledStamps)) {
            // Refresh job to get latest status
            $this->cronJobRepository->getEntityManager()->refresh($job);

            if ($job->getLastStatus() === 'success') {
                $io->success('Job completed successfully');
            } else {
                $io->error(sprintf('Job failed with status: %s', $job->getLastStatus()));
                if ($job->getLastOutput()) {
                    $io->section('Output');
                    $io->text($job->getLastOutput());
                }

                return Command::FAILURE;
            }
        } else {
            $io->success('Job dispatched for execution');
            $io->note('Check status with: bin/console app:cron:list');
        }

        return Command::SUCCESS;
    }
}
