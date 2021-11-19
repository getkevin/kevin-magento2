<?php

namespace Kevin\Payment\Helper;

/**
 * Class Data
 * @package Kevin\Payment\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ORDER_STATUS_REFUND_PENDING = 'refund_pending';
    const ORDER_STATUS_REFUND_ERROR = 'refund_error';
}