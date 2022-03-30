<?php

namespace Kevin\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class QuoteSubmitSuccess implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getData('quote');
        $order = $observer->getEvent()->getData('order');
        if ($order->getPayment()->getMethodInstance()->getCode() == \Kevin\Payment\Model\Ui\ConfigProvider::CODE) {
            $quote->setIsActive(true);
        }
    }
}
