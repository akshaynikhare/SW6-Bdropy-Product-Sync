<?php

namespace slox_product_sync\Controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\HeaderUtils;
use slox_product_sync\Util\DebugLog;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use slox_product_sync\_JbImport\Jbsloxfullsync;
use slox_product_sync\_JbImport\Jbsloxnewsync;
use slox_product_sync\_JbImport\Jbsloxolddelete;
use slox_product_sync\_JbImport\Jbsloxproductupdate;
use slox_product_sync\Controller\bdroppy\BaseServer;
use Doctrine\DBAL\Connection;

class adminControlController extends AbstractController
{

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;



    /**
     * @var DebugLog
     */
    private $debugLog;


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
     * @var BaseServer
     */
    private $baseServer;
        /**
     * @var Connection
     */
    public $connection;


    /**
     * Constructor
     *
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        DebugLog $debugLog,
        Jbsloxfullsync $jbsloxfullsync,
        Jbsloxnewsync $jbsloxnewsync,
        Jbsloxolddelete $jbsloxolddelete,
        Jbsloxproductupdate $jbsloxproductupdate,
        BaseServer $baseServer,
        Connection $connection

    ) {
        $this->systemConfigService = $systemConfigService;
        $this->debugLog = $debugLog;

        $this->jbsloxfullsync = $jbsloxfullsync;
        $this->jbsloxnewsync = $jbsloxnewsync;
        $this->jbsloxolddelete = $jbsloxolddelete;
        $this->jbsloxproductupdate = $jbsloxproductupdate;
        $this->baseServer = $baseServer;
        $this->connection = $connection;
    }

    /**
     * @Route("/api/slox_product_sync/sync", name="api.slox_product_sync.sync", defaults={"auth_required"=false, "_acl"={"_routeScope"={"api"},"system.slox_product_sync"}}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/sync", name="api.slox_product_sync_old.sync", defaults={"auth_required"=false, "_acl"={"_routeScope"={"api"},"system.slox_product_sync"}}, methods={"GET"})
     */
    public function sync(Request $request): JsonResponse
    {

        try {

            $count =   $this->connection->fetchOne("SELECT count(`task_type`)  FROM `slox_BDropy_Sync_Status` where `pending_json` IS NOT NULL ORDER BY `updated_at`;");
            if ($count > 0) {

                $oldType = $this->connection->fetchOne("SELECT `task_type`  FROM `slox_BDropy_Sync_Status` where `pending_json` IS NOT NULL ORDER BY `updated_at`;");

                switch ((string)  $oldType) {
                    case 'Jbsloxfullsync':
                        $response = $this->jbsloxfullsync->startTask("controller_SYNC_Pending_Handler");
                        break;
                    case 'Jbsloxnewsync':
                        $response = $this->jbsloxnewsync->startTask("controller_SYNC_Pending_Handler");
                        break;
                    case 'Jbsloxolddelete':
                        $response = $this->jbsloxolddelete->startTask("controller_SYNC_Pending_Handler");
                        break;
                    case 'Jbsloxproductupdate':
                        $response = $this->jbsloxproductupdate->startTask("controller_SYNC_Pending_Handler");
                        break;
                    default:
                }
               
            }else{
                if ((bool) $this->systemConfigService->get('slox_product_sync.config.cornTaskActive')) {
                    $response = '';
                    switch ((string) $this->systemConfigService->get('slox_product_sync.config.cornSyncMethod')) {
                        case 'fullsync':
                            $response = $this->jbsloxfullsync->startTask("controller_SYNC_SyncMainTask");
                            break;
                        case 'newsync':
                            $response = $this->jbsloxnewsync->startTask("controller_SYNC_SyncMainTask");
                            break;
                        case 'olddelete':
                            $response = $this->jbsloxolddelete->startTask("controller_SYNC_SyncMainTask");
                            break;
                        case 'productupdate':
                            $response = $this->jbsloxproductupdate->startTask("controller_SYNC_SyncMainTask");
                            break;
                        default:
                           
                    }
                }
            }
        } catch (Exception $e) {
            echo ("Exiting!!  Error:" . $e->getMessage());
        }
        
       die();
    }


    /**
     * @Route("/api/slox_product_sync/fullsync", name="api.slox_product_sync.fullsync", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/fullsync", name="api.slox_product_sync_old.fullsync", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     */
    public function fullsync(Request $request): JsonResponse
    {

        try {

            $response = $this->jbsloxfullsync->startTask();

            $responsejosn = new JsonResponse([
                'success' => true,
                'message' => 'Sync Sucessfully',
                'log' => $response
            ], 200);

            return $responsejosn;
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 200);
        }
    }


    /**
     * @Route("/api/slox_product_sync/fullsync_status", name="api.slox_product_sync.fullsync_status", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/fullsync_status", name="api.slox_product_sync_old.fullsync_status", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     */
    public function fullsync_status(Request $request): JsonResponse
    {
        return  $this->jbsloxfullsync->taskStatus();
    }





    /**
     * @Route("/api/slox_product_sync/newsync", name="api.slox_product_sync.newsync", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/newsync", name="api.slox_product_sync_old.newsync", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     */
    public function newsync(Request $request): JsonResponse
    {

        // try {

        $response = $this->jbsloxnewsync->startTask();

        $responsejosn = new JsonResponse([
            'success' => true,
            'message' => 'Sync Sucessfully',
            'log' => $response
        ], 200);

        return $responsejosn;
        // } catch (Exception $e) {
        //     return new JsonResponse([
        //         'success' => false,
        //         'message' => $e->getMessage()
        //     ], 200);
        // }
    }


    /**
     * @Route("/api/slox_product_sync/newsync_status", name="api.slox_product_sync.newsync_status", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/newsync_status", name="api.slox_product_sync_old.newsync_status", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     */
    public function newsync_status(Request $request): JsonResponse
    {
        return  $this->jbsloxnewsync->taskStatus();
    }




    /**
     * @Route("/api/slox_product_sync/olddelete", name="api.slox_product_sync.olddelete", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/olddelete", name="api.slox_product_sync_old.olddelete", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     */
    public function olddelete(Request $request): JsonResponse
    {

        try {

            $response = $this->jbsloxolddelete->startTask();

            $responsejosn = new JsonResponse([
                'success' => true,
                'message' => 'Sync Sucessfully',
                'log' => $response
            ], 200);

            return $responsejosn;
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * @Route("/api/slox_product_sync/olddeleteall", name="api.slox_product_sync.olddeleteall", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/olddeleteall", name="api.slox_product_sync_old.olddeleteall", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     */
    public function olddeleteall(Request $request): JsonResponse
    {
        try {
            $response = $this->jbsloxolddelete->startTask(null, 'DeleteAll');

            $responsejosn = new JsonResponse([
                'success' => true,
                'message' => 'Delete Sucessfully',
                'log' => $response
            ], 200);

            return $responsejosn;
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 200);
        }
    }



    /**
     * @Route("/api/slox_product_sync/olddelete_status", name="api.slox_product_sync.olddelete_status", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/olddelete_status", name="api.slox_product_sync_old.olddelete_status", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     */
    public function olddelete_status(Request $request): JsonResponse
    {
        return  $this->jbsloxolddelete->taskStatus();
    }




    /**
     * @Route("/api/slox_product_sync/productupdate", name="api.slox_product_sync.productupdate", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/productupdate", name="api.slox_product_sync_old.productupdate", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     */
    public function productupdate(Request $request): JsonResponse
    {

        try {

            $response = $this->jbsloxproductupdate->startTask();

            $responsejosn = new JsonResponse([
                'success' => true,
                'message' => 'Sync Sucessfully',
                'log' => $response
            ], 200);

            return $responsejosn;
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 200);
        }
    }


    /**
     * @Route("/api/slox_product_sync/productupdate_status", name="api.slox_product_sync.productupdate_status", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/productupdate_status", name="api.slox_product_sync_old.productupdate_status", defaults={"_routeScope"={"api"},"auth_required"=false, "_acl"={"system.slox_product_sync"}}, methods={"GET"})
     */
    public function productupdate_status(Request $request): JsonResponse
    {
        return  $this->jbsloxproductupdate->taskStatus();
    }
}
