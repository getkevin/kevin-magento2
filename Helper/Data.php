<?php

namespace Kevin\Payment\Helper;

/**
 * Class Data.
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
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * @var \Kevin\Payment\Model\PaymentMethodsFactory
     */
    protected $paymentMethodsFactory;

    /**
     * @var \Kevin\Payment\Gateway\Config\Config
     */
    protected $_config;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Kevin\Payment\Model\PaymentMethodsFactory $paymentMethodsFactory,
        \Kevin\Payment\Gateway\Config\Config $config
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->resourceConnection = $resourceConnection;
        $this->_assetRepo = $assetRepo;
        $this->paymentMethodsFactory = $paymentMethodsFactory;
        $this->_config = $config;

        parent::__construct($context);
    }

    /**
     * @return void
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setQuoteInactive($quoteId)
    {
        $quote = $this->quoteRepository->get($quoteId);

        if ($quote && $quote->getIsActive()) {
            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);
        }
    }

    /**
     * @return void
     */
    public function truncateTable($tableName)
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->query(
            sprintf(
                'TRUNCATE %s',
                $this->resourceConnection->getTableName($tableName)
            )
        );
    }

    /**
     * @return bool
     */
    public function saveAvailablePaymentList($bankList)
    {
        $this->truncateTable('kevin_payment_list');

        if ($bankList) {
            foreach ($bankList as $bank) {
                $description = !empty($bank['officialName']) ? $bank['officialName'] : '';

                $methodModel = $this->paymentMethodsFactory->create();
                $methodModel->setPaymentId($bank['id'])
                    ->setCountryId($bank['countryCode'])
                    ->setTitle($bank['name'])
                    ->setDescription($description)
                    ->setLogoPath($bank['imageUri'])
                    ->save();
            }
        }

        return true;
    }

    /**
     * @return void
     */
    public function saveAvailableCountryList($countryList)
    {
        if (!empty($countryList)) {
            $countryList = implode(',', $countryList);

            $this->_config->setCountryList($countryList);
        }
    }
}
