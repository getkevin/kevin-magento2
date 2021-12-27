<?php

namespace Kevin\Payment\Helper;

/**
 * Class Data
 * @package Kevin\Payment\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ORDER_STATUS_REFUND_PENDING = 'refund_pending';
    const ORDER_STATUS_REFUND_ERROR = 'refund_error';

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository

    ) {
        $this->quoteRepository = $quoteRepository;

        parent::__construct($context);
    }

    public function setQuoteInactive($quoteId){
        try {
            $quote = $this->quoteRepository->get($quoteId);

            if($quote && $quote->getIsActive()){
                $quote->setIsActive(false);
                $this->quoteRepository->save($quote);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}