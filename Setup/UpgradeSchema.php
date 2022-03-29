<?php

namespace Kevin\Payment\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var \Kevin\Payment\Api\Kevin
     */
    protected $api;

    /**
     * @var \Kevin\Payment\Gateway\Config\Config
     */
    protected $config;

    /**
     * @var \Kevin\Payment\Helper\Data
     */
    protected $helper;

    public function __construct(
        \Kevin\Payment\Api\Kevin $api,
        \Kevin\Payment\Gateway\Config\Config $config,
        \Kevin\Payment\Helper\Data $helper
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->helper = $helper;
    }

    /**
     * @return void
     *
     * @throws \Zend_Db_Exception
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.1.2', '<')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('kevin_payment_list'))
                ->addColumn(
                    'id', Table::TYPE_INTEGER, null,
                    ['identity' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                    'ID'
                )
                ->addColumn(
                    'payment_id', Table::TYPE_TEXT, 100,
                    ['nullable' => true],
                    'Payment ID'
                )
                ->addColumn(
                    'country_id', Table::TYPE_TEXT, 100,
                    ['nullable' => true],
                    'Country ID'
                )
                ->addColumn(
                    'title', Table::TYPE_TEXT, 100,
                    ['nullable' => true],
                    'Title'
                )
                ->addColumn(
                    'description', Table::TYPE_TEXT, 100,
                    ['nullable' => true],
                    'Description'
                )
                ->addColumn(
                    'logo_path', Table::TYPE_TEXT, 255,
                    ['nullable' => true],
                    'Logo'
                )
                ->addColumn(
                    'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, 100,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addColumn(
                    'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, 100,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Updated At'
                )->addIndex(
                    $setup->getIdxName('kevin_payment_list', ['country_id']),
                    ['country_id']
                )->setComment('Kevin Payment Methods List');
            $installer->getConnection()->createTable($table);

            $this->updatePaymentList();
        }

        $installer->endSetup();
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function updatePaymentList()
    {
        try {
            if ($this->config->getClientId() && $this->config->getClientSecret()) {
                $kevinMethods = $this->api->getPaymentMethods();

                if (is_array($kevinMethods) && !empty($kevinMethods)) {
                    if (in_array('bank', $kevinMethods)) {
                        $this->helper->saveAvailablePaymentList($this->api->getBanks());
                        $this->helper->saveAvailableCountryList($this->api->getAvailableCountries());
                    }
                }

                $this->config->setStatus(true);
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
