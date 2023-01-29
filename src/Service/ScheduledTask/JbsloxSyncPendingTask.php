<?php

declare(strict_types=1);

namespace slox_product_sync\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class JbsloxSyncPendingTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'slox_product_import.JbsloxSyncPendingTask';
    }

    public static function getDefaultInterval(): int
    {
        return (ini_get("max_execution_time") + (100));
    }
}
