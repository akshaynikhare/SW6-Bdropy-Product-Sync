<?php

declare(strict_types=1);

namespace slox_product_sync\Core\Content\Product;

use slox_product_sync\Core\Content\Product\Aggregate\sloxBDropyProductDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;


class ProductExtensionDefinition extends EntityExtension
{

    public const EXTENSION_NAME = 'sloxBDropyProduct';

    public function extendFields(FieldCollection $collection): void
    {
        
        $collection->add(
            (new OneToOneAssociationField(
                self::EXTENSION_NAME,
                'id',
                'product_id',
                sloxBDropyProductDefinition::class
            ))
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }
}
