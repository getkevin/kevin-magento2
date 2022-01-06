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

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Kevin\Payment\Api\Kevin $api
     * @param \Kevin\Payment\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Kevin\Payment\Api\Kevin $api,
        \Kevin\Payment\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    )
    {
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

            if(is_array($kevinMethods) && !empty($kevinMethods)){
                if (in_array("bank", $kevinMethods)) {

                    $this->helper->saveAvailablePaymentList($this->api->getBanks());
                    $this->helper->saveAvailableCountryList($this->api->getAvailableCountries());

                    $updateDate = $this->timezone->date()->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
                    $error = false;
                    $message = __('Updated');
                }
            }
        } catch (\Exception $e){
        }

        /** @var Json $result */
        $result = $this->resultJsonFactory->create();

        return $result->setData(['message' => $message, 'update_date' => $updateDate, 'error' => $error]);
    }
}
