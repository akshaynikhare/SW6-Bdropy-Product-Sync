<?php

declare(strict_types=1);

namespace slox_product_sync\_JbImport;

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


class JbsloxfullBase extends AbstractController
{
    /**
     * @var SalesChannelRepositoryInterface
     */
    public $productRepository;

    public $container;

    /**
     * @var MediaService
     */
    public $mediaService;

    /**
     * @var FileSaver
     */
    public $fileSaver;

    /**
     * @var Connection
     */
    public $connection;

    /**
     * @var String
     */
    public $lastlog;

    /**
     * @var String
     */
    public $fileActiveJson;

    /**
     * @var String
     */
    public $logFileName;

    /**
     * @var String
     */
    public $runLogJsonFile;

    /**
     * @var String
     */
    public $logLevel;
    /**
     * @var BaseServer
     */
    public $baseServer;

    /**
     * @var SystemConfigService
     */
    public $systemConfigService;


    public function __construct(
        ContainerInterface $container,
        SalesChannelRepositoryInterface $saleschannelRepository,
        MediaService $mediaService,
        FileSaver $fileSaver,
        Connection $connection,
        BaseServer $baseServer,
        SystemConfigService $systemConfigService
    ) {
        $this->setLogFiles("/runLog_JbsloxBase.log", "/_log_JbsloxBase.log");
        $this->init($container, $saleschannelRepository, $mediaService, $fileSaver, $connection, $baseServer, $systemConfigService);
    }

    public function setLogFiles($runLogJsonFile, $logFileName)
    {
        $this->runLogJsonFile = (__DIR__ . $runLogJsonFile);
        $this->logFileName = (__DIR__ . $logFileName);
    }

    public function init(
        ContainerInterface $container,
        SalesChannelRepositoryInterface $saleschannelRepository,
        MediaService $mediaService,
        FileSaver $fileSaver,
        Connection $connection,
        BaseServer $baseServer,
        SystemConfigService $systemConfigService
    ) {
        $this->container = $container;
        $this->fileSaver = $fileSaver;
        $this->mediaService = $mediaService;
        $this->connection = $connection;

        /** @var EntityRepository $productRepository */
        $productRepository = $this->container->get('product.repository');
        $this->productRepository = $productRepository;

        $this->lastlog = '';
        $this->fileActiveJson = (__DIR__ . "/importInProgress.json");

        $this->baseServer = $baseServer;
        $this->systemConfigService = $systemConfigService;
        //$this->logLevel = 1;
        $this->logLevel = 0;
    }







    public function createAndUpdateProduct($line, $canCreate = true, $canUpdate = true): void
    {

        // $handle = curl_init('https://ensyjrls6wwuecr.m.pipedream.net');
        // $encodedData = json_encode($line);

        // curl_setopt($handle, CURLOPT_POST, 1);
        // curl_setopt($handle, CURLOPT_POSTFIELDS, $encodedData);
        // curl_setopt($handle, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        // $result = curl_exec($handle);

        if ($line['code'] == "" and $line['name'] == null and $line['brand'] !=  "" and isset($line['sellPrice']) and $line['sellPrice'] != "") {
            return;
        }

        if (isset($line['title'])) {
            $line['name'] = $line['title'];
        } else {
            $line['name'] =  $line['brand'] . ' - ' . $line['name'];
        }

        $this->createLog("importing code : " . $line['code'] . "\t price :" . $line['sellPrice'] . "\t brand :" . $line['brand'] . "\t title :" . $line['name']);


        $product = $this->getProductFromNumber($line['code']);
        if ($product != null and $product instanceof ProductEntity) {
            if ($canUpdate) {

                $this->createLog("Product is already there . " . $product->getProductNumber() . " ");
                //updating manufactur & name
                $productData = $this->getProductUpdatePayload($line, $product);

                if (is_array($line['models']) && count($line['models']) > 1) {
                    if (isset($line['models'][0]['model'])) {

                        foreach ($line['models'] as $key => $model) {
                            if (isset($model["availability"]) && isset($model["model"])) {

                                $productVarientData = $this->getProuctVarientPayload($product->getId(), $model);
                                if ($productVarientData !== null) {
                                    $productData[] = $productVarientData;
                                }
                            }
                        }
                    }
                }

                if ($this->logLevel == 1) {
                    $this->createLog("\n---- Product Data start ---------------------------------------------\n " . print_r($productData, true) . "---- Product Data End ---------------------------------------------");
                }
                /** @var EntityRepository $productRepository */
                $productRepository = $this->container->get('product.repository');
                $productRepository->update(
                    $productData,
                    $this->createContextWithRules()
                );
            }
        } else {
            if ($canCreate) {

                $this->createLog("Create Product START:" . "code :" . $line['code']);
                $productData = $this->getProductInsertPayload($line, null);
                if ($this->logLevel == 1) {
                    $this->createLog("\n---- Product Data start ---------------------------------------------\n " . print_r($productData, true) . "---- Product Data End ---------------------------------------------");
                }
                /** @var EntityRepository $productRepository */
                $productRepository = $this->container->get('product.repository');
                $a =  $productRepository->create(
                    $productData,
                    $this->createContextWithRules()
                );
                $this->createLog("Create Product END " . $line['code']);
            }
        }
    }

    public function deleteOldProduct($allbdropyArticleCodeArray): void
    {

        /** @var EntityRepository $productRepository */
        $productRepository = $this->container->get('product.repository');
        $allDBProducts = $this->getAllProduct();
        //find/delete product not in bropy cataloge   
        foreach ($allDBProducts as $dBProduct) {;
            if (array_search($dBProduct->getProductNumber(), $allbdropyArticleCodeArray, true) <= 0 && !$dBProduct->getParentId()) {
                $this->createLog("Deleting --- " . $dBProduct->getProductNumber());
                $productRepository->delete([
                    [
                        'id' => $dBProduct->getId(),
                    ],
                ], $this->createContextWithRules());
            }
        }
    }






















    public function checkConfig()
    {
        if ($this->systemConfigService->get('slox_product_sync.config.user') === null || $this->systemConfigService->get('slox_product_sync.config.user') === '') {
            throw new \RuntimeException("\"User\" not set in Config!");
        }
        if ($this->systemConfigService->get('slox_product_sync.config.user') === null || $this->systemConfigService->get('slox_product_sync.config.password') === '') {
            throw new \RuntimeException("\"Password\" not set in Config!");
        }
        if ($this->systemConfigService->get('slox_product_sync.config.userCatalogName') === null || $this->systemConfigService->get('slox_product_sync.config.userCatalogName') === '') {
            throw new \RuntimeException("\"user Catalog Name\" not set in Config!");
        }

        if ($this->systemConfigService->get('slox_product_sync.config.ImportToSalesChannel') === null || $this->systemConfigService->get('slox_product_sync.config.ImportToSalesChannel') === '') {
            throw new \RuntimeException("\"Sales Chanel EntryPoint\" not set in Config!");
        }
        if ($this->systemConfigService->get('slox_product_sync.config.ImportToCategories') === null || $this->systemConfigService->get('slox_product_sync.config.ImportToCategories') === '') {
            throw new \RuntimeException("\"Categories EntryPoint\" not set in Config!");
        }

        if ($this->systemConfigService->get('slox_product_sync.config.BearerToken') === null || $this->systemConfigService->get('slox_product_sync.config.BearerToken') === '') {
            throw new \RuntimeException("\"Api Token\" not set in Config! check user/password in setting");
        }
        return;
    }




    public function CheckCanWeStartImport()
    {
        if (!file_exists($this->fileActiveJson)) {
            return true;
        } else {
            $json = json_decode(file_get_contents($this->fileActiveJson), true);

            if ($json['inProgress']['timeStamp'] && $json['inProgress']['type']) {

                $to_time = strtotime(date("Y-m-d H:i:s"));
                $from_time = strtotime($json['inProgress']['timeStamp']);
                if (round(abs($to_time - $from_time) / 60, 2) < 30) {
                    return false;
                } else {
                    $this->WiteWeStopedImport();
                    return true;
                }
            } else {
                $this->WiteWeStopedImport();
                return true;
            }
        }
    }

    public function WiteWeStartedImport()
    {
        if (!file_exists($this->fileActiveJson)) {
            $response = array();
            $response['inProgress'] = array(
                'timeStamp' => date("Y-m-d H:i:s"),
                'type' => 'Jbsloxfullsync'
            );

            file_put_contents($this->fileActiveJson, json_encode($response));
        } else {
            throw new \RuntimeException('failed to wite impor start to internal file');
        }
    }
    public function WiteWeStopedImport()
    {
        file_put_contents($this->runLogJsonFile, date("Y-m-d H:i:s"));

        if (!file_exists($this->fileActiveJson)) {
            return true;
        } else {
            return unlink($this->fileActiveJson);
        }
    }

    public function getDeliveryTime($deliveryTimeName)
    {

        $repository = $this->container->get('delivery_time.repository'); //shipping_method.repository
        $criteria = (new Criteria())->addFilter(new EqualsFilter('name', $deliveryTimeName));

        $deliveryTime = $repository->search($criteria, $this->createContextWithRules())->first();

        if ($deliveryTime) {
            return $deliveryTime; //->getId();
        }
        return false;
    }

    /**
     * @param $productNumber
     * @return ProductEntity
     */
    public function getProductFromNumber($productNumber)
    {
        /** @var EntityRepository $productRepository */
        $productRepository = $this->container->get('product.repository');
        $criteria = (new Criteria())->addFilter(new EqualsFilter('productNumber', $productNumber));

        /** @var EntitySearchResult $entities */
        $entities = $productRepository->search(
            $criteria,
            $this->createContextWithRules()
        );

        /** @var ProductEntity $product */
        $product = $entities->getEntities()->first();

        return $product;
    }

    /**
     * @param $productNumber
     * @return ProductEntity
     */
    public function getAllProduct()
    {
        /** @var EntityRepository $productRepository */
        $productRepository = $this->container->get('product.repository');

        /** @var EntitySearchResult $entities */
        $entities = $productRepository->search(
            new Criteria(),
            $this->createContextWithRules()
        );

        return $entities->getEntities();
    }








    public function getProuctVarientPayload($productParentID, $model)
    {

        $VarientObjectID = $this->getPropertieIdByName($model['model']);

        if ($VarientObjectID === null or $VarientObjectID === "") {
            return null;
        }

        $productRepository = $this->container->get('product.repository');
        $criteria = new Criteria();

        $criteria->addFilter(new EqualsFilter('parentId', $productParentID));
        $criteria->addFilter(new EqualsFilter('optionIds', $VarientObjectID));

        /** @var EntitySearchResult $entities */
        $entities = $productRepository->search(
            $criteria,
            $this->createContextWithRules()
        );

        /** @var ProductEntity $product */
        $product = $entities->getEntities()->first();


        if ($product != null and $product instanceof ProductEntity) {
            $payload = [
                'id' => $product->getId(),
                'active' => intval($model["availability"]) > 0 ? true : false,
                'stock' => intval($model["availability"]),
                'purchasePrices' => [[
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => floatval($model['bestTaxable']),
                    'net' => floatval($model['bestTaxable'] / 1.19),
                    'linked' => true,
                ]],
                'price' => [[
                    'net' => floatval($model['sellPrice'] / 1.19),
                    'gross' => floatval($model['sellPrice']),
                    'linked' => true,
                    'currencyId' => Defaults::CURRENCY,
                ]],
            ];

            return $payload;
        }

        return null;
    }



    /**
     * @param $line
     * @param ProductEntity $product
     * @return array[]
     */
    public function getProductUpdatePayload($line, $product): array
    {
        if ($product) {
            $productId = $product->getId();
            if (isset($line['stock'])) {
                $stock = $line['stock'] ? $line['stock'] : 0;
            } elseif (isset($line['availability'])) {
                $stock = $line['availability'] ? $line['availability'] : 0;
            } else {
                $stock = 0;
            }

            $line_descriptions = "";
            if (isset($line['descriptions']["de_DE"])) {
                $line_descriptions = $line['descriptions']["de_DE"];
            } else if (is_string($line['descriptions'])) {
                $line_descriptions = $line['descriptions'];
            } else {
                if (is_array($line['attributes']) && count($line['attributes']) > 0) {
                    foreach ($line['attributes'] as $key => $value) {
                        $line_descriptions .= "$key : $value,";
                    }
                    $line_descriptions = rtrim($line_descriptions, ',');
                }
            }

            $payload = [
                [
                    'id' => $productId,
                    'name' => $line['name'],
                    'description' =>  $line_descriptions,
                    'active' => intval($stock) > 0 ? true : false,
                    'stock' => intval($stock),
                    'weight' => $line['weight'],
                    'purchasePrices' => [[
                        'currencyId' => Defaults::CURRENCY,
                        'gross' => floatval($line['bestTaxable']),
                        'net' => floatval($line['bestTaxable'] / 1.19),
                        'linked' => true,
                    ]],
                    'price' => [[
                        'net' => floatval($line['sellPrice'] / 1.19),
                        'gross' => floatval($line['sellPrice']),
                        'linked' => true,
                        'currencyId' => Defaults::CURRENCY,
                    ]],
                    'deliveryTime' => $this->getdeliveryTimePayload(),
                ],
            ];
        }

        return $payload;
    }



    public function getMediaId($mediaUrl)
    {

        $mediaPath = pathinfo($mediaUrl);

        if ($mediaPath) {
            $mediaName = $mediaPath['filename'];
            //$mediaName = trim($mediaName, '\t\n\r\0\x0B\??');
            $mediaName = str_replace(".", "", str_replace("%", "", str_replace("%20", "", $mediaName)));
        }
        if ($mediaName) {
            /** @var EntityRepository $mediaRepository */
            $mediaRepository = $this->container->get('media.repository');
            $criteria = (new Criteria())->addFilter(new EqualsFilter('fileName', $mediaName));

            /** @var EntitySearchResult $entities */
            $entities = $mediaRepository->search(
                $criteria,
                $this->createContextWithRules()
            );

            /** @var MediaEntity $media */
            $media = $entities->getEntities()->first();

            if ($media instanceof MediaEntity) {
                return $media->getId();
            }
        }

        return null;
    }

    public function getProductInsertPayload($line, $product = null): array
    {

        if ($product) {
            $productId = $product->getId();
        } else {
            $productId = Uuid::randomHex();
        }

        if (isset($line['stock'])) {
            $stock = $line['stock'] ? $line['stock'] : 0;
        } elseif (isset($line['availability'])) {
            $stock = $line['availability'] ? $line['availability'] : 0;
        } else {
            $stock = 0;
        }

        $line_descriptions = "";
        if (isset($line['descriptions']["de_DE"])) {
            $line_descriptions = $line['descriptions']["de_DE"];
        } else if (is_string($line['descriptions'])) {
            $line_descriptions = $line['descriptions'];
        } else {
            if (is_array($line['attributes']) && count($line['attributes']) > 0) {
                foreach ($line['attributes'] as $key => $value) {
                    $line_descriptions .= "$key : $value,";
                }
                $line_descriptions = rtrim($line_descriptions, ',');
            }
        }
        $payload = [
            [
                'id' => $productId,
                'productNumber' => "" . $line['code'] . "",
                'active' => intval($stock) > 0 ? true : false,
                'taxId' =>  $this->getTaxId(),
                'stock' => intval($stock),
                'purchaseUnit' => 1.0,
                'referenceUnit' => 1.0,
                'shippingFree' => false,
                'purchasePrices' => [[
                    'net' => floatval($line['bestTaxable']),
                    'gross' => floatval($line['bestTaxable'] * 1.19),
                    'linked' => true,
                    'currencyId' => Defaults::CURRENCY
                ]],
                'price' => [[
                    'net' => floatval($line['sellPrice']),
                    'gross' => floatval($line['sellPrice'] * 1.19),
                    'linked' => true,
                    'currencyId' => Defaults::CURRENCY,
                    'listPrice' => [
                        'net' => floatval($line['suggestedPrice']),
                        'gross' => floatval($line['suggestedPrice'] * 1.19),
                        'linked' => true,
                        'currencyId' => Defaults::CURRENCY,
                    ],
                ]],

                'weight' => $line['weight'],
                'width' => 0,
                'height' => 0,
                'length' => 0,
                //'releaseDate' => $nextAvailibility,
                'displayInListing' => true,
                'name' => $line['name'],
                'description' =>  $line_descriptions,
                //'ean' => '',

                "categories" => [
                    ["id" => $this->systemConfigService->get('slox_product_sync.config.ImportToCategories')]
                ],
                'deliveryTime' => $this->getdeliveryTimePayload(),
                //"categories" => $this->getCategoryPayloads($line,$this->systemConfigService->get('slox_product_sync.config.ImportToCategories')),
                //'properties' => $this->getPropertiesPayloads($line),
                //'maxPurchase' => 5,

            ],
        ];

        if ($product == null) {
            $productMediaID = Uuid::randomHex();
            $payload[0]["media"] = $this->getMediaPayload($line, $productMediaID);
            $payload[0]["coverId"] = $productMediaID;
            $payload[0]["visibilities"] = [
                [
                    'salesChannelId' => $this->getSalesChannelId(),
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ]
            ];
        }

        if (isset($line['brand']) && $line['brand']) {
            $manufactureId = $this->getManufacturerId($line['brand']);
            if ($manufactureId && $manufactureId != "") {
                $payload[0]["manufacturer"] = [
                    "id" => $manufactureId
                ];
            } else {
                $payload[0]["manufacturer"] = [
                    "name" => $line['brand']
                ];
            }

            if (strpos($payload[0]["name"], $line['brand']) === false) {
                $payload[0]["name"] = $line['brand'] . " - " . $line['title'];
            }
        }


        if (is_array($line['models']) && count($line['models']) > 1) {
            if (isset($line['models'][0]['model'])) {
                $payload[0]["configuratorGroupConfig"] = [
                    [
                        "id" =>   $this->getorGenratePropertyGroupByName('model'),       //group id for "color"
                        "representation" => "box",
                        "expressionForListings" => false                   // display all colors in listings
                    ],
                ];

                $payload[0]["children"] = [];
                foreach ($line['models'] as $key => $model) {
                    if (isset($model["availability"]) && isset($model["model"])) {
                        $payload[0]["children"][] = $this->getProuctClildPayload($line['code'] . '.' . $key, $model);
                    }
                }
                $payload[0]["configuratorSettings"] = $this->getAllOptionIDs($payload[0]["children"]);
            }
        }

        return $payload;
    }

    public function getProuctClildUpdatedPayload($productCode, $model)
    {
        $groupId = $this->getorGenratePropertyGroupByName('model');
        $payload = [
            "productNumber" => "" . $productCode . "",
            "stock" => $model["availability"],
            "price" => [[
                'net' => floatval($model['sellPrice'] / 1.19),
                'gross' => floatval($model['sellPrice']),
                'linked' => false,
                'currencyId' => Defaults::CURRENCY,
            ]],
            "options" => [
                ["id" => Uuid::randomHex(), "groupId" => $groupId, "name" => $model["model"]]
            ]
        ];
        return $payload;
    }

    public function getProuctClildPayload($productCode, $model)
    {
        $groupId = $this->getorGenratePropertyGroupByName('model');
        $payload = [
            "productNumber" => "" . $productCode . "",
            "stock" => $model["availability"],
            "price" => [[
                'net' => floatval($model['sellPrice'] / 1.19),
                'gross' => floatval($model['sellPrice']),
                'linked' => false,
                'currencyId' => Defaults::CURRENCY,
            ]],
            "options" => [
                ["id" => Uuid::randomHex(), "groupId" => $groupId, "name" => $model["model"]]
            ]
        ];
        return $payload;
    }
    public function getAllOptionIDs($productChildrenArray)
    {
        $payload_0_configuratorSettings = [];
        foreach ($productChildrenArray as  $productChildren) {
            $payload_0_configuratorSettings[] = [
                "optionId" =>   $productChildren["options"][0]['id']
            ];
        }

        return $payload_0_configuratorSettings;
    }

    /**
     * @param $tagName
     * @return false|\Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent|TagEntity
     */
    public function getProductTag($tagName)
    {

        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('tag.repository');
        $criteria = (new Criteria())->addFilter(new EqualsFilter('name', $tagName));

        /** @var TagEntity $productTag */
        $productTag = $repository->search($criteria, $this->createContextWithRules())->first();

        if ($productTag) {
            return $productTag;
        } else {
            $productTag = $repository->create(
                array(
                    [
                        'id' => Uuid::randomHex(),
                        'name' => $tagName
                    ]
                ),
                $this->createContextWithRules()
            );

            return $productTag;
        }
        return false;
    }

    public function getManufacturerId($manufacturerName): string
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('product_manufacturer.repository');

        $criteria = (new Criteria())->addFilter(new EqualsFilter('name', $manufacturerName));

        /** @var ProductManufacturerEntity $productManufacturerEntity */
        $productManufacturerEntity = $repository->search($criteria, $this->createContextWithRules())->first();

        if ($productManufacturerEntity instanceof ProductManufacturerEntity)
            return $productManufacturerEntity->getId();

        return "";
    }

    public function getSalesChannelId(): string
    {
        $ImportToSalesChannel = $this->systemConfigService->get('slox_product_sync.config.ImportToSalesChannel');

        if ($ImportToSalesChannel == '') {
            $this->createLog("\"SalesChanel To Import Into\" not set in Config!");
            return "";
        }

        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('sales_channel.repository');
        $criteria = new Criteria([$ImportToSalesChannel]);


        /** @var SalesChannelEntity $salesChannelEntity */
        $salesChannelEntity = $repository->search($criteria, $this->createContextWithRules())->first();

        if ($salesChannelEntity instanceof SalesChannelEntity) {
            return $salesChannelEntity->getId();
        } else {
            $this->createLog("SalesChannel not Found");
            return "";
        }
    }



    public function getMediaPayload($line, $productMediaID)
    {
        if ($this->logLevel == 1) {
            $this->createLog("Create Media START");
        }

        $mediaArray = [];
        $count = 0;

        if (is_array($line['pictures']) && count($line['pictures']) > 0) {
            foreach ($line['pictures'] as $key => $data) {
                if ($data['url'] != "") {



                    if (strpos($data['url'], 'http') !== false) {
                        $imageUrl = $data['url'];
                    } else {
                        $imageUrl = 'https://www.mediabd.it/storage-foto/prod/' . $data['url'];
                    }

                    if ($this->logLevel == 1) {
                        $this->createLog("trying to find image: " . $imageUrl);
                    }


                    $mediaID = $this->getMediaId($imageUrl);


                    if (!$mediaID || $mediaID == null)
                        $mediaID = $this->addImageToProductMedia($imageUrl,  $this->createContextWithRules());

                    else
                    if ($this->logLevel == 1) {
                        $this->createLog("Media Already Exist  , System ID: " . $mediaID);
                    }


                    if ($mediaID) {
                        if ($count > 0) {
                            $productMediaID = Uuid::randomHex();
                        }

                        $mediaPayload = [
                            'id' => $productMediaID,
                            'position' => 1,
                            'mediaId' => $mediaID,
                        ];

                        $mediaArray[] = $mediaPayload;
                        $count = $count + 1;
                    }
                }
            }
            if ($this->logLevel == 1) {
                $this->createLog($line['code'] . "\n Media " . print_r($mediaArray, true));
            }
        }


        return $mediaArray;
    }




    public function getCategoryIdByName($categoryName, $parentCategoryID = null): string
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('category.repository');
        $criteria = (new Criteria())->addFilter(new EqualsFilter('name', $categoryName));
        if ($parentCategoryID) {
            $criteria->addFilter(new EqualsFilter('parentId', $parentCategoryID));
        }

        /** @var CategoryEntity $categoryEntity */
        $categoryEntity = $repository->search($criteria, $this->createContextWithRules())->first();

        if ($categoryEntity instanceof CategoryEntity)
            return $categoryEntity->getId();

        return "";
    }





    public function getPropertiesPayloads($line)
    {


        if ($this->logLevel == 1) {
            $this->createLog("Create Properties START");
        }
        $PropertiesArray = [];

        if (isset($line['attributes'])) {
            if (is_array($line['attributes']) && count($line['attributes']) > 0) {
                foreach ($line['attributes'] as $key => $value) {
                    //$key : $value
                    $groupId = $this->getorGenratePropertyGroupByName($key);
                    $PropertieID = $this->getPropertieIdByName($value, $groupId);
                    if ($PropertieID != "") {
                        array_push($PropertiesArray, ["id" => $PropertieID, "groupId" => $groupId]);
                    } else {
                        array_push($PropertiesArray, ["id" => Uuid::randomHex(), "groupId" => $groupId, "name" => $value]);
                    }
                }
            }
        }
        return $PropertiesArray;
    }

    public function getorGenratePropertyGroupByName($key): string
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('property_group.repository');
        $criteria = (new Criteria())->addFilter(new EqualsFilter('name', $key));
        $context = $this->createContextWithRules();
        /** @var PropertyGroupEntity $propertyGroupEntity */
        $propertyGroupEntity = $repository->search($criteria, $context)->first();


        if ($propertyGroupEntity instanceof PropertyGroupEntity)
            return $propertyGroupEntity->getId();
        else {
            $propertyGroupid = Uuid::randomHex();
            $data = [
                'id' =>  $propertyGroupid,
                'name' => $key,
                'options' => [],
            ];
            $repository->upsert([$data], $context);
            return $propertyGroupid;
        }
        return "";
    }



    public function getPropertieIdByName($value, $groupId = null): string
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('property_group_option.repository');
        $criteria = (new Criteria())->addFilter(new EqualsFilter('name', $value));
        if ($groupId !== null) {
            $criteria = (new Criteria())->addFilter(new EqualsFilter('groupId', $groupId));
        }


        /** @var PropertyGroupOptionEntity $PropertyGroupOptionEntity */
        $propertyGroupOptionEntity = $repository->search($criteria, $this->createContextWithRules())->first();

        if ($propertyGroupOptionEntity instanceof PropertyGroupOptionEntity)
            return $propertyGroupOptionEntity->getId();

        return "";
    }



    public function getdeliveryTimePayload()
    {

        $payloadExistingID = $this->getDiliveryTimeIdByName('5-7 days');

        if ($payloadExistingID != '') {
            $payloadArray =     [
                'id' => $payloadExistingID,
                'name' => '5-7 days',
                'min' => 5,
                'max' => 7,
                'unit' => DeliveryTimeEntity::DELIVERY_TIME_DAY,
            ];
        } else {
            $payloadArray =     [
                'id' => Uuid::randomHex(),
                'name' => '5-7 days',
                'min' => 5,
                'max' => 7,
                'unit' => DeliveryTimeEntity::DELIVERY_TIME_DAY,
            ];
        }


        return $payloadArray;
    }

    public function getDiliveryTimeIdByName($DiliveryTimeName): string
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('delivery_time.repository');
        $criteria = (new Criteria())->addFilter(new EqualsFilter('name', $DiliveryTimeName));

        /** @var CategoryEntity $categoryEntity */
        $deliveryTimeEntity = $repository->search($criteria, $this->createContextWithRules())->first();

        if ($deliveryTimeEntity instanceof DeliveryTimeEntity)
            return $deliveryTimeEntity->getId();

        return "";
    }


    public function getCategoryPayloads($line, $ImportToCategories = 'Catalogue #1')
    {

        if ($this->logLevel == 1) {
            $this->createLog("Create Category START");
        }

        if (isset($line['attributes']['subcategory']) && isset($line['attributes']['category'])) {
            $line['category_name'] = $line['attributes']['category'] . '|' . $line['attributes']['subcategory'];
        } else  if ((!isset($line['attributes']['subcategory'])) && isset($line['attributes']['category'])) {
            $line['category_name'] = $line['attributes']['category'];
        }


        $categoryArray = [];
        $extraCharsToRemove = array("\"", "'", "`", "^", "~");


        if (!isset($line['parent_category'])) {
            $line['parent_category'] = $ImportToCategories;
        } else {
            $line['parent_category'] = str_replace($extraCharsToRemove, "", iconv("utf-8", "ASCII//TRANSLIT", $line['parent_category']));
        }


        $parentCategoryID = $this->getCategoryIdByName($line['parent_category']);
        if ($this->logLevel == 1) {
            $this->createLog("parentCategoryID " . $parentCategoryID);
        }


        if (isset($line['category_name'])) {
            $line['category_name'] = str_replace($extraCharsToRemove, "", iconv("utf-8", "ASCII//TRANSLIT",  $line['category_name']));

            $categoryNames = explode("|", $line['category_name']);
            foreach ($categoryNames as $categoryName) {
                $categoryID = $this->getCategoryIdByName($categoryName, $parentCategoryID);
                if ($categoryID != "") {
                    $categoryArray[] = ["id" => $categoryID];
                } else {
                    $categoryID = Uuid::randomHex();
                    if ($parentCategoryID) {
                        $categoryArray[] = ['id' => $categoryID, 'name' => $categoryName, 'parentId' => $parentCategoryID];
                    } else {
                        $categoryArray[] = ['id' => $categoryID, 'name' => $categoryName];
                    }
                }
                $parentCategoryID = $categoryID;
            }
        } else {
            $categoryArray = [
                ["id" => $parentCategoryID]
            ];
        }

        return $categoryArray;
    }






    /**
     * @param $imageUrl
     * @param Context $context
     * @return string|null
     */
    public function addImageToProductMedia($imageUrl, Context $context)
    {

        if ($this->logLevel == 1) {
            $this->createLog("adding Image To Product Media :  " . print_r($imageUrl, true));
        }



        $mediaId = NULL;
        $context->disableInheritance(function (Context $context) use ($imageUrl, &$mediaId): void {
            $filePathParts = explode('/', $imageUrl);
            $fileName = array_pop($filePathParts);
            $fileNameParts = explode('.', $fileName);

            $mediaPath = pathinfo($imageUrl);
            if ($mediaPath) {
                $mediaName = $mediaPath['filename'];
                $actualFileName = str_replace(".", "", str_replace("%", "", str_replace("%20", "", $mediaName)));
            }
            if (isset($fileNameParts[count($fileNameParts) - 1])) {
                $fileExtension = trim($fileNameParts[count($fileNameParts) - 1]);
            } else {
                $fileExtension = "png";
            }
            if ($this->logLevel == 1) {
                $this->createLog("Media file name in system : " . $actualFileName . "-" . $fileExtension);
            }


            if ($actualFileName && $fileExtension && @getimagesize($imageUrl)) {
                $tempFile = tempnam(sys_get_temp_dir(), 'image-import');
                file_put_contents($tempFile, file_get_contents($imageUrl));

                $fileSize = filesize($tempFile);
                $mimeType = mime_content_type($tempFile);

                $mediaFile = new MediaFile($tempFile, $mimeType, $fileExtension, $fileSize);
                $mediaId = $this->mediaService->createMediaInFolder('product', $context, false);

                $this->fileSaver->persistFileToMedia(
                    $mediaFile,
                    $actualFileName,
                    $mediaId,
                    $context
                );
            }
        });
        return $mediaId;
    }


    public function getTaxId(): string
    {
        $result = $this->connection->fetchColumn('
            SELECT LOWER(HEX(COALESCE(
                (SELECT `id` FROM `tax` WHERE tax_rate = "19.00" LIMIT 1),
	            (SELECT `id` FROM `tax`  LIMIT 1)
            )))
        ');

        if (!$result) {
            throw new \RuntimeException('No tax found, please make sure that basic data is availabel by running the migrations.');
        }

        return (string) $result;
    }



    /**
     * @param array $ruleIds
     * @return Context
     */
    public function createContextWithRules(array $ruleIds = []): Context
    {
        return new Context(new SystemSource(), $ruleIds, Defaults::CURRENCY, [Defaults::LANGUAGE_SYSTEM], Defaults::LIVE_VERSION, 1.0);
        //return new Context(new SystemSource(), $ruleIds, Defaults::CURRENCY, [Defaults::LANGUAGE_SYSTEM], Defaults::LIVE_VERSION, 1.0, 2, true);
    }

    public function setIniConfig()
    {
        set_time_limit(360);
        ini_set('memory_limit', '14048M');
        ini_set('max_execution_time', '360');
    }

    /**
     * @param $message
     */
    public function createLog($message)
    {
        $this->lastlog = $this->lastlog . "[ " . date("Y-m-d H:i:s") . " ]:" . $message . " \n";
        file_put_contents($this->logFileName, "[ " . date("Y-m-d H:i:s") . " ]:" . $message . " \r\n", FILE_APPEND);
    }

    public function getFileContents($file)
    {
        if (!file_exists($file)) {
            file_put_contents($file, "");
        }
        return (string) file_get_contents($file);
    }

    public function CleanLastLog()
    {
        if (!file_exists($this->logFileName)) {
            return true;
        } else {
            return unlink($this->logFileName);
        }
    }
}
