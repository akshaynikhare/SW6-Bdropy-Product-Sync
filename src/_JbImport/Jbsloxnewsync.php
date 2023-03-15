<?php

declare(strict_types=1);

namespace slox_product_sync\_JbImport;

use Exception;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Tag\TagEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\JsonResponse;
use slox_product_sync\Controller\bdroppy\BaseServer;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;


class Jbsloxnewsync extends JbsloxfullBase
{
    public function __construct(
        ContainerInterface $container,
        SalesChannelRepositoryInterface $saleschannelRepository,
        MediaService $mediaService,
        FileSaver $fileSaver,
        Connection $connection,
        BaseServer $baseServer,
        SystemConfigService $systemConfigService
    ) {

        parent::setLogName("Jbsloxnewsync");
        parent::init($container, $saleschannelRepository, $mediaService, $fileSaver, $connection, $baseServer, $systemConfigService);
    }



    public function startTask($whoStarted = null)
    {
        $this->setIniConfig();
        $key = Uuid::randomHex();
        $this->setLogKey($key);
        $this->SetStartTime();




        if ($whoStarted) {
            $this->createLog(" Process Started by > " . $whoStarted);
            $this->setLogName($whoStarted+"_Jbsloxnewsync");

        }

        try {
            $this->checkConfig();

            //check for old pending runs

            $count =  $this->GetOldSyncStatusCount();
            if ($count > 0) {

                $this->createLog("-----------------------------------------old Sync yet to complete .... resuming------------------------------------------");
                $oldkey = $this->connection->fetchOne("SELECT HEX(`id`)  FROM `slox_BDropy_Sync_Status` where `pending_json` IS NOT NULL   and `task_type`='$this->logKeyName' ORDER BY `updated_at`;");
                $this->setLogKey($oldkey);
                $this->createProductNext($oldkey);
            } else {
                if (!($this->CleanLastLog())) {
                    return 'Can not clearn last log';
                }
                $this->createLog("------------------------------------------Import Started------------------------------------------");


                $ArticleList = $this->importProductsFromBdroppy();
                $this->createLog("item to be imported : " . count($ArticleList));


                $this->connection->executeStatement(
                    "INSERT INTO `slox_BDropy_Sync_Status` (`id`, `task_type`, `started_by`, `pending_json`, `updated_at`, `created_at`)
                    VALUES (UNHEX('$key'), '$this->logKeyName', '$whoStarted', '" . base64_encode(json_encode($ArticleList)) . "', now(), now());"
                );
                $this->createProductNext($key);
            }
        } catch (Exception $e) {
            $this->createLog("Exiting!! Error:" . $e->getMessage());
        }



        return $this->getLastLog();
    }


    public function importProductsFromBdroppy()
    {

        $catalogName = $this->systemConfigService->get('slox_product_sync.config.userCatalogName');
        $catalogID = $this->baseServer->getUserCatalogIdByName($catalogName);
        if ($catalogID == null) {
            $this->createLog("catalog not found on bdroppy.com ->" . $catalogName);
            return;
        } else {
            $this->createLog("catalog found on bdroppy.com >" . $catalogName);
        }
        $ArticleList = $this->baseServer->getArticeArrayByCatalogId($catalogID);

        if ($ArticleList == null || !is_array($ArticleList) || count($ArticleList) < 1) {
            $this->createLog("No Article found in Bdroppy catalog -> " . $catalogName);
            return;
        } else {
            $this->createLog("Article Count found in Bdroppy catalog >" . count($ArticleList));
        }

        $allDB_BdroppyProducts = $this->getProducts();


        $allDB_BdroppyProductsIds = [];
        foreach ($allDB_BdroppyProducts as $products) {
            if (!$products->getParentId()) {
                array_push($allDB_BdroppyProductsIds, $products->getProductNumber());
            }
        }


        $ArticleListFiltred = [];
        foreach ($ArticleList as $item) {
            if (!array_search($item['code'], $allDB_BdroppyProductsIds, true)) {
                array_push($ArticleListFiltred, $item);
            }
        }


        $this->createLog("New Artical Found to be added >" . count($ArticleListFiltred));


        return $ArticleListFiltred;
    }


    private function createProductNext($key)
    {

        $ArticleList = $this->connection->fetchOne("SELECT pending_json FROM `slox_BDropy_Sync_Status` where `id`=UNHEX('$key')");
        if ($ArticleList) {
            $ArticleList = array_values(json_decode(base64_decode($ArticleList), true));
            $index = 0;
            foreach ($ArticleList as $k => $line) {
                $this->createAndUpdateProduct(
                    $line,
                    true,
                    false
                );
                $index++;

                if ($index >= count($ArticleList)) {
                    $this->connection->executeStatement(
                        "UPDATE `slox_BDropy_Sync_Status` SET
                        `pending_json`= NULL,
                        `updated_at` =  now()
                         WHERE `id` = UNHEX('$key');"
                    );
                    $this->createLog("------------------------------------------Import Ended------------------------------------------");
                } else {
                    $this->connection->executeStatement(
                        "UPDATE `slox_BDropy_Sync_Status` SET
                        `pending_json`='" . base64_encode(json_encode(array_slice($ArticleList, $index))) . "',
                        `updated_at` =  now()
                         WHERE `id` = UNHEX('$key');"
                    );
                }
            }
        }
    }
}
