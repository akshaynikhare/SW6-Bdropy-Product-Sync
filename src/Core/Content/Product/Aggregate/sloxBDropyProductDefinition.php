<?php

declare(strict_types=1);

namespace slox_product_sync\Core\Content\Product\Aggregate;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\System\Tax\TaxDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;




class sloxBDropyProductDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'slox_BDropy_Product_extension';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return sloxBDropyProductCollection::class;
    }

    public function getEntityClass(): string
    {
        return sloxBDropyProductEntity::class;
    }


    protected function defineFields(): FieldCollection
    {
        return new FieldCollection(
            [
                (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),

                
                (new DateTimeField('imported_on', 'importedOn')),
                (new DateTimeField('last_updated', 'lastUpdated')),
                (new JsonField('import_json', 'importJson')),
                (new JsonField('update_json', 'updateJson')),

                new FkField('product_id', 'productId', ProductDefinition::class),
                new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id'),
                new ReferenceVersionField(ProductDefinition::class, 'product_version_id'),
                
                new UpdatedAtField(),
                new CreatedAtField(),
            ]
        );
    }
}
