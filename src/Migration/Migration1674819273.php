<?php

declare(strict_types=1);

namespace slox_product_sync\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1674819273 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1674819273;
    }

    /**
     * @param Connection $connection
     *
     * @throws DBALException
     */
    public function update(Connection $connection): void
    {
        $this->createSyncStatusTable($connection);
        $this->createSyncLogTable($connection);
    }


    /**
     * @param Connection $connection
     *
     * @throws DBALException
     */
    public function createSyncStatusTable(Connection $connection): void
    {
        $sql = <<<EOL
CREATE TABLE IF NOT EXISTS `slox_BDropy_Sync_Status` (
    `id` BINARY(16) NOT NULL,
    `task_type` varchar(100) NOT NULL,
    `started_by` varchar(100) NOT NULL,
    `pending_json`  longblob NULL,
    `updated_at` DATETIME(3) NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
EOL;
        $connection->executeStatement($sql);
    }

    /**
     * @param Connection $connection
     *
     * @throws DBALException
     */
    public function createSyncLogTable(Connection $connection): void
    {
        $sql = <<<EOL
        CREATE TABLE IF NOT EXISTS `slox_BDropy_Sync_Log` (
            `id` int NOT NULL AUTO_INCREMENT  ,
            `task_id` binary(16) NOT NULL ,
            `task_type` varchar(100) NOT NULL,
            `date_time` DATETIME(3) NOT NULL,
            `log`  longtext NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        EOL;

        $connection->executeStatement($sql);
    }


    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
