<?php

namespace slox_product_sync\Controller;

use Exception;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;


/**
 * @Acl(value={"system.slox_product_sync"})
 */
class adminConfigController extends AbstractController
{

    public $container;

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
     * @var BaseServer
     */
    private $baseServer;


    /**
     * Constructor
     *
     */
    public function __construct(
        ContainerInterface $container,
        SystemConfigService $systemConfigService,
        DebugLog $debugLog,
        BaseServer $baseServer

    ) {
        $this->container = $container;
        $this->systemConfigService = $systemConfigService;
        $this->debugLog = $debugLog;
        $this->baseServer = $baseServer;
    }



    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/credenquire", name="api.slox_product_sync.credenquire", methods={"POST"})
     * @Route("/api/v{version}/slox_product_sync/credenquire", name="api.slox_product_sync_old.credenquire", methods={"POST"})
     */
    public function credenquire(Request $request): JsonResponse
    {
        try {
            $body = $request->getContent();
            $data = json_decode($body);

            if (!isset($data->user) || !isset($data->password))
                throw new Exception('Invalid request body');
            $result = $this->baseServer->getNewToken($data->user, $data->password);

            if (isset($result["api-token"])) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Valid Credentials!'
                ], 200);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => $result["message"]
                ], 200);
            }
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/bdropy/catogerytree", name="api.slox_product_sync.bdropy.catogerytree", defaults={"auth_required"=false}, methods={"POST"})
     * @Route("/api/v{version}/slox_product_sync/bdropy/catogerytree", name="api.slox_product_sync_old.bdropy.catogerytree", defaults={"auth_required"=false}, methods={"POST"})
     */
    public function bdropy_catogerytree(Request $request): JsonResponse
    {
        try {
            return new JsonResponse([
                'success' => true,
                'catogeryTree' => $this->baseServer->getAllCategoriesWithSubCategories()
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 200);
        }
    }
    

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/bdropy/currentmappings", name="api.slox_product_sync.bdropy.currentmappings", defaults={"auth_required"=false}, methods={"POST"})
     * @Route("/api/v{version}/slox_product_sync/bdropy/currentmappings", name="api.slox_product_sync_old.bdropy.currentmappings", defaults={"auth_required"=false}, methods={"POST"})
     */
    public function bdropy_currentmappings(Request $request): JsonResponse
    {
        try {

            return new JsonResponse([
                'success' => true,
                'map' => $this->baseServer->getCategoryMappingArray()
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/bdropy/addmappings", name="api.slox_product_sync.bdropy.addmappings", defaults={"auth_required"=false}, methods={"POST"})
     * @Route("/api/v{version}/slox_product_sync/bdropy/addmappings", name="api.slox_product_sync_old.bdropy.addmappings", defaults={"auth_required"=false}, methods={"POST"})
     */
    public function bdropy_addmappings(Request $request): JsonResponse
    {
        try {
            $body = $request->getContent();
            $data = json_decode($body);

            if (!isset($data->sel_bdropy_cat) || !isset($data->our_cat_id))
                throw new Exception('Invalid request body');


            $oldMap = $this->baseServer->getCategoryMappingArray();

            $categoryRepository = $this->container->get('category.repository');
            $catSystem = $categoryRepository->search(new Criteria([$data->our_cat_id]), Context::createDefaultContext())->first();
            $breadCrumbs = $catSystem->getBreadcrumb();
            $catName = '';
            foreach ($breadCrumbs as $breadCrumb) {
                $catName .= $breadCrumb.' > ';
            }
            
            $newMapItem = [
                'BdropyCat' => [
                    'value'=>$data->sel_bdropy_cat->value,
                    'label'=>$data->sel_bdropy_cat->label,
                    'code'=>$data->sel_bdropy_cat->code,
                    'parent_code'=>$data->sel_bdropy_cat->parent_code,
                ],
                'ourCat' => [
                    'id' => $data->our_cat_id,
                    'label' => rtrim($catName,' > ')
                ]
            ];

            if (count($oldMap) > 0) {
                $oldMap[count($oldMap)] = $newMapItem;
                $this->baseServer->setCategoryMappingArray($oldMap);
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Added!',
                    'map' => json_encode($oldMap)
                ], 200);
            } else {
                $this->baseServer->setCategoryMappingArray([$newMapItem]);

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Added!',
                    'map' => json_encode([$newMapItem])
                ], 200);

            }

        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'map' => ''
            ], 200);
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/bdropy/deletemappings", name="api.slox_product_sync.bdropy.deletemappings", defaults={"auth_required"=false}, methods={"POST"})
     * @Route("/api/v{version}/slox_product_sync/bdropy/deletemappings", name="api.slox_product_sync_old.bdropy.deletemappings", defaults={"auth_required"=false}, methods={"POST"})
     */
    public function bdropy_deletemappings(Request $request): JsonResponse
    {
        try {
            $body = $request->getContent();
            $data = json_decode($body);


            if (!isset($data->bdropy_cat_value))
                throw new Exception('Invalid request body');

            $oldMap = $this->baseServer->getCategoryMappingArray();

            if (count($oldMap) > 0) {

                $newMap = [];
                foreach ($oldMap as $item) {
                    if ($item->BdropyCat->value !== $data->bdropy_cat_value) {
                        $newMap[count($newMap)] = $item;
                    }
                }
                $this->baseServer->setCategoryMappingArray($newMap);
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Deleted!',
                    'map' => json_encode($newMap)
                ], 200);
            }else{
                return new JsonResponse([
                    'success' => true,
                    'message' => 'can not delete no element found in Map!',
                    'map' => json_encode($oldMap)
                ], 200);
            }

           
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'map' => ''
            ], 200);
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/slox_product_sync/bdropy/deleteallmappings", name="api.slox_product_sync.bdropy.deleteallmappings", defaults={"auth_required"=false}, methods={"POST"})
     * @Route("/api/v{version}/slox_product_sync/bdropy/deleteallmappings", name="api.slox_product_sync_old.bdropy.deleteallmappings", defaults={"auth_required"=false}, methods={"POST"})
     */
    public function bdropy_deleteallmappings(Request $request): JsonResponse
    {
        try {
            $this->baseServer->setCategoryMappingArray([]);
            return new JsonResponse([
                'success' => true,
                'message' => 'All Deleted!',
                'map' => ''
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'map' => ''
            ], 200);
        }
    }
}
