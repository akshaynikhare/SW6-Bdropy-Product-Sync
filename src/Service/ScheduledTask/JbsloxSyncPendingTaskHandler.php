<?php

declare(strict_types=1);

namespace slox_product_sync\Service\ScheduledTask;

use Shopware\Core\Framework\App\Lifecycle\Update\AbstractAppUpdater;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use slox_product_sync\_JbImport\Jbsloxfullsync;
use slox_product_sync\_JbImport\Jbsloxnewsync;
use slox_product_sync\_JbImport\Jbsloxolddelete;
use slox_product_sync\_JbImport\Jbsloxproductupdate;
use Doctrine\DBAL\Connection;
use Exception;

class JbsloxSyncPendingTaskHandler extends ScheduledTaskHandler
{

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var Jbsloxfullsync
     */
    private $jbsloxfullsync;

    /**
     * @var Jbsloxnewsync
     */
    private $jbsloxnewsync;

    /**
     * @var Jbsloxolddelete
     */
    private $jbsloxolddelete;

    /**
     * @var Jbsloxproductupdate
     */
    private $jbsloxproductupdate;


    /**
     * @var Connection
     */
    public $connection;
    /**
     * Constructor
     *
     */
    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        SystemConfigService $systemConfigService,
        Jbsloxfullsync $jbsloxfullsync,
        Jbsloxnewsync $jbsloxnewsync,
        Jbsloxolddelete $jbsloxolddelete,
        Jbsloxproductupdate $jbsloxproductupdate,
        Connection $connection
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->systemConfigService = $systemConfigService;
        $this->jbsloxfullsync = $jbsloxfullsync;
        $this->jbsloxnewsync = $jbsloxnewsync;
        $this->jbsloxolddelete = $jbsloxolddelete;
        $this->jbsloxproductupdate = $jbsloxproductupdate;
        $this->connection = $connection;
    }


    public static function getHandledMessages(): iterable
    {
        return [JbsloxSyncPendingTask::class];
    }

    public function run(): void
    {

        $time_start = microtime(true);
        try {

            $count =   $this->connection->fetchOne("SELECT count(`task_type`)  FROM `slox_BDropy_Sync_Status` where `pending_json` IS NOT NULL ORDER BY `updated_at`;");
            if ($count > 0) {
                $this->createLog(">>>>  Starting Pending Stock Sync");

                $oldType = $this->connection->fetchOne("SELECT `task_type`  FROM `slox_BDropy_Sync_Status` where `pending_json` IS NOT NULL ORDER BY `updated_at`;");

                switch ((string)  $oldType) {
                    case 'Jbsloxfullsync':
                        $response = $this->jbsloxfullsync->startTask("Pending_Handler");
                        break;
                    case 'Jbsloxnewsync':
                        $response = $this->jbsloxnewsync->startTask("Pending_Handler");
                        break;
                    case 'Jbsloxolddelete':
                        $response = $this->jbsloxolddelete->startTask("Pending_Handler");
                        break;
                    case 'Jbsloxproductupdate':
                        $response = $this->jbsloxproductupdate->startTask("Pending_Handler");
                        break;
                    default:
                }
                $this->createLog(">>>>  Finished Pending Stock Sync in " . $this->secondsToTime(microtime(true) - $time_start));
            }
        } catch (Exception $e) {
            $this->createLog(">>>>  Pending_Handler Exiting!!  Error:" . $e->getMessage());
            $this->createLog(">>>>  Finished Pending Stock Sync in " . $this->secondsToTime(microtime(true) - $time_start));
        }
    }

    /**
     * @param $message
     */
    public function createLog($message)
    {
        return $this->connection->executeStatement(
            "INSERT INTO `slox_BDropy_Sync_Log` (`task_id` ,`task_type`, `date_time`, `log`) VALUES ('Jbslox','Pending_Handler', now(), '" . $message . "')"
        );
    }
    public function secondsToTime($seconds_time)
    {
        $hours = floor($seconds_time / 3600);
        $minutes = floor(($seconds_time - $hours * 3600) / 60);
        $seconds = floor($seconds_time - ($hours * 3600) - ($minutes * 60));
        return "$hours h  : $minutes m  : $seconds S";
    }
}
