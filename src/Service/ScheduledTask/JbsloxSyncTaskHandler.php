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
     * @var String
     */
    private $logFileName;
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
        Jbsloxproductupdate $jbsloxproductupdate
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->systemConfigService = $systemConfigService;
        $this->jbsloxfullsync = $jbsloxfullsync;
        $this->jbsloxnewsync = $jbsloxnewsync;
        $this->jbsloxolddelete = $jbsloxolddelete;
        $this->jbsloxproductupdate = $jbsloxproductupdate;
        $this->logFileName = (__DIR__ . "/_log_jbsloxSyncTask.log");
    }


    public static function getHandledMessages(): iterable
    {
        return [JbsloxSyncTask::class];
    }

    public function run(): void
    {
        $this->createLog("****************************   Corn Starting : Stock Sync  ***********************************");
        $time_start = microtime(true);
        try {

            if ((bool) $this->systemConfigService->get('slox_product_sync.config.cornTaskActive')) {
                $response = '';
                switch((string) $this->systemConfigService->get('slox_product_sync.config.cornSyncMethod')){
                    case 'fullsync' :
                        $response = $this->jbsloxfullsync->startTask("CornTask");
                        break;
                    case 'newsync' :
                        $response = $this->jbsloxnewsync->startTask("CornTask");
                    break;
                    case 'olddelete' :
                        $response = $this->jbsloxolddelete->startTask("CornTask");
                    break;
                    case 'productupdate' :
                        $response = $this->jbsloxproductupdate->startTask("CornTask");
                    break;
                    default:
                        $this->createLog("Type of Sync to Run Automatically Not defined in Setting.");
                }
                $this->createLog("\n" . $response);
            }else{
                $this->createLog("Disabled from Setting.");
            }

        } catch (Exception $e) {
            $this->createLog("Error in Corn Job.  Exception :" . $e->getMessage());
        }

        $this->createLog("****************************   Corn Finished in " . $this->secondsToTime(microtime(true) - $time_start) . " ***********************************");
    }

    /**
     * @param $message
     */
    public function createLog($message)
    {
        file_put_contents($this->logFileName, "[ " . date("Y-m-d H:i:s") . " ]:" . $message . " \r\n", FILE_APPEND);
    }


    public function secondsToTime($seconds_time)
    {
            $hours = floor($seconds_time / 3600);
            $minutes = floor(($seconds_time - $hours * 3600) / 60);
            $seconds = floor($seconds_time - ($hours * 3600) - ($minutes * 60));
            return "$hours h  : $minutes m  : $seconds S";

    }
}
