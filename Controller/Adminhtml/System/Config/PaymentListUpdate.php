<?php

namespace Kevin\Payment\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;

class PaymentListUpdate extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Kevin\Payment\Api\Kevin
     */
    protected $api;

    /**
     * @var \Kevin\Payment\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Kevin\Payment\Api\Kevin $api,
        \Kevin\Payment\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->api = $api;
        $this->helper = $helper;
        $this->timezone = $timezone;

        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $error = true;
        $updateDate = null;
        $message = __('Error');

        try {
            $kevinMethods = $this->api->getPaymentMethods();

            if (is_array($kevinMethods) && !empty($kevinMethods)) {
                if (in_array('bank', $kevinMethods)) {
                    $this->helper->saveAvailablePaymentList($this->api->getBanks());
                    $this->helper->saveAvailableCountryList($this->api->getAvailableCountries());

                    $updateDate = $this->timezone->date(new \DateTime())->format('Y-m-d H:i:s');

                    $error = false;
                    $message = __('Updated');
                }
            }
        } catch (\Exception $e) {
        }

        /** @var Json $result */
        $result = $this->resultJsonFactory->create();

        return $result->setData(['message' => $message, 'update_date' => $updateDate, 'error' => $error]);
    }
}
