<?php

namespace Kevin\Payment\Model;

/**
 * Class Adapter.
 */
class Adapter
{
    public const PAYMENT_STATUS_GROUP_SUCCESS = 'completed';
    public const PAYMENT_STATUS_GROUP_ERROR = 'failed';
    public const PAYMENT_STATUS_GROUP_STARTED = 'started';
    public const PAYMENT_STATUS_GROUP_PENDING = 'pending';

    public const PAYMENT_STATUS_REJECTED = 'RJCT';
    public const PAYMENT_STATUS_RECEIVED = 'RCVD';
    public const PAYMENT_STATUS_PENDING = 'PDNG';
    public const PAYMENT_STATUS_CANCELLED = 'CANC';
    public const PAYMENT_STATUS_STARTED = 'STRD';

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
     *
     * @return array
     */
    public function initPayment($order)
    {
        $additionalInformation = $order->getPayment()->getAdditionalInformation();

        $companyName = $this->config->getCompanyName();
        $companyBankAccount = $this->config->getCompanyBankAccount();

        $params = [
            'Redirect-URL' => $this->getRedirectContextUrl('kevin/payment/callback'),
            'Webhook-URL' => $this->getWebHookContextUrl('kevin/payment/notify'),
            'description' => sprintf('Order %s', $order->getIncrementId()),
            'currencyCode' => $order->getOrderCurrency()->ToString(),
            'amount' => number_format($order->getGrandTotal(), 2, '.', ''),
            'identifier' => [
                'email' => $order->getCustomerEmail(),
            ],
        ];

        if (!empty($additionalInformation['bank_code'])) {
            $bankAccounts = $this->config->getAdditionalBankAccounts();
            if ($bankAccounts) {
                foreach ($bankAccounts as $account) {
                    if ($account['bank'] == $additionalInformation['bank_code']) {
                        $companyName = $account['company'];
                        $companyBankAccount = $account['bank_account'];
                        break;
                    }
                }
            }

            if ($additionalInformation['bank_code'] == 'card') {
                $params['cardPaymentMethod'] = [];
                $params['paymentMethodPreferred'] = 'card';
            } else {
                $params['bankId'] = $additionalInformation['bank_code'];

                if ($this->config->getRedirectPreferred()) {
                    $params['redirectPreferred'] = 'true';
                }
            }
        } else {
            if ($kevinMethods = $this->api->getPaymentMethods()) {
                if (in_array('card', $kevinMethods)) {
                    $params['cardPaymentMethod'] = [];
                }
            }
        }

        $params['bankPaymentMethod'] = [
            'endToEndId' => $order->getIncrementId(),
            'creditorName' => $companyName,
            'creditorAccount' => [
                'iban' => $companyBankAccount,
            ],
        ];

        $response = $this->api->initPayment($params);

        return $response;
    }

    /**
     * @param $payment
     * @param $amount
     *
     * @return array
     */
    public function initRefund($transactionId, $amount)
    {
        $params = [
            'amount' => $amount,
            'Webhook-URL' => $this->storeManager->getStore()->getBaseUrl().'kevin/payment/notify',
        ];

        $response = $this->api->initRefund($transactionId, $params);

        return $response;
    }

    /**
     * @param null  $order
     * @param array $paymentData
     *
     * @return mixed
     */
    public function createTransaction($order = null, $paymentData = [])
    {
        try {
            // get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['payment_id']);
            $payment->setTransactionId($paymentData['payment_id']);
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );

            $message = __('The order amount is %1.', $formatedPrice);
            // get the object of builder class
            $trans = $this->transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['payment_id'])
                ->setAdditionalInformation(
                    $paymentData
                )
                ->setFailSafe(true)
                // build method creates the transaction and returns the object
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);

            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();

            return $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            // log errors here
        }
    }

    /**
     * @param $transactionId
     *
     * @return mixed
     */
    public function getTransaction($transactionId)
    {
        $transaction = $this->transactions->create()
            ->addFieldToFilter('txn_id', $transactionId)
            ->getFirstItem();

        return $transaction;
    }

    /**
     * @param $uri
     *
     * @return string
     */
    private function getWebHookContextUrl($uri)
    {
        if (getenv('CUSTOM_WEBHOOK_URL')) {
            return getenv('CUSTOM_WEBHOOK_URL').$uri;
        }

        return $this->url->getUrl($uri);
    }

    /**
     * @param $uri
     *
     * @return string
     */
    private function getRedirectContextUrl($uri)
    {
        if (getenv('CUSTOM_REDIRECT_URL')) {
            return getenv('CUSTOM_REDIRECT_URL').$uri;
        }

        return $this->url->getUrl($uri);
    }
}
