<?php

namespace Kevin\Payment\Controller\Payment;

/**
 * Class Redirect.
 */
class Redirect extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Kevin\Payment\Model\Adapter
     */
    protected $adapter;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $store;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var \Kevin\Payment\Gateway\Config\Config
     */
    protected $config;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Kevin\Payment\Logger\Logger
     */
    protected $logger;

    /**
     * Redirect constructor.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Kevin\Payment\Model\Adapter $adapter,
        \Magento\Framework\Locale\Resolver $store,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Kevin\Payment\Gateway\Config\Config $config,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Kevin\Payment\Logger\Logger $logger
    ) {
        $this->adapter = $adapter;
        $this->store = $store;
        $this->remoteAddress = $remoteAddress;
        $this->config = $config;
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;

        parent::__construct($context);
    }

    protected function _getCheckout()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }

    /**
     * @return false
     */
    public function getOrder()
    {
        if ($this->_getCheckout()->getLastRealOrderId()) {
            $order = $this->orderFactory->create()->loadByIncrementId($this->_getCheckout()->getLastRealOrderId());

            return $order;
        }

        return false;
    }

    /**
     * execute.
     */
    public function execute()
    {
        $status = $this->config->getActive();

        if (!$status) {
            return;
        }

        $order = $this->getOrder();
        if (!$order) {
            throw new \Exception('Order not found');
        }

        try {
            $payment = $order->getPayment();
            if ($payment->getMethodInstance()->getCode() == \Kevin\Payment\Model\Ui\ConfigProvider::CODE) {
                $paymentLink = null;

                if ($transactionId = $payment->getLastTransId()) {
                    $transaction = $this->adapter->getTransaction($transactionId);
                    if ($transaction->getId()) {
                        $transaction->delete();

                        $payment->setLastTransId('');
                        $payment->save();
                    }
                }

                $response = $this->adapter->initPayment($order);

                if (isset($response['confirmLink'])) {
                    $additional = [];
                    $additional['payment_id'] = $response['id'];
                    $additional['payment_link'] = $response['confirmLink'];
                    $additional['ip_address'] = $this->getCustomerIpAddress();
                    $additional['ip_port'] = $this->getCustomerIpPort();
                    $additional['user_agent'] = $this->getCustomerUserAgent();
                    $additional['device_id'] = $this->getCustomerDeviceId();

                    $paymentLink = $response['confirmLink'];
                    $this->adapter->createTransaction($order, $additional);

                    $lang = $this->getLocaleCode();
                    $query = parse_url($response['confirmLink'], \PHP_URL_QUERY);
                    $paymentLink .= ($query) ? '&lang='.$lang : '?lang='.$lang;

                    $this->_redirect($paymentLink);
                } else {
                    throw new \Exception('Something went wrong please contact support');
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addException($e, $e->getMessage());
            $this->_getCheckout()->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * @return false|string
     */
    protected function getLocaleCode()
    {
        $currentStore = $this->store->getLocale();
        $lang = strstr($currentStore, '_', true);

        return $lang;
    }

    /**
     * @return string
     */
    protected function getUUID()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0x0FFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF)
        );
    }

    /**
     * @return string
     */
    protected function getCustomerDeviceId()
    {
        return $this->getUUID();
    }

    protected function getCustomerIpAddress()
    {
        return $this->remoteAddress->getRemoteAddress();
    }

    /**
     * @return string
     */
    protected function getCustomerIpPort()
    {
        if (isset($_SERVER['HTTP_X_REAL_PORT'])) {
            return trim($_SERVER['HTTP_X_REAL_PORT']);
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            return trim($_SERVER['HTTP_X_FORWARDED_PORT']);
        } elseif (isset($_SERVER['REMOTE_PORT'])) {
            return trim($_SERVER['REMOTE_PORT']);
        }

        return '';
    }

    /**
     * @return string
     */
    protected function getCustomerUserAgent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
    }
}
