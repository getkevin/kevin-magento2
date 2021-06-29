<?php
namespace Kevin\Payment\Gateway\Command;

/**
 * Class RefundCommand
 * @package Kevin\Payment\Gateway\Command
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
     * @param \Kevin\Payment\Model\Adapter $adapter
     * @param \Magento\Framework\Locale\Resolver $store
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Sales\Model\Order\Payment\Transaction\Builder $transactionBuilder
     * @param \Kevin\Payment\Logger\Logger $logger
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Store\Model\App\Emulation $emulation
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
     * @param array $commandSubject
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

            if(!$creditMemo['do_offline']){
                //emulate environment to get specific store config data
                $this->emulation->startEnvironmentEmulation($order->getStoreId());

                $this->adapter->initRefund($payment->getPayment(), $amount);

                $order
                    ->setState(\Magento\Sales\Model\Order::STATE_CLOSED)
                    ->setStatus(\Kevin\Payment\Setup\InstallData::ORDER_STATUS_REFUND_PENDING);

                $order->addStatusToHistory($order->getStatus(), "Refund request sent to kevin.");
                $order->save();

                $this->emulation->stopEnvironmentEmulation();
            }
        } catch (\Exception $exception) {
            $this->logger->critical(__('Refund: %1 %2', [$orderId, $exception->getMessage()]));
            throw new \Exception($exception->getMessage());
        }
    }
}
