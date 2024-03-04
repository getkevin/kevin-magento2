<?php

namespace Kevin\Payment\Model\ResourceModel;

class PaymentMethods extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('kevin_payment_list', 'id');
    }
}
