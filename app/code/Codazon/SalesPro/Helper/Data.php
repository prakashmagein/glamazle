<?php
/**
* Copyright © 2020 Codazon. All rights reserved.
* See COPYING.txt for license details.
*/

namespace Codazon\SalesPro\Helper;

use Magento\Store\Model\ScopeInterface;

class Data extends \Codazon\Core\Helper\Data
{
    public function enableOneStepCheckout()
    {
        return $this->getConfig('codazon_osc/general/enable');
    }

    public function getCustomOptionsJson()
    {
        $options = [];
        $options['customPlaceOrderLabel'] = $this->getConfig('codazon_osc/customization/place_order_label');
        $options['enableOrderComment'] = (bool)$this->getConfig('codazon_osc/customization/enable_order_comment');
        $options['defaultShippingMethod'] = $this->getConfig('codazon_osc/customization/default_shipping_method');
        $options['foreceSelectShipping'] = (bool)$this->getConfig('codazon_osc/customization/force_select_shipping');
        $options['removeEstimatedShipping'] = !$this->isEstimatedShippingEnabled();
        return json_encode($options);
    }

    public function isEstimatedShippingEnabled(): bool
    {
        return $this->_scopeConfig->isSetFlag('checkout/cart/estimate_shipping', ScopeInterface::SCOPE_STORE);
    }
}
