<?php

namespace Telr\TelrPayments\Model;

/**
 * Telr Apple Pay payment method model
 */
class TelrApplePay extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'telr_applepay';


    public function getConfig($key){
        return $this->getConfigData($key);
    }
}