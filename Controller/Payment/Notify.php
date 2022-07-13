<?php

namespace Kevin\Payment\Controller\Payment;

use Zend\Json\Json;

/**
 * Class Notify.
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
     * @var \Kevin\Payment\Gateway\Config\Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \agento\Sales\Api\CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * @var \Kevin\Payment\Model\Creditmemo\Delete
     */
    protected $creditmemoDelete;

    /**
     * @var \Kevin\Payment\Helper\Data
     */
    protected $kevinHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Kevin\Payment\Api\Kevin $api,
        \Kevin\Payment\Model\Adapter $adapter,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\Transaction $transaction,
        \Kevin\Payment\Logger\Logger $logger,
        \Magento\Store\Model\App\Emulation $emulation,
        \Kevin\Payment\Gateway\Config\Config $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Kevin\Payment\Model\Creditmemo\Delete $creditmemoDelete,
        \Kevin\Payment\Helper\Data $kevinHelper
    ) {
        $this->api = $api;
        $this->orderFactory = $orderFactory;
        $this->adapter = $adapter;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transaction = $transaction;
        $this->logger = $logger;
        $this->emulation = $emulation;
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->creditmemoDelete = $creditmemoDelete;
        $this->kevinHelper = $kevinHelper;

        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $body = $this->getRequest()->getContent();
        if ($body) {
            $response = Json::decode($body, true);
            if (!empty($response)) {
                $this->logger->info('Callback Body: '.$body);

                if ($response['id']) {
                    $paymentId = $response['id'];

                    $transaction = $this->adapter->getTransaction($paymentId);
                    if ($transaction->getId()) {
                        $order = $transaction->getOrder();

                        if ($order->getId()) {
                            $signature = $this->scopeConfig->getValue(
                                'payment/kevin_payment/signature',
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                $order->getStoreId()
                            );
                            $headers = getallheaders();

                            $webhookUrl = $this->getRequest()->getUriString();

                            if (getenv('CUSTOM_WEBHOOK_URL')) {
                                $webhookUrl = getenv('CUSTOM_WEBHOOK_URL').'kevin/payment/notify';
                            }

                            $isValid = $this->api->verifySignature($signature, $body, $headers, $webhookUrl);
                            if ($isValid) {
                                // emulate environment to get specific store config data
                                $this->emulation->startEnvironmentEmulation($order->getStoreId());

                                if ($response['type'] == 'PAYMENT_REFUND') {
                                    if ($response['statusGroup'] == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_SUCCESS) {
                                        if ($order->canCreditmemo()) {
                                            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                                                ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                                        } else {
                                            $order->setState(\Magento\Sales\Model\Order::STATE_CLOSED)
                                                ->setStatus(\Magento\Sales\Model\Order::STATE_CLOSED);
                                        }
                                        $order->addStatusToHistory($order->getStatus(), sprintf('Refund transaction "%s" completed', $paymentId));
                                        $order->save();

                                        $quoteId = $order->getQuoteId();
                                        $this->kevinHelper->setQuoteInactive($quoteId);

                                        return $this->getResponse()->setBody(sprintf('Payment with ID "%s" was refunded', $paymentId));
                                    } elseif ($response['statusGroup'] == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_ERROR) {
                                        $creditmemo = $this->getCreditMemoByTxnId($paymentId);
                                        if ($creditmemo) {
                                            $this->creditmemoDelete->deleteCreditmemo($creditmemo->getId());
                                        }
                                        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                                            ->setStatus(\Kevin\Payment\Helper\Data::ORDER_STATUS_REFUND_ERROR);

                                        $order->addStatusToHistory($order->getStatus(), sprintf('Refund transaction "%s" error', $paymentId));
                                        $order->save();

                                        return $this->getResponse()->setBody(sprintf('Payment with ID "%s" was not refunded', $paymentId));
                                    }
                                } else {
                                    if (!in_array($order->getState(), [
                                        $order::STATE_NEW,
                                        $order::STATE_PENDING_PAYMENT,
                                    ])) {
                                        return $this->getResponse()->setBody(sprintf('Order "%s" status is already changed', $order->getIncrementId()));
                                    }

                                    $group = $response['statusGroup'];
                                    if ($group == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_ERROR
                                        || $group == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_STARTED) {
                                        if ($order->getId() && !$order->isCanceled()) {
                                            $order->registerCancellation('')->save();

                                            $order->addStatusToHistory($order->getStatus(), 'Canceled by kevin.');
                                            $order->save();

                                            $this->getResponse()->setBody(sprintf('Order "%s" was canceled', $order->getIncrementId()));
                                        }
                                    } elseif ($response['statusGroup'] == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_SUCCESS) {
                                        try {
                                            if ($order->canInvoice()) {
                                                // Save bank if not saved before
                                                $payment = $order->getPayment();
                                                if (!$payment->getAdditionalInformation('bank_code') || !$payment->getAdditionalInformation('bank_name')) {
                                                    $additional = $transaction->getAdditionalInformation();
                                                    $attr = [
                                                        'PSU-IP-Address' => $additional['ip_address'],
                                                        'PSU-IP-Port' => $additional['ip_port'],
                                                        'PSU-User-Agent' => $additional['user_agent'],
                                                        'PSU-Device-ID' => $additional['device_id'],
                                                    ];

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

                                                if ($payment->getAdditionalInformation('bank_code') == 'card') {
                                                    $paymentType = 'card';
                                                } else {
                                                    $paymentType = 'bank';
                                                }

                                                $invoice = $this->invoiceService->prepareInvoice($order);

                                                if (in_array($paymentType, $this->api->getAllowedRefund())) {
                                                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                                                } else {
                                                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                                                }

                                                $invoice->register();

                                                $invoice->setTransactionId($paymentId);
                                                $invoice->save();

                                                $stateProcessing = \Magento\Sales\Model\Order::STATE_PROCESSING;
                                                if ($order->getState() !== $stateProcessing) {
                                                    $order->setState($stateProcessing)
                                                        ->setStatus($order->getConfig()->getStateDefaultStatus($stateProcessing));

                                                    $order->addStatusToHistory($order->getStatus(), 'Status updated by kevin confirmation.');
                                                    $order->save();
                                                }

                                                $this->invoiceSender->send($invoice);

                                                $this->getResponse()->setBody('Signatures match.');
                                            }
                                        } catch (\Exception $exc) {
                                            $this->getResponse()->setHttpResponseCode(400);
                                            $this->getResponse()->setBody($exc->getMessage());

                                            $this->logger->critical($exc->getMessage());
                                        }
                                    }

                                    $this->emulation->stopEnvironmentEmulation();
                                }
                            } else {
                                $order->addStatusToHistory($order->getStatus(), 'Unable to change order status. Please check whether signature is correct.');
                                $order->save();

                                $this->getResponse()->setHttpResponseCode(400);
                                // Unable to change order status. Please check whether signature is correct.
                                $this->getResponse()->setBody('Signatures do not match.');
                            }
                        }
                    } else {
                        $this->getResponse()->setHttpResponseCode(400);
                        $this->getResponse()->setBody(sprintf('Payment with ID "%s" not found in magento', $paymentId));
                    }
                }
            }
        } else {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody('Response body is empty');
        }
    }

    public function getCreditMemoByTxnId($txnId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('transaction_id', $txnId)
            ->create();
        try {
            $creditmemos = $this->creditmemoRepository->getList($searchCriteria);
            foreach ($creditmemos->getItems() as $item) {
                return $item;
            }
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $creditmemoRecords = null;
        }
    }

    public function isRefundCompleted($paymentId)
    {
        $refunds = $this->api->getRefunds($paymentId);
        foreach ($refunds as $refund) {
            if ($refund['paymentId'] == $paymentId && $refund['statusGroup'] == \Kevin\Payment\Model\Adapter::PAYMENT_STATUS_GROUP_SUCCESS) {
                return true;
            }
        }

        return false;
    }
}
