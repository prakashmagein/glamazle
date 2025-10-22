<?php

namespace Telr\TelrPayments\Controller;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Filesystem\DirectoryList as FileSystem;

abstract class TelrPayments extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface {

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    protected $_orderCollectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote = false;

    protected $_telrModel;
    protected $_telrApplePayModel;

    protected $_telrHelper;
    protected $_messageManager;
    protected $_resultJsonFactory;
    protected $_filesystem;
    protected $_driver;

    protected $_invoiceService;
    protected $_transaction;
    protected $_invoiceSender;
    protected $_transactionBuilder;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Telr\TelrPayments\Model\TelrPayments $telrModel
     * @param \Telr\TelrPayments\Helper\TelrPayments $telrHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Telr\TelrPayments\Model\TelrPayments $telrModel,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Telr\TelrPayments\Model\TelrApplePay $telrApplePayModel,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Telr\TelrPayments\Helper\TelrPayments $telrHelper,
        \Magento\Framework\DB\Transaction $transaction,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        FileSystem $fileSystem,
        \Magento\Framework\Filesystem\File\ReadFactory $driver,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
        $this->_telrModel = $telrModel;
        $this->_invoiceService = $invoiceService;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_invoiceSender = $invoiceSender;
        $this->_telrApplePayModel = $telrApplePayModel;
        $this->_telrHelper = $telrHelper;
        $this->_transaction = $transaction;
        $this->_messageManager = $messageManager;
        $this->_resultJsonFactory  = $resultJsonFactory;
        $this->_filesystem = $fileSystem;
        $this->_driver = $driver;

        parent::__construct($context);
    }

    /**
     * Cancel order, return quote to customer
     *
     * @param string $errorMsg
     * @return false|string
     */

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    
    protected function _cancelPayment($errorMsg = '') {
        $gotoSection = false;
        $this->_telrHelper->cancelCurrentOrder($errorMsg);
        if ($this->_checkoutSession->restoreQuote()) {
            //Redirect to payment step
            $gotoSection = 'paymentMethod';
        }
        return $gotoSection;
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrderById($order_id) {
        $order=$this->_orderFactory->create()->load($order_id);
	return $order;
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder() {
        return $this->_orderFactory->create()->loadByIncrementId(
            $this->_checkoutSession->getLastRealOrderId()
        );
    }

    protected function getQuote() {
        if (!$this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }
        return $this->_quote;
    }

    protected function getCheckoutSession() {
        return $this->_checkoutSession;
    }

    protected function getCustomerSession() {
        return $this->_customerSession;
    }

    protected function getTelrModel() {
        return $this->_telrModel;
    }

    protected function getTelrApplePayModel() {
        return $this->_telrApplePayModel;
    }

    protected function getTelrHelper() {
        return $this->_telrHelper;
    }
}
