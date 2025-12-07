<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\CronJob;
use App\Message\CronJobMessage;
use App\Repository\CronJobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles cron job execution with locking and output capture.
 */
#[AsMessageHandler]
class CronJobMessageHandler
{
    private Application $application;

    public function __construct(
        private readonly CronJobRepository $cronJobRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LockFactory $lockFactory,
        private readonly LoggerInterface $logger,
        KernelInterface $kernel,
    ) {
        $this->application = new Application($kernel);
        $this->application->setAutoExit(false);
    }

    public function __invoke(CronJobMessage $message): void
    {
        $cronJob = $this->cronJobRepository->find($message->cronJobId);

        if (!$cronJob instanceof CronJob || !$cronJob->getIsActive()) {
            $this->logger->info('Skipping inactive or missing cron job', [
                'job_id' => $message->cronJobId,
            ]);

            return;
        }

        // Acquire lock with 1-hour TTL to prevent concurrent execution
        $lock = $this->lockFactory->createLock(
            'cron_job_' . $cronJob->getId(),
            ttl: 3600,
        );

        if (!$lock->acquire(blocking: false)) {
            $cronJob->setLastStatus(CronJob::STATUS_LOCKED);
            $this->entityManager->flush();

            $this->logger->info('Cron job skipped - already running', [
                'job_id' => $cronJob->getId(),
                'job_name' => $cronJob->getName(),
            ]);

            return;
        }

        try {
            $this->executeJob($cronJob);
        } finally {
            $lock->release();
        }
    }

    private function executeJob(CronJob $cronJob): void
    {
        $cronJob->setLastRunAt(new \DateTimeImmutable());
        $cronJob->setLastStatus(CronJob::STATUS_RUNNING);
        $this->entityManager->flush();

        $this->logger->info('Executing cron job', [
            'job_id' => $cronJob->getId(),
            'job_name' => $cronJob->getName(),
            'command' => $cronJob->getCommand(),
        ]);

        try {
            $output = new BufferedOutput();
            $input = new StringInput($cronJob->getCommand());

            $exitCode = $this->application->doRun($input, $output);

            $cronJob->setLastStatus($exitCode === 0 ? CronJob::STATUS_SUCCESS : CronJob::STATUS_FAILED);
            $cronJob->setLastOutput($output->fetch());

            $this->logger->info('Cron job completed', [
                'job_id' => $cronJob->getId(),
                'job_name' => $cronJob->getName(),
                'exit_code' => $exitCode,
            ]);
        } catch (\Throwable $e) {
            $cronJob->setLastStatus(CronJob::STATUS_FAILED);
            $cronJob->setLastOutput($e->getMessage() . "\n" . $e->getTraceAsString());

            $this->logger->error('Cron job failed with exception', [
                'job_id' => $cronJob->getId(),
                'job_name' => $cronJob->getName(),
                'error' => $e->getMessage(),
            ]);
        }

        $this->entityManager->flush();
    }
}
