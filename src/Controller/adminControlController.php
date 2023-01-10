<?php

namespace slox_product_sync\Controller;

use Exception;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
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


/**
 * @Acl(value={"system.slox_product_sync"})
 */
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
        BaseServer $baseServer

    ) {
        $this->systemConfigService = $systemConfigService;
        $this->debugLog = $debugLog;

        $this->jbsloxfullsync = $jbsloxfullsync;
        $this->jbsloxnewsync = $jbsloxnewsync;
        $this->jbsloxolddelete = $jbsloxolddelete;
        $this->jbsloxproductupdate = $jbsloxproductupdate;
        $this->baseServer = $baseServer;
    }



    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/fullsync", name="api.slox_product_sync.fullsync", defaults={"auth_required"=false}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/fullsync", name="api.slox_product_sync_old.fullsync", defaults={"auth_required"=false}, methods={"GET"})
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
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/fullsync_status", name="api.slox_product_sync.fullsync_status", defaults={"auth_required"=false}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/fullsync_status", name="api.slox_product_sync_old.fullsync_status", defaults={"auth_required"=false}, methods={"GET"})
     */
    public function fullsync_status(Request $request): JsonResponse
    {
        return  $this->jbsloxfullsync->taskStatus();
    }





    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/newsync", name="api.slox_product_sync.newsync", defaults={"auth_required"=false}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/newsync", name="api.slox_product_sync_old.newsync", defaults={"auth_required"=false}, methods={"GET"})
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
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/newsync_status", name="api.slox_product_sync.newsync_status", defaults={"auth_required"=false}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/newsync_status", name="api.slox_product_sync_old.newsync_status", defaults={"auth_required"=false}, methods={"GET"})
     */
    public function newsync_status(Request $request): JsonResponse
    {
        return  $this->jbsloxnewsync->taskStatus();
    }




    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/olddelete", name="api.slox_product_sync.olddelete", defaults={"auth_required"=false}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/olddelete", name="api.slox_product_sync_old.olddelete", defaults={"auth_required"=false}, methods={"GET"})
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
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/olddelete_status", name="api.slox_product_sync.olddelete_status", defaults={"auth_required"=false}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/olddelete_status", name="api.slox_product_sync_old.olddelete_status", defaults={"auth_required"=false}, methods={"GET"})
     */
    public function olddelete_status(Request $request): JsonResponse
    {
        return  $this->jbsloxolddelete->taskStatus();
    }




    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/productupdate", name="api.slox_product_sync.productupdate", defaults={"auth_required"=false}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/productupdate", name="api.slox_product_sync_old.productupdate", defaults={"auth_required"=false}, methods={"GET"})
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
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/productupdate_status", name="api.slox_product_sync.productupdate_status", defaults={"auth_required"=false}, methods={"GET"})
     * @Route("/api/v{version}/slox_product_sync/productupdate_status", name="api.slox_product_sync_old.productupdate_status", defaults={"auth_required"=false}, methods={"GET"})
     */
    public function productupdate_status(Request $request): JsonResponse
    {
        return  $this->jbsloxproductupdate->taskStatus();
    }

    
}
