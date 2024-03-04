<?php

namespace Kevin\Payment\Controller\Payment;

use Kevin\Payment\Model\Adapter as KevinAdapter;

/**
 * Class Callback.
 */
class Callback extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Kevin\Payment\Api\Kevin
     */
    protected $api;

    /**
     * @var KevinAdapter
     */
    protected $adapter;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $transaction;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var \Kevin\Payment\Logger\Logger
     */
    protected $logger;

    /**
     * @var \Kevin\Payment\Helper\Data
     */
    protected $kevinHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Kevin\Payment\Api\Kevin $api,
        KevinAdapter $adapter,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\Transaction $transaction,
        \Kevin\Payment\Logger\Logger $logger,
        \Kevin\Payment\Helper\Data $kevinHelper
    ) {
        $this->api = $api;
        $this->adapter = $adapter;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transaction = $transaction;
        $this->logger = $logger;
        $this->kevinHelper = $kevinHelper;

        parent::__construct($context);
    }

    protected function _getCheckout()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }

    /**
     * execute.
     */
    public function execute()
    {
        $statusGroup = $this->getRequest()->getParam('statusGroup');
        $order = $this->_getCheckout()->getLastRealOrder();
        $success = false;

        if ($order->getId()) {
            $order->addStatusToHistory($order->getStatus(), __('Customer came back to the store.'));
            $order->save();
        }

        switch ($statusGroup) {
            case KevinAdapter::PAYMENT_STATUS_GROUP_STARTED:
                $this->messageManager->addError(__('Payment initiation was cancelled. Please try again.'));
                break;
            case KevinAdapter::PAYMENT_STATUS_GROUP_PENDING:
                $this->messageManager->addSuccess(__('We will start executing the order only after receiving the payment.'));
                $success = true;
                break;
            case KevinAdapter::PAYMENT_STATUS_GROUP_SUCCESS:
                $this->messageManager->addSuccess(__('Thank you for your payment. Your transaction has been completed and a receipt for your purchase has been emailed to you.'));
                $success = true;
                break;
            case KevinAdapter::PAYMENT_STATUS_GROUP_ERROR:
                $this->messageManager->addError(__('Unfortunately, your order cannot be processed as the originating bank has declined your transaction. Please attempt your purchase again.'));
                break;
        }

        if ($success) {
            if ($order->getId()) {
                $quoteId = $order->getQuoteId();
                $this->kevinHelper->setQuoteInactive($quoteId);
            }

            $this->_redirect('checkout/onepage/success');
        } else {
            $this->_getCheckout()->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }
}
