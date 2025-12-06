<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message dispatched by scheduler for cron job execution.
 */
readonly class CronJobMessage
{
    public function __construct(
        public int $cronJobId,
    ) {
    }
}
