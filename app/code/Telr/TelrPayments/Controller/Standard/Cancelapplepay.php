<?php

namespace Telr\TelrPayments\Controller\Standard;

class Cancelapplepay extends \Telr\TelrPayments\Controller\TelrPayments {

    public function execute()
    {
        $this->_cancelPayment();
        $this->messageManager->addError("We were unable to process your payment, Please try again.");
        $this->_checkoutSession->restoreQuote();
        $this->getResponse()->setRedirect(
            $this->getTelrHelper()->getUrl('checkout/cart')
        );
    }
}
