<?php

namespace Kevin\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 * @package Kevin\Payment\Model\Ui
 */
final class ConfigProvider implements ConfigProviderInterface
{
    /**
     * payment method code
     */
    const CODE = 'kevin_payment';

    /**
     * @var \Kevin\Payment\Api\Kevin
     */
    protected $api;

    /**
     * @var \Kevin\Payment\Gateway\Config\Config
     */
    protected $config;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    protected $countryCollectionFactory;

    /**
     * @var \Kevin\Payment\Model\PaymentMethodsFactory
     */
    protected $paymentMethodsFactory;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * @var \Kevin\Payment\Helper\Data
     */
    protected $helper;

    /**
     * @param \Kevin\Payment\Api\Kevin $api
     * @param \Kevin\Payment\Gateway\Config\Config $config
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Kevin\Payment\Model\PaymentMethodsFactory $paymentMethodsFactory
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Kevin\Payment\Helper\Data $helper
     */
    public function __construct(
        \Kevin\Payment\Api\Kevin $api,
        \Kevin\Payment\Gateway\Config\Config $config,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Kevin\Payment\Model\PaymentMethodsFactory $paymentMethodsFactory,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Kevin\Payment\Helper\Data $helper
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->paymentMethodsFactory = $paymentMethodsFactory;
        $this->_assetRepo = $assetRepo;
        $this->helper = $helper;
    }

    /**
     * @return \array[][]
     */
    public function getConfig()
    {
        $this->api->getProjectSettings();
        return [
            'payment' => [
                self::CODE => [
                    'show_banks' => $this->config->getPaymentList(),
                    'show_name' => $this->config->getShowPaymentName(),
                    'show_country_list' => $this->config->getCountryList(),
                    'show_search' => $this->config->getPaymentSearch(),
                    'banks' => $this->getBanks(),
                    'countries' => $this->getAvailableCountries(),
                    'redirectUrl' => 'kevin/payment/redirect',
                    'code' => self::CODE
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function getBanks()
    {
        if ($this->config->getPaymentList()) {

            $kevinMethods = $this->api->getPaymentMethods();
            $paymentMethods = [];

            if ($kevinMethods) {
                if (in_array('bank', $kevinMethods)) {
                    $collection = $this->paymentMethodsFactory->create()
                        ->getCollection();

                    if (!$collection->getSize()) {
                        $this->helper->saveAvailablePaymentList($this->api->getBanks());

                        $collection = $this->paymentMethodsFactory->create()
                            ->getCollection();
                    }

                    if ($collection->getSize()) {
                        foreach ($collection as $method) {
                            $subMethodCode = self::CODE . '_' . $method->getData('payment_id');
                            $countryId = $method->getData('country_id');
                            $paymentMethods[$countryId][] = [
                                'id' => $method->getData('payment_id'),
                                'methodCode' => $subMethodCode,
                                'title' => $method->getData('title'),
                                'description' => $method->getData('description'),
                                'logoPath' => $method->getData('logo_path')
                            ];
                        }
                    }
                }

                if (in_array("card", $kevinMethods)) {
                    $subMethodCode = self::CODE . '_card';
                    $paymentMethods['card'] = [
                        'id' => 'card',
                        'methodCode' => $subMethodCode,
                        'title' => 'Credit/Debit card',
                        'description' => '',
                        'logoPath' => $this->_assetRepo->getUrl("Kevin_Payment::images/credit_card.png")
                    ];
                }
            }

            return $paymentMethods;
        }
    }

    /**
     * @return array
     */
    public function getAvailableCountries()
    {
        $kevinCountries = $this->config->getKevinCountryList();

        $result = [];
        if ($kevinCountries) {
            $collection = $this->countryCollectionFactory->create()
                ->loadByStore()
                ->addFieldToFilter('iso2_code', ['in' => explode(',', $kevinCountries)]);

            $list = [];
            foreach ($collection as $country) {
                $list[$country->getName()] = $country->getId();
            }
            ksort($list);

            foreach ($list as $name => $id) {
                $result[] = [
                    'label' => $name,
                    'value' => $id
                ];
            }

            foreach ($collection as $country) {
                $list[] = [
                    'label' => $country->getName(),
                    'value' => $country->getId()
                ];
            }
        }

        return $result;
    }
}
