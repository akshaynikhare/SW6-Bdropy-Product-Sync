<?php declare(strict_types=1);

namespace slox_product_sync;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class slox_product_sync extends Plugin
{

    // public function install(InstallContext $context): void
    // {
       
    // }


    // public function activate(ActivateContext $context): void
    // {
    //    parent::activate($context);
    // }

    // public function deactivate(DeactivateContext $context): void
    // {
    //     parent::deactivate($context);
    // }

    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);
    }
}
