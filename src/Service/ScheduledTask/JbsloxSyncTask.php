<?php declare(strict_types=1);

namespace slox_product_sync\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class JbsloxSyncTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'slox_product_import.JbsloxSyncTask';
    }

    public static function getDefaultInterval(): int
    {
        return (10*60*60); //in second // 10*60*60 = 10 hour 

    }
}