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

class JbsloxSyncTaskHandler extends ScheduledTaskHandler
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
        return [JbsloxSyncTask::class];
    }

    public function run(): void
    {
        $this->createLog(">>>> SyncMainTask Starting...");
        $time_start = microtime(true);
        try {

            if ((bool) $this->systemConfigService->get('slox_product_sync.config.cornTaskActive')) {
                $response = '';
                switch ((string) $this->systemConfigService->get('slox_product_sync.config.cornSyncMethod')) {
                    case 'fullsync':
                        $response = $this->jbsloxfullsync->startTask("SyncMainTask");
                        break;
                    case 'newsync':
                        $response = $this->jbsloxnewsync->startTask("SyncMainTask");
                        break;
                    case 'olddelete':
                        $response = $this->jbsloxolddelete->startTask("SyncMainTask");
                        break;
                    case 'productupdate':
                        $response = $this->jbsloxproductupdate->startTask("SyncMainTask");
                        break;
                    default:
                        $this->createLog("Type of Sync to Run Automatically Not defined in Setting.");
                }
            } else {
                $this->createLog("Disabled from Setting.");
                $this->createLog(">>>>  SyncMainTask Finished in " . $this->secondsToTime(microtime(true) - $time_start));
            }
        } catch (Exception $e) {
            $this->createLog(">>>>  SyncMainTask  Exiting!!  Error:" . $e->getMessage());
            $this->createLog(">>>>  SyncMainTask Finished in " . $this->secondsToTime(microtime(true) - $time_start));
        }
    }

    /**
     * @param $message
     */
    public function createLog($message)
    {
        return $this->connection->executeStatement(
            "INSERT INTO `slox_BDropy_Sync_Log` (`task_id` ,`task_type`, `date_time`, `log`) VALUES ('Jbslox','CornTask', now(), '" . $message . "')"
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
