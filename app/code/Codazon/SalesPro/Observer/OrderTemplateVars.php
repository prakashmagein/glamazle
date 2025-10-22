<?php
/**
 * Copyright © 2022 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Codazon\SalesPro\Observer;

use Magento\Framework\Event\ObserverInterface;

class OrderTemplateVars implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    protected $_filterManager;
    
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->_jsonHelper = $jsonHelper;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {   
        try {
            $requestBody = file_get_contents('php://input');
            if (!$requestBody) {
                $requestBody = '{}';
            }
            $data = $this->_jsonHelper->jsonDecode($requestBody);
            if (!empty($data['comments'])) {
                $transport = $observer->getData('transportObject');
                $orderData = $transport->getData('order_data');
                if (!empty($orderData['email_customer_note'])) {
                    $orderData['email_customer_note'] .= '<br />'. $data['comments'];
                } else {
                    $orderData['email_customer_note'] = $data['comments'];
                }
                $transport->setData('email_customer_note', $data['comments']);
                $transport->setData('order_data', $orderData);
            }
        } catch (\Exception $e) {
            
        }
    }
}