<?php

namespace Kevin\Payment\Gateway\Config;

/**
 * Class Config.
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    const CONFIG_PATH_STATUS = 'payment/kevin_payment/status';
    const CONFIG_PATH_COUNTRY_LIST = 'payment/kevin_payment/country_list';

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $moduleResource;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $configWriter;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serialize;

    /**
     * @var string|null
     */
    private $methodCode;

    /**
     * @param string|null $methodCode
     * @param string      $pathPattern
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\Serialize\Serializer\Json $serialize,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->moduleResource = $moduleResource;
        $this->productMetadata = $productMetadata;
        $this->configWriter = $configWriter;
        $this->serialize = $serialize;

        /*
            if we DI this class directly to our other components we need this to be initiated
            in all other case it is initiated trough di.xml
        */
        if (!$methodCode) {
            $methodCode = \Kevin\Payment\Model\Ui\ConfigProvider::CODE;
        }

        $this->methodCode = $methodCode;

        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    public function getStatus()
    {
        return $this->getValue('status');
    }

    public function getActive()
    {
        return $this->getValue('active');
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return (string) $this->getValue('client_id');
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return (string) $this->getValue('client_secret');
    }

    public function getSignature()
    {
        return $this->getValue('signature');
    }

    public function getRedirectPreferred()
    {
        return (int) $this->getValue('extra_settings/redirect_preferred');
    }

    /**
     * @return int
     */
    public function getShowPaymentName()
    {
        return (int) $this->getValue('extra_settings/show_name');
    }

    /**
     * @return int
     */
    public function getCountryList()
    {
        return (int) $this->getValue('extra_settings/show_country_list');
    }

    /**
     * @return int
     */
    public function getPaymentSearch()
    {
        return (int) $this->getValue('extra_settings/show_search');
    }

    public function getPaymentList()
    {
        return (int) $this->getValue('extra_settings/payment_list');
    }

    public function getCompanyName()
    {
        return $this->getValue('default_bank/company_name');
    }

    public function getCompanyBankAccount()
    {
        return $this->getValue('default_bank/company_bank_account');
    }

    public function getAdditionalBankAccounts()
    {
        $value = $this->getValue('additional_bank/additional_bank_list');

        return $value ? $this->serialize->unserialize($value) : '';
    }

    public function getKevinCountryList()
    {
        return $this->getValue('country_list');
    }

    /**
     * @return array
     */
    public function getSystemData()
    {
        return [
            'pluginVersion' => $this->moduleResource->getDbVersion('Kevin_Payment'),
            'pluginPlatform' => 'Magento 2',
            'pluginPlatformVersion' => $this->productMetadata->getVersion(),
        ];
    }

    /**
     * Set module status.
     *
     * @param bool $status
     */
    public function setStatus($status)
    {
        $this->configWriter->save(self::CONFIG_PATH_STATUS, $status);
    }

    /**
     * @return void
     */
    public function setCountryList($countryList)
    {
        $this->configWriter->save(self::CONFIG_PATH_COUNTRY_LIST, $countryList);
    }

    /**
     * @return int
     */
    public function getSendOrderEmailBefore()
    {
        return (int) $this->getValue('email_settings/order_email_before');
    }

    /**
     * @return int
     */
    public function getSendOrderEmailAfter()
    {
        return (int) $this->getValue('email_settings/order_email_after');
    }

    /**
     * @return int
     */
    public function getSendInvoiceEmail()
    {
        return (int) $this->getValue('email_settings/invoice_email');
    }
}
