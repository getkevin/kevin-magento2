<?php

namespace Kevin\Payment\Model\Config\Source;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $_directoryHelper;

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $_moduleResource;

    /**
     * Version constructor.
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        array $data = []
    ) {
        $this->_directoryHelper = $directoryHelper;
        $this->_moduleResource = $moduleResource;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = parent::_getElementHtml($element);
        $version = $this->_moduleResource->getDbVersion('Kevin_Payment');
        $html .= '<strong>'.$version.'</strong>';

        return $html;
    }
}
