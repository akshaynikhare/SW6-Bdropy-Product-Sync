<?php

declare(strict_types=1);

namespace slox_product_sync\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1673602566 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1673602566;
    }

    /**
     * @param Connection $connection
     *
     * @throws DBALException
     */
    public function update(Connection $connection): void
    {
        $this->createProductForVoucherTable($connection);
    }


    /**
     * @param Connection $connection
     *
     * @throws DBALException
     */
    public function createProductForVoucherTable(Connection $connection): void
    {
        $sql = <<<EOL
CREATE TABLE IF NOT EXISTS `slox_BDropy_Product_extension` (
    `id` BINARY(16) NOT NULL,
    `imported_on` DATETIME(3) NULL,
    `last_updated` DATETIME(3) NULL,
    `import_json` json NULL,
    `update_json` json NULL,

    `product_id` BINARY(16) NULL,
    `product_version_id` BINARY(16) NULL,
    `updated_at` DATETIME(3) NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.slox_BDropy_Product_extension.value` CHECK (JSON_VALID(`import_json`)),
    CONSTRAINT `json.slox_BDropy_Product_extension.value` CHECK (JSON_VALID(`update_json`)),
    KEY `fk.slox_BDropy_Product_extension.product_id` (`product_id`,`product_version_id`),
    CONSTRAINT `fk.slox_BDropy_Product_extension.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
EOL;
        $connection->executeStatement($sql);
    }


    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
