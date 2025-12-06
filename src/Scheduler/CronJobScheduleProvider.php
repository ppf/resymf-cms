<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Message\CronJobMessage;
use App\Repository\CronJobRepository;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * Provides schedule from database-configured cron jobs.
 *
 * Schedule is rebuilt on each worker restart (--time-limit=60).
 * Changes to jobs take effect within ~1 minute.
 */
#[AsSchedule('cron')]
class CronJobScheduleProvider implements ScheduleProviderInterface
{
    public function __construct(
        private readonly CronJobRepository $cronJobRepository,
    ) {
    }

    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();

        $activeJobs = $this->cronJobRepository->findActive();

        foreach ($activeJobs as $job) {
            $schedule->add(
                RecurringMessage::cron(
                    $job->getCronExpression(),
                    new CronJobMessage($job->getId()),
                ),
            );
        }

        return $schedule;
    }
}
