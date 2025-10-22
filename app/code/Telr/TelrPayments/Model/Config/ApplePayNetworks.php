<?php

namespace Telr\TelrPayments\Model\Config;
class ApplePayNetworks implements \Magento\Framework\Option\ArrayInterface
{
    const AMEX = 'amex';
    const MASTERCARD = 'masterCard';
    const VISA = 'visa';
    const MADA = 'mada';
    
    /**
     * Apple Button Type Array
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::AMEX,
                'label' => __('amex')
            ],
            [
                'value' => self::MASTERCARD,
                'label' => __('master card')
            ],
            [
                'value' => self::VISA,
                'label' => __('visa')
            ],
            [
                'value' => self::MADA,
                'label' => __('mada')
            ]
        ];
    }
}
