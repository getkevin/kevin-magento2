<?php

namespace Kevin\Payment\Model\Config\Source;

class ModuleStatus implements \Magento\Config\Model\Config\CommentInterface
{
    /**
     * @var \Kevin\Payment\Gateway\Config\Config
     */
    protected $_config;

    public function __construct(
        \Kevin\Payment\Gateway\Config\Config $config
    ) {
        $this->_config = $config;
    }

    public function getCommentText($elementValue)
    {
        if (!$this->_config->getStatus()) {
            return sprintf(
                '<span style="font-size: 16px; color: red;">%s</span>',
                __('Unauthorized')
            );
        } else {
            return sprintf(
                '<span style="font-size: 16px; color: green;">%s</span>',
                __('Active')
            );
        }
    }
}
