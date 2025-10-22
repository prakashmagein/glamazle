<?php

namespace TotalPay\Gateway\Model\Config\Source\Options;

/**
 * Checkout Payment Method Types Model Source
 * Class PaymentMethodType
 * @package TotalPay\Gateway\Model\Config\Source\Method\Options
 */
class IframeType implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'lightbox', 'label' => __('Lightbox')],
            ['value' => 'embedded', 'label' => __('Embedded')],
        ];
    }
}
