<?php

namespace Kevin\Payment\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class PaymentListButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Kevin_Payment::system/config/payment_button.phtml';

    /**
     * @var \Kevin\Payment\Model\PaymentMethodsFactory
     */
    protected $paymentMethodsFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Kevin\Payment\Model\PaymentMethodsFactory $paymentMethodsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->paymentMethodsFactory = $paymentMethodsFactory;
        $this->timezone = $timezone;

        parent::__construct($context, $data);
    }

    /**
     * Remove scope label.
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Return element html.
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for custom button.
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('kevin/system_config/paymentListUpdate');
    }

    /**
     * @throws LocalizedException
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'kevin_payment_button',
                'label' => __('Update Now'),
            ]
        );

        return $button->toHtml();
    }

    /**
     * @return false
     */
    public function getLastUpdateDate()
    {
        $rowItem = $this->paymentMethodsFactory->create()
            ->getCollection()
            ->getFirstItem();

        if ($rowItem && $rowItem->getCreatedAt()) {
            return $this->timezone->date(new \DateTime($rowItem->getCreatedAt()))->format('Y-m-d H:i:s');
        }

        return false;
    }
}
