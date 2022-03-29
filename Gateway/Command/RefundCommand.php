<?php

namespace Kevin\Payment\Gateway\Command;

/**
 * Class RefundCommand.
 */
class RefundCommand implements \Magento\Payment\Gateway\CommandInterface
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
     * @var \Magento\Sales\Model\Order\Payment\Transaction\Builder
     */
    protected $transactionBuilder;

    /**
     * @var \Kevin\Payment\Logger\Logger
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $emulation;

    /**
     * RefundCommand constructor.
     */
    public function __construct(
        \Kevin\Payment\Model\Adapter $adapter,
        \Magento\Framework\Locale\Resolver $store,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Sales\Model\Order\Payment\Transaction\Builder $transactionBuilder,
        \Kevin\Payment\Logger\Logger $logger,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\App\Emulation $emulation
    ) {
        $this->adapter = $adapter;
        $this->store = $store;
        $this->remoteAddress = $remoteAddress;
        $this->transactionBuilder = $transactionBuilder;
        $this->logger = $logger;
        $this->request = $request;
        $this->emulation = $emulation;
    }

    /**
     * @throws \Exception
     */
    public function execute(array $commandSubject)
    {
        try {
            if (!isset($commandSubject['payment'])
            ) {
                throw new \InvalidArgumentException(__('Wrong payment request'));
            }

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $request = $objectManager->get('Magento\Framework\App\Request\Http');

            $amount = $commandSubject['amount'];
            $payment = $commandSubject['payment'];

            $order = $payment->getPayment()->getOrder();
            $orderId = $order->getIncrementId();

            $creditMemo = $this->request->getParam('creditmemo');

            if (!$creditMemo['do_offline']) {
                //emulate environment to get specific store config data
                $this->emulation->startEnvironmentEmulation($order->getStoreId());

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $transaction = $objectManager->create('\Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory')->create()
                    ->addOrderIdFilter($order->getId())
                    ->setOrder('transaction_id', 'ASC')
                    ->getFirstItem();

                $transactionId = $transaction->getTxnId();

                $results = $this->adapter->initRefund($transactionId, $amount);

                if (isset($results['id'])) {
                    $payment->getPayment()->setTransactionId($results['id']);
                } else {
                    throw new \Exception('Kevin Error');
                }

                $this->emulation->stopEnvironmentEmulation();
            }
        } catch (\Exception $exception) {
            $this->logger->critical(__('Refund: %1 %2', [$orderId, $exception->getMessage()]));
            throw new \Exception($exception->getMessage());
        }
    }
}
