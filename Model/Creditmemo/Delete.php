<?php

namespace Kevin\Payment\Model\Creditmemo;

use Exception;
use Magento\Backend\Model\Auth\Session;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;

class Delete
{
    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Session
     */
    protected $_authSession;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param Order $order
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param LoggerInterface $logger
     * @param Session $authSession
     * @param Registry $registry
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Order $order,
        CreditmemoRepositoryInterface $creditmemoRepository,
        LoggerInterface $logger,
        Session $authSession,
        Registry $registry,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->order = $order;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->logger = $logger;
        $this->_authSession = $authSession;
        $this->registry = $registry;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param $creditmemoId
     * @return \Magento\Sales\Model\Order
     * @throws \Exception
     */
    public function deleteCreditmemo($creditmemoId)
    {
        if($this->registry->registry('isSecureArea')){
            $this->registry->unregister('isSecureArea');
        }
        $this->registry->register('isSecureArea', true);

        $creditmemo = $this->creditmemoRepository->get($creditmemoId);
        $orderId = $creditmemo->getOrder()->getId();

        $order = $this->orderRepository->get($orderId);
        $orderItems = $order->getAllItems();
        $creditmemoItems = $creditmemo->getAllItems();

        // revert credit memo fields in ordered items table
        foreach ($orderItems as $item) {
            foreach ($creditmemoItems as $creditmemoItem) {
                if ($creditmemoItem->getOrderItemId() == $item->getItemId()) {
                    $item->setQtyRefunded($item->getQtyRefunded() - $creditmemoItem->getQty());
                    $item->setTaxRefunded($item->getTaxRefunded() - $creditmemoItem->getTaxAmount());
                    $item->setBaseTaxRefunded($item->getBaseTaxRefunded() - $creditmemoItem->getBaseTaxAmount());
                    $discountTaxItem = $item->getDiscountTaxCompensationRefunded();
                    $discountTaxCredit = $creditmemoItem->getDiscountTaxCompensationAmount();
                    $item->setDiscountTaxCompensationRefunded(
                        $discountTaxItem - $discountTaxCredit
                    );
                    $baseDiscountItem = $item->getBaseDiscountTaxCompensationRefunded();
                    $baseDiscountCredit = $creditmemoItem->getBaseDiscountTaxCompensationAmount();
                    $item->setBaseDiscountTaxCompensationRefunded(
                        $baseDiscountItem - $baseDiscountCredit
                    );
                    $item->setAmountRefunded($item->getAmountRefunded() - $creditmemoItem->getRowTotal());
                    $item->setBaseAmountRefunded($item->getBaseAmountRefunded() - $creditmemoItem->getBaseRowTotal());
                    $item->setDiscountRefunded($item->getDiscountRefunded() - $creditmemoItem->getDiscountAmount());
                    $item->setBaseDiscountRefunded(
                        $item->getBaseDiscountRefunded() - $creditmemoItem->getBaseDiscountAmount()
                    );
                }
            }
        }

        // revert info in order table
        $order->setBaseTotalRefunded($order->getBaseTotalRefunded() - $creditmemo->getBaseGrandTotal());
        $order->setTotalRefunded($order->getTotalRefunded() - $creditmemo->getGrandTotal());

        $order->setBaseSubtotalRefunded($order->getBaseSubtotalRefunded() - $creditmemo->getBaseSubtotal());
        $order->setSubtotalRefunded($order->getSubtotalRefunded() - $creditmemo->getSubtotal());

        $order->setBaseTaxRefunded($order->getBaseTaxRefunded() - $creditmemo->getBaseTaxAmount());
        $order->setTaxRefunded($order->getTaxRefunded() - $creditmemo->getTaxAmount());
        $order->setBaseDiscountTaxCompensationRefunded(
            $order->getBaseDiscountTaxCompensationRefunded() - $creditmemo->getBaseDiscountTaxCompensationAmount()
        );
        $order->setDiscountTaxCompensationRefunded(
            $order->getDiscountTaxCompensationRefunded() - $creditmemo->getDiscountTaxCompensationAmount()
        );

        $order->setBaseShippingRefunded($order->getBaseShippingRefunded() - $creditmemo->getBaseShippingAmount());
        $order->setShippingRefunded($order->getShippingRefunded() - $creditmemo->getShippingAmount());

        $order->setBaseShippingTaxRefunded(
            $order->getBaseShippingTaxRefunded() - $creditmemo->getBaseShippingTaxAmount()
        );
        $order->setShippingTaxRefunded($order->getShippingTaxRefunded() - $creditmemo->getShippingTaxAmount());
        $order->setAdjustmentPositive($order->getAdjustmentPositive() - $creditmemo->getAdjustmentPositive());
        $order->setBaseAdjustmentPositive(
            $order->getBaseAdjustmentPositive() - $creditmemo->getBaseAdjustmentPositive()
        );
        $order->setAdjustmentNegative($order->getAdjustmentNegative() - $creditmemo->getAdjustmentNegative());
        $order->setBaseAdjustmentNegative(
            $order->getBaseAdjustmentNegative() - $creditmemo->getBaseAdjustmentNegative()
        );
        $order->setDiscountRefunded($order->getDiscountRefunded() - $creditmemo->getDiscountAmount());
        $order->setBaseDiscountRefunded($order->getBaseDiscountRefunded() - $creditmemo->getBaseDiscountAmount());

        $this->setTotalandBaseTotal($creditmemo, $order);

        try {
            $invoice = $creditmemo->getInvoice();
            $invoice->setData('is_used_for_refund', 0);
            $invoice->save();

            $creditmemoData = $this->creditmemoRepository->get($creditmemoId);

            //delete credit-memo by credit-memo object
            $this->creditmemoRepository->delete($creditmemoData);
            $this->orderRepository->save($order);
            //$this->saveOrder($order);
        } catch (Exception $exception) {
            echo $exception->getMessage(); die;
            $this->logger->critical($exception->getMessage());
        }

        return $order;
    }

    /**
     * @param $creditmemo
     * @param $order
     */
    protected function setTotalandBaseTotal($creditmemo, $order)
    {
        if ($creditmemo->getDoTransaction()) {
            $order->setTotalOnlineRefunded($order->getTotalOnlineRefunded() - $creditmemo->getGrandTotal());
            $order->setBaseTotalOnlineRefunded($order->getBaseTotalOnlineRefunded() - $creditmemo->getBaseGrandTotal());
        } else {
            $order->setTotalOfflineRefunded($order->getTotalOfflineRefunded() - $creditmemo->getGrandTotal());
            $order->setBaseTotalOfflineRefunded(
                $order->getBaseTotalOfflineRefunded() - $creditmemo->getBaseGrandTotal()
            );
        }
    }

    /**
     * @param $order
     */
    protected function saveOrder($order)
    {
        if ($order->hasShipments() || $order->hasInvoices() || $order->hasCreditmemos()) {
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                ->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING))
                ->save();
        } elseif (!$order->canInvoice() && !$order->canShip() && !$order->hasCreditmemos()) {
            $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE)
                ->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_COMPLETE))
                ->save();
        } else {
            $order->setState(\Magento\Sales\Model\Order::STATE_NEW)
                ->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_NEW))
                ->save();
        }
    }
}
