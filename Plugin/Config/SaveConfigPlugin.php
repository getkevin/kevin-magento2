<?php

namespace Kevin\Payment\Plugin\Config;

class SaveConfigPlugin
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
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Kevin\Payment\Helper\Data
     */
    protected $helper;

    public function __construct(
        \Kevin\Payment\Api\Kevin $api,
        \Kevin\Payment\Gateway\Config\Config $config,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Kevin\Payment\Helper\Data $helper
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->messageManager = $messageManager;
        $this->helper = $helper;
    }

    /**
     * @return void
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function beforeSave(
        \Magento\Config\Model\Config $subject
    ) {
        if (array_key_exists('kevin_payment', $subject->getData()['groups'])) {
            $enabled = null;
            $clientId = null;
            $clientSecret = null;

            if (isset($subject->getData()['groups']['kevin_payment']['fields']['active']['value'])) {
                $enabled = $subject->getData()['groups']['kevin_payment']['fields']['active']['value'];
            }

            if ($subject->getSection() === 'payment' && $enabled) {
                // client ID - new value ( not saved )
                if (isset($subject->getData()['groups']['kevin_payment']['fields']['client_id']['value'])) {
                    $clientId = $subject->getData()['groups']['kevin_payment']['fields']['client_id']['value'];
                }

                // Client Secret - new value ( not saved )
                if (isset($subject->getData()['groups']['kevin_payment']['fields']['client_secret']['value'])) {
                    $clientSecret = $subject->getData()['groups']['kevin_payment']['fields']['client_secret']['value'];
                }

                if ($clientId && $clientSecret) {
                    if (str_contains($clientSecret, '***')) {
                        $clientSecret = $this->config->getClientSecret();
                    }

                    if (
                        $clientId != $this->config->getClientId()
                        || $clientSecret != $this->config->getClientSecret()
                        || $enabled != $this->config->getActive()
                    ) {
                        try {
                            $kevinConnection = $this->api->getConnection($clientId, $clientSecret);
                            if ($kevinMethods = $kevinConnection->auth()->getPaymentMethods()) {
                                if (isset($kevinMethods['data'])) {
                                    if (in_array('bank', $kevinMethods['data'])) {
                                        $bankList = $kevinConnection->auth()->getBanks();
                                        $countryList = $kevinConnection->auth()->getCountries();

                                        if (isset($bankList['data'])) {
                                            $this->helper->saveAvailablePaymentList($bankList['data']);
                                        }

                                        if (isset($countryList['data'])) {
                                            $this->helper->saveAvailableCountryList($countryList['data']);
                                        }

                                        $this->messageManager->addSuccessMessage(__('Kevin Payment: Bank list was updated.'));
                                    }
                                }

                                $this->config->setStatus(true);
                            }
                        } catch (\Exception $e) {
                            $this->config->setStatus(0);
                        }
                    }
                }
            }
        }
    }
}
