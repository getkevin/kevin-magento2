<?php

namespace Kevin\Payment\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.9', '<')) {
            $setup->getConnection()->update(
                $setup->getTable('sales_order_status_state'),
                ['state' => 'processing'],
                ['status = ?' => \Kevin\Payment\Helper\Data::ORDER_STATUS_REFUND_PENDING]
            );

            $data = [];
            $data[] = ['status' => \Kevin\Payment\Helper\Data::ORDER_STATUS_REFUND_ERROR, 'label' => 'Refund Error'];
            $setup->getConnection()->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $data);

            $setup->getConnection()->insertArray(
                $setup->getTable('sales_order_status_state'),
                ['status', 'state', 'is_default', 'visible_on_front'],
                [
                    [\Kevin\Payment\Helper\Data::ORDER_STATUS_REFUND_ERROR, 'processing', '0', '1'],
                ]
            );
        }
    }
}

