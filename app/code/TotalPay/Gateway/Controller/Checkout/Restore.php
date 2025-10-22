<?php


namespace TotalPay\Gateway\Controller\Checkout;

/**
 * Front Controller for Checkout Method
 * it does a redirect to checkout
 * Class Restore
 * @package TotalPay\Gateway\Controller\Checkout
 */
class Restore extends \TotalPay\Gateway\Controller\AbstractCheckoutAction
{

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}
