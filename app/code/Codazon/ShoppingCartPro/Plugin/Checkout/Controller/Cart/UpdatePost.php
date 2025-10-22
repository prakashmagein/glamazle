<?php
/**
 * Copyright © 2020 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace Codazon\ShoppingCartPro\Plugin\Checkout\Controller\Cart;

class UpdatePost
{
    protected $helper;
    
    protected $resultPageFactory;
    
    public function __construct(
        \Codazon\Core\Helper\Data $helper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
    }
    
    public function aroundExecute(
        \Magento\Checkout\Controller\Cart\UpdatePost $subject,
        \Closure $proceed
    ) {
        $request = $subject->getRequest();
        if ($request->getPostValue('cdz_is_ajax')) {
            $result = [];
            $success = true;
            $message = false;
            try {
                $resultPage = $proceed();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $success = false;
                $message = $e->getMessage();
            } catch (\Exception $e) {
                $success = false;
                $message =  __('We can\'t update the shopping cart.');
            }
            if ($success) {
                $request->setActionName('index');
                $page = $this->resultPageFactory->create();
                $cartBlock = $page->getLayout()->getBlock('checkout.cart.form');
                $result['cart_form_html'] = $cartBlock->toHtml();
                $result['items_count'] = $cartBlock->getItemsCount();
                if ($result['items_count'] > 0) {
                    $result['message'] = [
                        'text' => __('The shopping cart was updated successfully'),
                        'type' => 'success'
                    ];
                } else {
                    $result['message'] = [
                        'text' => __('You have no items in your shopping cart.'),
                        'type' => 'info'
                    ];
                }
            } else {
                $result['message'] = [
                    'text' =>$message,
                    'type' => 'error'
                ];
            }
            return $subject->getResponse()->representJson(
                $this->helper->getObjectManager()->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
            );         
        } else {
            return $proceed();
        }
    }
    
    /* public function afterExecute(\Magento\Checkout\Controller\Cart\UpdatePost $controller, $resultPage)
    {
        
        return $resultPage;
    } */
}