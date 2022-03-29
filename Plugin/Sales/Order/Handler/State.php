<?php

namespace Kevin\Payment\Plugin\Sales\Order\Handler;

class State
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    public function __construct(
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->request = $request;
    }

    /**
     * @param $order
     *
     * @return mixed|void
     */
    public function aroundCheck(
        \Magento\Sales\Model\ResourceModel\Order\Handler\State $subject, \Closure $proceed, $order
    ) {
        if (
            $order->getPayment()->getMethodInstance()->getCode() != \Kevin\Payment\Model\Ui\ConfigProvider::CODE
            || $order->getStatus() != \Kevin\Payment\Helper\Data::ORDER_STATUS_REFUND_PENDING) {
            $originalResult = $proceed($order);

            return $originalResult;
        }
    }
}
