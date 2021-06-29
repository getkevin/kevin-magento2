<?php

namespace Kevin\Payment\Model;

/**
 * Class Adapter
 * @package Kevin\Payment\Model
 */
class Adapter
{
    const PAYMENT_STATUS_GROUP_SUCCESS = 'completed';
    const PAYMENT_STATUS_GROUP_ERROR = 'failed';
    const PAYMENT_STATUS_GROUP_STARTED = 'started';

    const PAYMENT_STATUS_REJECTED = 'RJCT';
    const PAYMENT_STATUS_RECEIVED = 'RCVD';
    const PAYMENT_STATUS_PENDING = 'PDNG';
    const PAYMENT_STATUS_CANCELLED = 'CANC';
    const PAYMENT_STATUS_STARTED = 'STRD';

    /**
     * @var \Kevin\Payment\Api\Kevin
     */
    protected $api;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Kevin\Payment\Gateway\Config\Config
     */
    protected $config;

    /**
     * @var \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory
     */
    protected $transactions;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\Builder
     */
    protected $transactionBuilder;

    /**
     * Adapter constructor.
     * @param \Kevin\Payment\Api\Kevin $api
     * @param \Magento\Framework\UrlInterface $url
     * @param \Kevin\Payment\Gateway\Config\Config $config
     * @param \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactions
     * @param \Magento\Sales\Model\Order\Payment\Transaction\Builder $transactionBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Kevin\Payment\Api\Kevin $api,
        \Magento\Framework\UrlInterface $url,
        \Kevin\Payment\Gateway\Config\Config $config,
        \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactions,
        \Magento\Sales\Model\Order\Payment\Transaction\Builder $transactionBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->api = $api;
        $this->url = $url;
        $this->config = $config;
        $this->transactions = $transactions;
        $this->transactionBuilder = $transactionBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $order
     * @return array
     */
    public function initPayment($order){
        $additionalInformation = $order->getPayment()->getAdditionalInformation();

        $params = [
            'Redirect-URL' => $this->url->getUrl('kevin/payment/callback'),
            'Webhook-URL' => $this->url->getUrl('kevin/payment/notify'),
            'description' => sprintf(__('Order'). ' %s', $order->getIncrementId()),
            'currencyCode' => $order->getOrderCurrency()->ToString(),
            'amount' => number_format($order->getGrandTotal(), 2, '.', ''),
            'bankPaymentMethod' => [
                'endToEndId' => $order->getIncrementId(),
                'creditorName' => $this->config->getCompanyName(),
                'creditorAccount' => [
                    'iban' => $this->config->getCompanyBankAccount(),
                ]
            ]
        ];

        if (!empty($additionalInformation['bank_code'])) {
            if($additionalInformation['bank_code'] == 'card'){
                $params['cardPaymentMethod'] = [];
                $params['paymentMethodPreferred'] = 'card';
            } else {
                $params['bankId'] = $additionalInformation['bank_code'];

                if($this->config->getRedirectPreferred()){
                    $params['redirectPreferred'] = 'true';
                }
            }
        }

        //echo "<pre>";
        //print_r($params); die;

        $response = $this->api->initPayment($params);

        return $response;
    }

    /**
     * @param $payment
     * @param $amount
     * @return array
     */
    public function initRefund($payment, $amount){
        $paymentid = $payment->getLastTransId();
        $params = [
            'amount' => $amount,
            'Webhook-URL' => $this->storeManager->getStore()->getBaseUrl().'kevin/payment/notify'
        ];

        $response = $this->api->initRefund($paymentid, $params);

        return $response;
    }

    /**
     * @param null $order
     * @param array $paymentData
     * @return mixed
     */
    public function createTransaction($order = null, $paymentData = [])
    {
        try {
            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['payment_id']);
            $payment->setTransactionId($paymentData['payment_id']);
            /*$payment->setAdditionalInformation(
                $paymentData
            );*/
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );

            $message = __('The order amount is %1.', $formatedPrice);
            //get the object of builder class
            $trans = $this->transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['payment_id'])
                ->setAdditionalInformation(
                    $paymentData
                )
                ->setFailSafe(true)
                //build method creates the transaction and returns the object
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);

            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();

            return  $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            //log errors here
        }
    }

    /**
     * @param $transactionId
     * @return mixed
     */
    public function getTransaction($transactionId){
        $transaction = $this->transactions->create()
            ->addFieldToFilter('txn_id', $transactionId)
            ->getFirstItem();

        return $transaction;
    }
}