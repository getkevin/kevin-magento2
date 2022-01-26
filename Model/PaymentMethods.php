<?php

namespace Kevin\Payment\Model;

class PaymentMethods extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \Kevin\Payment\Model\ResourceModel\PaymentMethods::class
        );
    }
}
