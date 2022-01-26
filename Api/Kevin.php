<?php

namespace Kevin\Payment\Api;

use Kevin\Client;
use Kevin\SecurityManager;

/**
 * Class Kevin
 * @package Kevin\Payment\Api
 */
class Kevin
{
    /**
     * Signature verify timeout in milliseconds
     */
    const SIGNATURE_VERIFY_TIMEOUT = 300000;

    /**
     * @var \Kevin\Payment\Gateway\Config\Config
     */
    protected $config;

    /**
     * @var
     */
    protected $banks;

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $moduleResource;

    /**
     * Kevin constructor.
     * @param \Kevin\Payment\Gateway\Config\Config $config
     * @param \Magento\Framework\Module\ResourceInterface $moduleResource
     */
    public function __construct(
        \Kevin\Payment\Gateway\Config\Config $config,
        \Magento\Framework\Module\ResourceInterface $moduleResource
    ) {
        $this->config = $config;
        $this->moduleResource = $moduleResource;
    }

    /**
     * @param $clientId
     * @param $clientSecret
     * @return Client|void
     */
    public function getConnection($clientId = null, $clientSecret = null){
        $options = [
            'error' => 'exception',
            'version' => '0.3'
        ];

        $options = array_merge($options, $this->config->getSystemData());

        return new \Kevin\Client($clientId, $clientSecret, $options);
    }

    /**
     * @return Client|void
     */
    public function getClient()
    {
        $clientId = $this->config->getClientId();
        $clientSecret = $this->config->getClientSecret();

        $client = $this->getConnection($clientId, $clientSecret);
        return $client;
    }

    /**
     * @return array
     */
    public function getProjectSettings(){
        try {
            $methods = $this->getClient()->auth()->getProjectSettings();

            return $methods;
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @return array|mixed|void
     */
    public function getAllowedRefund(){
        try {
            $settings = $this->getProjectSettings();
            if(isset($settings['allowedRefundsFor'])){
                return $settings['allowedRefundsFor'];
            }
            return [];
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @return array
     */
    public function getPaymentMethods(){
        try {
            $settings = $this->getProjectSettings();
            if(isset($settings['paymentMethods'])){
                return $settings['paymentMethods'];
            }
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @param null $country
     * @return array|mixed
     */
    public function getBanks($country = null)
    {
        try {
            if(!$this->banks) {
                $params = [];
                if($country){
                    $params = ['countryCode' => $country];
                }
                $kevinAuth = $this->getClient()->auth();

                $banks = $kevinAuth->getBanks($params);
                if(isset($banks['data'])){
                    $this->banks = $banks['data'];
                }
            }

            return $this->banks;
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @param $bankId
     * @return array
     */
    public function getBank($bankId){
        try {
            $kevinAuth = $this->getClient()->auth();
            $bank = $kevinAuth->getBank($bankId);

            return $bank;
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function initPayment($params){
       return $this->getClient()->payment()->initPayment($params);
    }

    /**
     * @param $paymentId
     * @param $attr
     * @return mixed
     */
    public function getPaymentStatus($paymentId, $attr){
        return $this->getClient()->payment()->getPaymentStatus($paymentId, $attr);
    }

    /**
     * @param $paymentId
     * @param $attr
     * @return mixed
     */
    public function getPayment($paymentId, $attr){
        return $this->getClient()->payment()->getPayment($paymentId, $attr);
    }

    /**
     * @return array
     */
    public function getAvailableCountries(){
        try {
            $kevinAuth = $this->getClient()->auth();
            $response = $kevinAuth->getCountries();

            if(isset($response['data'])){
                return $response['data'];
            }
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @param $paymentId
     * @param $attr
     * @return mixed
     */
    public function initRefund($paymentId, $attr){
        return $this->getClient()->payment()->initiatePaymentRefund($paymentId, $attr);
    }

    /**
     * @param $paymentId
     * @return mixed|void
     */
    public function getRefunds($paymentId){
        $response = $this->getClient()->payment()->getPaymentRefunds($paymentId);
        if(isset($response['data'])){
            return $response['data'];
        }
    }

    /**
     * @param $endpointSecret
     * @param $requestBody
     * @param $headers
     * @param $webhookUrl
     * @return mixed
     */
    public function verifySignature($endpointSecret, $requestBody, $headers, $webhookUrl){
        $timestampTimeout = self::SIGNATURE_VERIFY_TIMEOUT;
        $isValid = SecurityManager::verifySignature($endpointSecret, $requestBody, $headers, $webhookUrl, $timestampTimeout);

        return $isValid;
    }
}
