<?php


namespace TotalPay\Gateway\Controller;

use Magento\Framework\View\Result\PageFactory;

/**
 * Base Checkout Redirect Controller Class
 * Class AbstractCheckoutRedirectAction
 * @package TotalPay\Gateway\Controller
 */
abstract class AbstractCheckoutRedirectAction extends \TotalPay\Gateway\Controller\AbstractCheckoutAction
{
    /**
     * @var \TotalPay\Gateway\Helper\Checkout
     */
    private $_checkoutHelper;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \TotalPay\Gateway\Helper\Checkout $checkoutHelper,
        PageFactory $resultPageFactory

    ) {
        parent::__construct($context, $logger, $checkoutSession, $orderFactory, $resultPageFactory);
        $this->_checkoutHelper = $checkoutHelper;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Get an Instance of the Magento Checkout Helper
     * @return \TotalPay\Gateway\Helper\Checkout
     */
    protected function getCheckoutHelper()
    {
        return $this->_checkoutHelper;
    }

    /**
     * Handle Success Action
     * @return void
     */
    protected function executeSuccessAction()
    {
        if ($this->getCheckoutSession()->getLastRealOrderId()) {
            $this->getMessageManager()->addSuccess(__("Your payment is complete"));
            $this->redirectToCheckoutOnePageSuccess();
        }
    }

    /**
     * Handle Cancel Action from Payment Gateway
     */
    protected function executeCancelAction()
    {
        $this->getCheckoutHelper()->cancelCurrentOrder('');
        $this->getCheckoutHelper()->restoreQuote();

//        $this->redirectToCheckoutCart();
        $this->redirectToRestoreCart();
    }

    /**
     * Get the redirect action
     *      - success
     *      - cancel
     *      - failure
     *
     * @return string
     */
    protected function getReturnAction()
    {
        return $this->getRequest()->getParam('action');
    }
}
