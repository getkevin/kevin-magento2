<?php

namespace Kevin\Payment\Block\Adminhtml\Form\Field\Accounts;

use Magento\Framework\View\Element\Html\Select;

class CountryColumnViewBuilder extends Select
{
    /**
     * @var \Kevin\Payment\Gateway\Config\Config
     */
    protected $config;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    protected $countryCollectionFactory;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Kevin\Payment\Gateway\Config\Config $config,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        array $data = []
    ) {
        $this->config = $config;
        $this->countryCollectionFactory = $countryCollectionFactory;

        parent::__construct($context, $data);
    }

    /**
     * Set "name" for <select> element.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element.
     *
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        $this->setClass('required-entry country-select');

        return parent::_toHtml();
    }

    /**
     * @return array
     */
    private function getSourceOptions()
    {
        $kevinCountries = $this->config->getKevinCountryList();

        $result = [];
        $result[] = [
            'label' => '---',
            'value' => '',
        ];

        if ($kevinCountries) {
            $collection = $this->countryCollectionFactory->create()
                ->loadByStore()
                ->addFieldToFilter('iso2_code', ['in' => explode(',', $kevinCountries)]);

            $list = [];
            foreach ($collection as $country) {
                $list[$country->getName()] = $country->getId();
            }
            ksort($list);

            foreach ($list as $name => $id) {
                $result[] = [
                    'label' => $name,
                    'value' => $id,
                ];
            }

            foreach ($collection as $country) {
                $list[] = [
                    'label' => $country->getName(),
                    'value' => $country->getId(),
                ];
            }
        }

        return $result;
    }
}
