<?php

namespace Kevin\Payment\Plugin\Sales\Order\Email\Sender;

class InvoiceSender
{
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $sender;

    /**
     * @var \Kevin\Payment\Gateway\Config\Config
     */
    protected $config;

    /**
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $sender
     * @param \Kevin\Payment\Gateway\Config\Config $config
     */
    public function __construct(
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $sender,
        \Kevin\Payment\Gateway\Config\Config $config
    ){
        $this->sender = $sender;
        $this->config = $config;
    }

    /**
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $subject
     * @param callable $proceed
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @param $forceSyncMode
     * @return void
     */
    public function aroundSend(
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $subject,
        callable $proceed,
        \Magento\Sales\Model\Order\Invoice $invoice,
        $forceSyncMode = false
    ) {
        $order = $invoice->getOrder();
        $canSendInvoice = true;

        if($order->getPayment()->getMethodInstance()->getCode() == \Kevin\Payment\Model\Ui\ConfigProvider::CODE){
            if(!$this->config->getSendInvoiceEmail()){
                $canSendInvoice = false;
            }

            if (!$order->getEmailSent()
                && $this->config->getSendOrderEmailAfter()) {
                $order->setCanSendNewEmailFlag(true);
                $this->sender->send($order);
            }
        }

        if($canSendInvoice){
            return $proceed($invoice, $forceSyncMode);
        }
    }
}
