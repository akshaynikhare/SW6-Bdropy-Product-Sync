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

        parent::setLogFiles("/_log_run_Jbsloxnewsync.log", "/_log_Jbsloxnewsync.log");
        parent::init($container, $saleschannelRepository, $mediaService, $fileSaver, $connection, $baseServer, $systemConfigService);
    }


    public function taskStatus(): JsonResponse
    {

        if (!($this->CheckCanWeStartImport())) {
            return new JsonResponse([
                'isRunning' => true,
                'lastRun' =>  parent::getFileContents($this->runLogJsonFile),
                'log' =>  parent::getFileContents($this->logFileName)
            ], 200);
        }

        return new JsonResponse([
            'isRunning' => false,
            'lastRun' =>  parent::getFileContents($this->runLogJsonFile),
            'log' =>  parent::getFileContents($this->logFileName)
        ], 200);
    }

    public function startTask($whoStarted = null)
    {
        $this->lastlog = '';

        if (!($this->CheckCanWeStartImport())) {
            return 'one of the import is still in progress';
        }

        if (!($this->CleanLastLog())) {
            return 'Can not clearn last log';
        }

        if ($whoStarted) {
            $this->createLog(" Process Started by > " . $whoStarted);
        }

        $this->WiteWeStartedImport();
        $this->createLog("------------------------------------------Import Started------------------------------------------");

        //try {
            $this->checkConfig();
            $this->importProductsFromBdroppy("articles");
        // } catch (Exception $e) {
        //     $this->createLog("Exiting!! Error:" . $e->getMessage());
        // }
        $this->WiteWeStopedImport();
        $this->createLog("------------------------------------------Import Ended------------------------------------------");

        return $this->lastlog;
    }


    public function importProductsFromBdroppy($importKey)
    {
        $this->setIniConfig();
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
            $this->createLog("No Article found in catalog -> " . $catalogName);
            return;
        } else {
            $this->createLog("Article Count found in catalog >" . count($ArticleList));
        }

        if ($importKey == "articles") {
            $this->createProducts($ArticleList);
        }
    }

    private function createProducts($ArticleList)
    {
        $this->createLog("item to be imported : " . count($ArticleList));
        foreach ($ArticleList as $line) {
            $this->createAndUpdateProduct(
                $line,
                true,
                false
            );
        }
    }
}
