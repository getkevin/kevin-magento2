<?php
namespace Kevin\Payment\Block\Adminhtml\Form\Field;

use Kevin\Payment\Block\Adminhtml\Form\Field\Accounts\CountryColumn;
use Kevin\Payment\Block\Adminhtml\Form\Field\Accounts\BankColumn;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class Accounts extends AbstractFieldArray
{
    /**
     * @var \Kevin\Payment\Model\PaymentMethodsFactory
     */
    protected $paymentMethodsFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $json;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Kevin\Payment\Model\PaymentMethodsFactory $paymentMethodsFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Kevin\Payment\Model\PaymentMethodsFactory $paymentMethodsFactory,
        \Magento\Framework\Serialize\Serializer\Json $json,
        array $data = []
    ){
        $this->paymentMethodsFactory = $paymentMethodsFactory;
        $this->json = $json;

        parent::__construct($context, $data);
    }

    /**
     * @var CountryColumn
     */
    private $countryRenderer;

    /**
     * @var BankColumn
     */
    private $bankRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('country_id', [
            'label' => __('Country'),
            'renderer' => $this->getCountryRenderer()
        ]);
        $this->addColumn('bank', [
            'label' => __('Bank'),
            'renderer' => $this->getBankRenderer()
        ]);
        $this->addColumn('company', ['label' => __('Company Name'), 'class' => 'required-entry']);
        $this->addColumn('bank_account', ['label' => __('Bank Account'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $countryId = $row->getCountryId();
        $bank = $row->getBank();
        if ($countryId !== null) {
            $options['option_' . $this->getCountryRenderer()->calcOptionHash($countryId)] = 'selected="selected"';
        }

        if ($bank !== null) {
            $options['option_' . $this->getBankRenderer()->calcOptionHash($bank)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return CountryColumn
     * @throws LocalizedException
     */
    private function getCountryRenderer()
    {
        if (!$this->countryRenderer) {
            $this->countryRenderer = $this->getLayout()->createBlock(
                CountryColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->countryRenderer;
    }

    /**
     * @return BankColumn|\Magento\Framework\View\Element\BlockInterface
     * @throws LocalizedException
     */
    private function getBankRenderer()
    {
        if (!$this->bankRenderer) {
            $this->bankRenderer = $this->getLayout()->createBlock(
                BankColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->bankRenderer;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = parent::_getElementHtml($element);

        $bankList = $this->json->serialize($this->getBanks());

        $script = '<script type="text/javascript">
                require(["jquery"], function ($) {
                    $(document).ready(function($) {
                        var banList = '.$bankList.';
                        $(".country-select").each(function(elem){
                            var selected = $(this).val();
                            var bankElem = $(this).parent().parent("tr").find(".bank-select");
                            if(selected){
                                selectedBank = bankElem.val();
                                bankElem.empty();
                                if(banList[selected].length){
                                    $.each(banList[selected], function (i, p) {
                                        bankElem.append($("<option></option>").val(p.code).html(p.title));
                                    });

                                    bankElem.val(selectedBank);
                                }
                            }
                        });

                        $(document).on("change", ".country-select", function() {
                            var activeCountry = this.value;
                            var bankElem = $(this).parent().parent("tr").find(".bank-select");
                            bankElem.empty();

                            if(banList[activeCountry].length){
                                $.each(banList[activeCountry], function (i, p) {
                                    bankElem.append($("<option></option>").val(p.code).html(p.title));
                                });

                                bankElem.prop("disabled", false);
                            }
                        });

                        $(document).on("click", "#payment_us_kevin_payment_additional_bank_additional_bank_list .action-add", function() {
                            $(this).closest("table").find("tr:last").find(".bank-select").prop("disabled", true);
                        });
                    });
                });
            </script>';
        $html .= $script;
        return $html;
    }

    /**
     * @return array
     */
    protected function getBanks(){
        $collection = $this->paymentMethodsFactory->create()
            ->getCollection();

        $banks = [];
        if($collection->getSize()){
            foreach($collection as $bank){
                $countryId = $bank->getData('country_id');
                $label = $bank->getData('description') ? $bank->getData('description') : $bank->getData('title');
                $banks[$countryId][] = [
                    'code' => $bank->getData('payment_id'),
                    'title' => $label
                ];
            }
        }

        return $banks;
    }
}
