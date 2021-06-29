<?php
namespace Kevin\Payment\Observer;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Payment\Model\InfoInterface;

/**
 * Class DataAssignObserver
 * @package Kevin\Payment\Observer
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @param Observer $observer
     */
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
