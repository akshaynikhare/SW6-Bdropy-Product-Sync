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


class Jbsloxolddelete extends JbsloxfullBase
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
        parent::setLogName("Jbsloxolddelete");
        parent::init($container, $saleschannelRepository, $mediaService, $fileSaver, $connection, $baseServer, $systemConfigService);
    }


    public function startTask($whoStarted = null, $specialTaskString = null)
    {
        $this->setIniConfig();
        $key = Uuid::randomHex();
        $this->setLogKey($key);
        $this->SetStartTime();



        if ($whoStarted) {
            $this->createLog(" Process Started by > " . $whoStarted);
            $this->setLogName($whoStarted+"_Jbsloxolddelete");

        }
        $this->createLog("------------------------------------------Import Started------------------------------------------");

        try {
            $this->checkConfig();
            if ($specialTaskString === "DeleteAll") {
                $this->deleteAllProducts();
            } else {
                $this->importProductsFromBdroppy("articles");
            }
        } catch (Exception $e) {
            $this->createLog("Exiting!! Error:" . $e->getMessage());
        }

        $this->createLog("------------------------------------------Import Ended------------------------------------------");

        return $this->getLastLog();
    }


    public function importProductsFromBdroppy($importKey)
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

        if ($importKey == "articles") {
            $this->deleteProducts($ArticleList);
        }
    }

    private function deleteProducts($ArticleList)
    {
        $this->createLog("Deleting old Articles....");

        $allbdropyArticleCodeArray = [];
        foreach ($ArticleList as $line) {
            array_push($allbdropyArticleCodeArray, $line['code']);
        }

        $allDBProducts = $this->getProducts();
        //find/delete product not in bropy cataloge   
        foreach ($allDBProducts as $dBProduct) {
            if (array_search($dBProduct->getProductNumber(), $allbdropyArticleCodeArray, true) < 0 && !$dBProduct->getParentId()) {
                $this->deleteOldProductReferingBDroppy($dBProduct);
            }
        }
    }

    private function deleteAllProducts()
    {
        $this->createLog("Deleting All Bdropy Articles....");
        $this->deleteAllBDroppyProducts();
    }
}
