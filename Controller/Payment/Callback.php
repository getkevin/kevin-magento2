<?php

namespace Kevin\Payment\Controller\Payment;

/**
 * Class Callback
 * @package Kevin\Payment\Controller\Payment
 */
class Callback extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Kevin\Payment\Api\Kevin
     */
    protected $api;

    /**
     * @var \Kevin\Payment\Model\Adapter
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
     * Callback constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Kevin\Payment\Api\Kevin $api
     * @param \Kevin\Payment\Model\Adapter $adapter
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Kevin\Payment\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Kevin\Payment\Api\Kevin $api,
        \Kevin\Payment\Model\Adapter $adapter,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\Transaction $transaction,
        \Kevin\Payment\Logger\Logger $logger
    ) {
        $this->api = $api;
        $this->adapter = $adapter;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transaction = $transaction;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    protected function _getCheckout(){
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }

    /**
     * execute
     */
    public function execute(){
        $paymentId = $this->getRequest()->getParam('paymentId');
        $transaction = $this->adapter->getTransaction($paymentId);
        if($transaction->getId()) {
            $order = $transaction->getOrder();

            if (!$order) {
                $this->messageManager->addError(__('Something went wrong, please try again later'));
                $this->_redirect('checkout/cart');
                return;
            }

            if (!in_array($order->getState(), array(
                $order::STATE_NEW,
                $order::STATE_PENDING_PAYMENT
            ))) {
                $this->_redirect('checkout/onepage/success');
                return;
            }

            $additional = $transaction->getAdditionalInformation();
            $attr = array(
                'PSU-IP-Address' => $additional['ip_address'],
                'PSU-IP-Port' => $additional['ip_port'],
                'PSU-User-Agent' => $additional['user_agent'],
                'PSU-Device-ID' => $additional['device_id'],
            );

            $results = $this->api->getPaymentStatus($paymentId, $attr);

            if (isset($results['group'])) {
                $group = $results['group'];
                /*$status = null;
                if(isset($results['bankStatus'])){
                    $status = $results['bankStatus'];
                } elseif($results['hybridStatus']){
                    $status = $results['hybridStatus'];
                }*/

                if ($group == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_ERROR
                    || $group == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_STARTED) {
                    if ($order->getId() && !$order->isCanceled()) {
                        $order->registerCancellation('')->save();

                        $order->addStatusToHistory($order->getStatus(), "Canceled by kevin.");
                        $order->save();
                    }

                    $this->_getCheckout()->restoreQuote();

                    //$this->messageManager->addError(__('Payment was canceled on gateway. Order data restored.'));
                    $this->_redirect('checkout', ['_fragment' => 'payment']);
                    return;
                } elseif($results['group'] == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_SUCCESS) {
                    //disabled because magento was capturing order twice when callback and webhook working at same time.
                    /*try {
                        if ($order->canInvoice()) {
                            $invoice = $this->invoiceService->prepareInvoice($order);
                            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                            $invoice->register();

                            $invoice->setTransactionId($paymentId);
                            $invoice->save();

                            $stateProcessing = \Magento\Sales\Model\Order::STATE_PROCESSING;
                            if ($order->getState() !== $stateProcessing) {
                                $order->setState($stateProcessing)
                                    ->setStatus($order->getConfig()->getStateDefaultStatus($stateProcessing));

                                $order->addStatusToHistory($order->getStatus(), "Status updated by kevin confirmation.");
                                $order->save();
                            }

                            //Save bank if not saved before
                            $payment = $order->getPayment();
                            if(!$payment->getAdditionalInformation('bank_code') || !$payment->getAdditionalInformation('bank_name')){
                                $results = $this->api->getPayment($paymentId, $attr);

                                if(isset($results['bankId'])){
                                    $bank = $this->api->getBank($results['bankId']);
                                    if(isset($bank['id'])){
                                        $payment->setAdditionalInformation('bank_code', $bank['id']);
                                        $payment->setAdditionalInformation('bank_name', $bank['name']);
                                        $payment->save();
                                    }
                                }
                            }

                            $this->invoiceSender->send($invoice);
                        }
                    } catch (\Exception $exc) {
                        $this->logger->critical($exc->getMessage());
                    }*/
                }
            }
        }
        $this->_redirect('checkout/onepage/success');
        return;
    }
}