<?php
declare(strict_types=1);

namespace Kevin\Payment\Block\Adminhtml\Form\Field\Accounts;

use Magento\Framework\View\Element\Html\Select;

class BankColumn extends Select
{
    /**
     * @var \Kevin\Payment\Model\PaymentMethodsFactory
     */
    protected $paymentMethodsFactory;

    /**
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Kevin\Payment\Model\PaymentMethodsFactory $paymentMethodsFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Kevin\Payment\Model\PaymentMethodsFactory $paymentMethodsFactory,
        array $data = []
    ){
        $this->paymentMethodsFactory = $paymentMethodsFactory;

        parent::__construct($context, $data);
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }

        $this->setClass('required-entry bank-select');

        return parent::_toHtml();
    }

    /**
     * @return array
     */
    private function getSourceOptions(): array
    {
        $collection = $this->paymentMethodsFactory->create()
            ->getCollection();

        $banks = [];
        $banks[] = [
            'label' => '---',
            'value' => ''
        ];

        if($collection->getSize()){
            foreach($collection as $bank){
                $label = $bank->getData('description') ? $bank->getData('description') : $bank->getData('title');
                $banks[] = [
                    'value' => $bank->getData('payment_id'),
                    'label' => $label
                ];
            }
        }

        return $banks;
    }
}
