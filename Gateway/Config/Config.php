<?php
namespace Kevin\Payment\Gateway\Config;

/**
 * Class Config
 * @package Kevin\Payment\Gateway\Config
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
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
     * Config constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Module\ResourceInterface $moduleResource
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->moduleResource = $moduleResource;
        $this->productMetadata = $productMetadata;

        //if we DI this class directly to our other components we need this to be initiated
        //in all other case it is initiated trough di.xml
        if (!$methodCode) {
            $methodCode = \Kevin\Payment\Model\Ui\ConfigProvider::CODE;
        }

        $this->methodCode = $methodCode;

        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    /**
     * @return mixed
     */
    public function getActive(){
        return $this->getValue('active');
    }

    /**
     * @return mixed
     */
    public function getClientId(){
        return $this->getValue('client_id');
    }

    /**
     * @return mixed
     */
    public function getClientSecret(){
        return $this->getValue('client_secret');
    }

    /**
     * @return mixed
     */
    public function getRedirectPreferred(){
        return $this->getValue('redirect_preferred');
    }

    /**
     * @return mixed
     */
    public function getPaymentList(){
        return $this->getValue('payment_list');
    }

    /**
     * @return mixed
     */
    public function getCompanyName(){

        $search = array('~','`','/','!','@','#','¬','£','$','%','^','&','(',')','_','=','{','}','[',']',':',';',',','<','>','+','?');
        $company = str_replace($search, '', $this->getValue('company_name'));
        return $company;
    }

    /**
     * @return mixed
     */
    public function getCompanyBankAccount(){
        return $this->getValue('company_bank_account');
    }

    /**
     * @return array
     */
    public function getSystemData(){
        return [
            'pluginVersion' => $this->moduleResource->getDbVersion('Kevin_Payment'),
            'pluginPlatform' => 'Magento 2',
            'pluginPlatformVersion' => $this->productMetadata->getVersion()
        ];
    }
}
