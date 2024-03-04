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
            ResourceModel\PaymentMethods::class
        );
    }
}
