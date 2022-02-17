<?php

namespace Kevin\Payment\Plugin\Sales;

class Order
{
    /**
     * @var \Kevin\Payment\Gateway\Config\Config
     */
    protected $config;

    /**
     * @param \Kevin\Payment\Gateway\Config\Config $config
     */
    public function __construct(
        \Kevin\Payment\Gateway\Config\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param \Magento\Sales\Model\Order $subject
     * @param $result
     * @return mixed
     */
    public function afterGetCanSendNewEmailFlag(
        \Magento\Sales\Model\Order $subject,
                                   $result
    ) {
        if($subject->getPayment()->getMethodInstance()->getCode() == \Kevin\Payment\Model\Ui\ConfigProvider::CODE
            && !$this->config->getSendOrderEmailBefore()){
            return false;
        }

        return $result;
    }
}
