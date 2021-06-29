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
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * ConfigProvider constructor.
     * @param \Kevin\Payment\Api\Kevin $api
     * @param \Kevin\Payment\Gateway\Config\Config $config
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Checkout\Model\Cart $cart
     */
    public function __construct(
        \Kevin\Payment\Api\Kevin $api,
        \Kevin\Payment\Gateway\Config\Config $config,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->_assetRepo = $assetRepo;
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
    }

    /**
     * @return \array[][]
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'show_banks' => $this->config->getPaymentList(),
                    'banks' => $this->getBanks(),
                    'redirectUrl' => 'kevin/payment/redirect',
                    'code' => self::CODE
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function getBanks(){
        if($this->config->getPaymentList()) {
            $paymentMethods = [];

            $methods = $this->api->getPaymentMethods();
            if (in_array("bank", $methods)){
                $bankList = $this->api->getBanks();
                foreach ($bankList as $bank) {
                    $subMethodCode = self::CODE . '_' . $bank['id'];
                    $paymentMethods[$bank['countryCode']][] = [
                        'id' => $bank['id'],
                        'methodCode' => $subMethodCode,
                        'title' => $bank['name'],
                        'description' => !empty($bank['officialName']) ? $bank['officialName'] : '',
                        'logoPath' => $bank['imageUri']
                    ];
                }
            }

            if (in_array("card", $methods)){
                $subMethodCode = self::CODE . '_card';
                $paymentMethods['card'] = [
                    'id' => 'card',
                    'methodCode' => $subMethodCode,
                    'title' => 'Credit/Debit card',
                    'description' => '',
                    'logoPath' => $this->_assetRepo->getUrl("Kevin_Payment::images/credit_card.png")
                ];
            }

            return $paymentMethods;
        }
    }


}