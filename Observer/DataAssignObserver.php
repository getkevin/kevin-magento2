<?php

namespace Kevin\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class DataAssignObserver.
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    public function execute(Observer $observer)
    {
        $method = $this->readMethodArgument($observer);
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getDataByKey(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $method->getInfoInstance();

        if (isset($additionalData['bank_code'])) {
            $paymentInfo->setAdditionalInformation(
                'bank_code',
                $additionalData['bank_code']
            );
        }

        if (isset($additionalData['bank_name'])) {
            $paymentInfo->setAdditionalInformation(
                'bank_name',
                $additionalData['bank_name']
            );
        }
    }
}
