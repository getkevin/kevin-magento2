<?php

namespace Kevin\Payment\Controller\Payment;

use Zend\Json\Json;

/**
 * Class Notify
 * @package Kevin\Payment\Controller\Payment
 */
class Notify extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Kevin\Payment\Api\Kevin
     */
    protected $api;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

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
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $emulation;

    /**
     * Notify constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Kevin\Payment\Api\Kevin $api
     * @param \Kevin\Payment\Model\Adapter $adapter
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Kevin\Payment\Logger\Logger $logger
     * @param \Magento\Store\Model\App\Emulation $emulation
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Kevin\Payment\Api\Kevin $api,
        \Kevin\Payment\Model\Adapter $adapter,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\Transaction $transaction,
        \Kevin\Payment\Logger\Logger $logger,
        \Magento\Store\Model\App\Emulation $emulation
    ) {
        $this->api = $api;
        $this->orderFactory = $orderFactory;
        $this->adapter = $adapter;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transaction = $transaction;
        $this->logger = $logger;
        $this->emulation = $emulation;

        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $body = $this->getRequest()->getContent();
        if($body) {
            $response = Json::decode($body, true);
            if (!empty($response)) {
                //$this->logger->info('Callback: '.$body);

                if ($response['id']) {
                    if($response['type'] == 'PAYMENT_REFUND'){
                        $paymentId = $response['paymentId'];
                    } else {
                        $paymentId = $response['id'];
                    }


                    $transaction = $this->adapter->getTransaction($paymentId);

                    if ($transaction->getId()) {
                        $order = $transaction->getOrder();

                        if ($order->getId()) {
                            //emulate environment to get specific store config data
                            $this->emulation->startEnvironmentEmulation($order->getStoreId());

                            if($response['type'] == 'PAYMENT_REFUND' && $order->getStatus() == \Kevin\Payment\Setup\InstallData::ORDER_STATUS_REFUND_PENDING){
                                $refundCompleted = $this->isRefundCompleted($paymentId);
                                if($refundCompleted){
                                    $order->setStatus(\Magento\Sales\Model\Order::STATE_CLOSED);
                                    $order->addStatusToHistory($order->getStatus(), "Refund accepted.");
                                    $order->save();

                                    $this->getResponse()->setBody('OK');
                                    $this->emulation->stopEnvironmentEmulation();
                                }
                            } else {

                                if (!in_array($order->getState(), array(
                                    $order::STATE_NEW,
                                    $order::STATE_PENDING_PAYMENT
                                ))) {
                                    return $this->getResponse()->setBody('OK');
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
                                    if ($group == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_ERROR
                                        || $group == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_STARTED) {
                                        if ($order->getId() && !$order->isCanceled()) {
                                            $order->registerCancellation('')->save();
                                        }
                                    } elseif ($results['group'] == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_SUCCESS) {
                                        try {
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
                                                if (!$payment->getAdditionalInformation('bank_code') || !$payment->getAdditionalInformation('bank_name')) {
                                                    $results = $this->api->getPayment($paymentId, $attr);

                                                    if (isset($results['bankId'])) {
                                                        $bank = $this->api->getBank($results['bankId']);
                                                        if (isset($bank['id'])) {
                                                            $payment->setAdditionalInformation('bank_code', $bank['id']);
                                                            $payment->setAdditionalInformation('bank_name', $bank['name']);
                                                            $payment->save();
                                                        }
                                                    }
                                                }

                                                $this->invoiceSender->send($invoice);

                                                $this->getResponse()->setBody('OK');
                                            }
                                        } catch (\Exception $exc) {
                                            $this->logger->critical($exc->getMessage());
                                        }
                                    }
                                }

                                $this->emulation->stopEnvironmentEmulation();
                            }
                        }
                    }
                }
            }
        }
    }

    public function isRefundCompleted($paymentId){
        $refunds = $this->api->getRefunds($paymentId);
        foreach($refunds as $refund){
            if($refund['paymentId'] == $paymentId && $refund['statusGroup'] == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_SUCCESS){
                return true;
            }
        }

        return false;
    }
}