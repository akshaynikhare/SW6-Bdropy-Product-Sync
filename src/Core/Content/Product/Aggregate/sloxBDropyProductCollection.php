<?php

declare(strict_types=1);

namespace slox_product_sync\Core\Content\Product\Aggregate;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method sloxBDropyProductCollection filterByProperty(string $property, $value)
 */
class sloxBDropyProductCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return sloxBDropyProductEntity::class;
    }
}
