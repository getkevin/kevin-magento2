<?php

namespace Kevin\Payment\Model\ResourceModel\PaymentMethods;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Identification field.
     *
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Connect model with resource model.
     */
    protected function _construct()
    {
        $this->_init(
            \Kevin\Payment\Model\PaymentMethods::class,
            \Kevin\Payment\Model\ResourceModel\PaymentMethods::class
        );
    }
}
