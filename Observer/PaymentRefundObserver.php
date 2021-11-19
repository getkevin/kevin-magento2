<?php
namespace Kevin\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class PaymentRefundObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request
    ){
        $this->request = $request;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $creditmemo = $observer->getCreditmemo();
        $order = $creditmemo->getOrder();

        if($order->getPayment()->getMethodInstance()->getCode() == \Kevin\Payment\Model\Ui\ConfigProvider::CODE) {
            $creditMemo = $this->request->getParam('creditmemo');
            if(is_array($creditMemo)) {
                if (isset($creditMemo['do_offline']) && !$creditMemo['do_offline']) {
                    $order
                        ->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                        ->setStatus(\Kevin\Payment\Helper\Data::ORDER_STATUS_REFUND_PENDING);

                    $order->addStatusToHistory($order->getStatus(), "Refund request sent to kevin, the response can take up to 24 hours");
                }
            }
        }
    }
}
