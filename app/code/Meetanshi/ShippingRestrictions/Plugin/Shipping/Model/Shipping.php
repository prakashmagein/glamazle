<?php

namespace Meetanshi\ShippingRestrictions\Plugin\Shipping\Model;

use Meetanshi\ShippingRestrictions\Model\Validator;
use Magento\Shipping\Model\Shipping as ShippingModel;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;

class Shipping
{
    protected $validator;
    private $rateErrorFactory;

    public function __construct(Validator $validator, ErrorFactory $rateErrorFactory)
    {
        $this->validator = $validator;
        $this->rateErrorFactory = $rateErrorFactory;
    }

    /**
     * @param ShippingModel $subject
     * @param \Closure $closure
     * @param RateRequest $request
     * @return ShippingModel
     */
    public function aroundCollectRates(ShippingModel $shipping, \Closure $closure, RateRequest $request
    ) {
        $closure($request);
        $result = $shipping->getResult();
        $rates = $result->getAllRates();

        if (!count($rates)) {
            return $shipping;
        }

        $rules = $this->validator->getAllRules($request);

        if (!count($rules)) {
            return $shipping;
        }
        $result->reset();

        $errorMessage = __(
            'Sorry, no shipping quotes are available for the selected products and destination'
        );
        $restrictRate = null;
        $isError = false;
        $rate = [];
        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $rate */
        foreach ($rates as $rate) {
            /** @var \Meetanshi\ShippingRestrictions\Model\Rule $rule */
            $restrict = false;
            foreach ($rules as $rule) {
                if ($rule->restrictMethod($rate)) {
                    $restrictRate = $rate;
                    $errorMessage = $rule->getErrorMessage();
                    if (!empty($errorMessage)) {
                        $error = $this->rateErrorFactory->create();
                        $error->setCarrier($restrictRate['carrier']);
                        $error->setCarrierTitle($restrictRate['carrier_title']);
                        $error->setErrorMessage($errorMessage);

                        $result->append($error);
                    }
                    $restrict = true;
                    break;
                }
            }
            if (!$restrict) {
                $result->append($rate);
                $isError = true;
            }
        }

        return $shipping;
    }
}
