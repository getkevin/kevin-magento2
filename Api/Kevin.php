<?php

namespace Kevin\Payment\Api;

use Kevin\Client;

/**
 * Class Kevin
 * @package Kevin\Payment\Api
 */
class Kevin
{
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
     * @return Client
     * @throws \Kevin\KevinException
     */
    public function getClient()
    {
        $options = [
            'error' => 'exception',
            'version' => '0.3'
        ];

        $options = array_merge($options, $this->config->getSystemData());

        return new \Kevin\Client($this->config->getClientId(), $this->config->getClientSecret(), $options);
    }

    /**
     * @return array
     */
    public function getPaymentMethods(){
        try {
            $methods = $this->getClient()->auth()->getPaymentMethods();
            if(isset($methods['data'])){
                return $methods['data'];
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
     * @return array
     * @throws \Exception
     */
    public function initPayment($params){
        try {
            $response = $this->getClient()->payment()->initPayment($params);

            return $response;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param $paymentId
     * @param $attr
     * @return array
     * @throws \Exception
     */
    public function getPaymentStatus($paymentId, $attr){
        try {
            $response = $this->getClient()->payment()->getPaymentStatus($paymentId, $attr);
            return $response;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param $paymentId
     * @param $attr
     * @return array
     * @throws \Exception
     */
    public function getPayment($paymentId, $attr){
        try {
            $response = $this->getClient()->payment()->getPayment($paymentId, $attr);
            return $response;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @return array
     */
    public function getAvailableCountries(){
        try {
            $kevinAuth = $this->getClient()->auth();
            $response = $kevinAuth->getCountries();
            return $response;
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @param $paymentId
     * @param $attr
     * @return mixed
     * @throws \Exception
     */
    public function initRefund($paymentId, $attr){
        try {
            $response = $this->getClient()->payment()->initiatePaymentRefund($paymentId, $attr);
            return $response;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param $paymentId
     * @return mixed
     * @throws \Exception
     */
    public function getRefunds($paymentId){
        try {
            $response = $this->getClient()->payment()->getPaymentRefunds($paymentId);
            if(isset($response['data'])){
                return $response['data'];
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}