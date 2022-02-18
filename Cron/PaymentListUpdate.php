<?php

namespace Kevin\Payment\Cron;

class PaymentListUpdate
{
    /**
     * @var \Kevin\Payment\Api\Kevin
     */
    protected $api;

    /**
     * @var \Kevin\Payment\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Kevin\Payment\Api\Kevin $api
     * @param \Kevin\Payment\Helper\Data $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Kevin\Payment\Api\Kevin $api,
        \Kevin\Payment\Helper\Data $helper
    )
    {
        $this->api = $api;
        $this->helper = $helper;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $kevinMethods = $this->api->getPaymentMethods();

        if (is_array($kevinMethods) && !empty($kevinMethods)) {
            if (in_array('bank', $kevinMethods)) {
                $this->helper->saveAvailablePaymentList($this->api->getBanks());
                $this->helper->saveAvailableCountryList($this->api->getAvailableCountries());
            }
        }
    }
}
