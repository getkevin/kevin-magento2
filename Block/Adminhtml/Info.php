<?php

namespace Kevin\Payment\Block\Adminhtml;

/**
 * Class Info.
 */
class Info extends \Magento\Payment\Block\Info
{
    /**
     * @param null $transport
     *
     * @return mixed
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);

        $data = [];
        $additionalData = $this->getInfo()->getAdditionalInformation();
        if (!empty($additionalData['bank_name'])) {
            $test = (string) __('Bank');
            $data[$test] = $additionalData['bank_name'];
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
