<?php

namespace Meetanshi\ShippingRestrictions\Block\Adminhtml\Rule\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Input;
use Magento\Backend\Block\Context;
use Magento\Shipping\Model\Config;
use Magento\Framework\DataObject;

class Methods extends Input
{
    private $config;

    public function __construct(Context $context, Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    public function render(DataObject $row)
    {
        $methods = $row->getData('shipping_methods');

        if (!$methods) {
            return __('Any');
        }

        $shippingMethods = $this->getShippingMethods();
        $result = [];
        $currentMethods = explode(",", $methods);

        foreach ($currentMethods as $method) {
            if (!empty($method) && array_key_exists($method, $shippingMethods)) {
                $result[] = $shippingMethods[$method];
            }
        }

        return implode("<br>", $result);
    }

    public function getShippingMethods()
    {
        $methods = [];
        $carriers = $this->config->getAllCarriers();

        foreach ($carriers as $methodCode => $carrier) {
            $carrierMethods = $carrier->getAllowedMethods();

            if (!$carrierMethods) {
                continue;
            }

            foreach ($carrierMethods as $code => $title) {
                $methods[$methodCode . '_' . $code] = '[' . $methodCode . '] ' . $title;
            }
        }

        return $methods;
    }
}
