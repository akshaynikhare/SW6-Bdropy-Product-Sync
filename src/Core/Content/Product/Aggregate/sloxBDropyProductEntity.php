<?php

declare(strict_types=1);

namespace  slox_product_sync\Core\Content\Product\Aggregate;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class sloxBDropyProductEntity extends Entity
{
    use EntityIdTrait;



    /**
     * @var ProductEntity|null
     */
    protected $product;

    /**
     * @var string|null
     */
    protected $productId;

    protected ?\DateTimeInterface  $importedOn = null;
    protected ?\DateTimeInterface  $lastUpdated = null;

    /**
     * @var array<string>|null
     */
    protected ?array $importJson = null;
    /**
     * @var array<string>|null
     */
    protected ?array $updateJson = null;


    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }

    public function getImportedOn(): ?\DateTimeInterface
    {
        return $this->importedOn;
    }

    public function setImportedOn(?\DateTimeInterface $importedOn): void
    {
        $this->importedOn = $importedOn;
    }

    public function getLastUpdated(): ?\DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(?\DateTimeInterface $lastUpdated): void
    {
        $this->lastUpdated = $lastUpdated;
    }

    /**
     *
     * @return array<string>|null
     */
    public function getImportJson(): ?array
    {
        return $this->importJson;
    }

    /**
     *
     * @param array<string>|null $newsletterSalesChannelIds
     */
    public function setImportJson(?array $importJson): void
    {
        $this->importJson = $importJson;
    }

    /**
     *
     * @return array<string>|null
     */
    public function getUpdateJson(): ?array
    {
        return $this->updateJson;
    }

    /**
     *
     * @param array<string>|null $newsletterSalesChannelIds
     */
    public function setUpdateJson(?array $updateJson): void
    {
        $this->updateJson = $updateJson;
    }

}
