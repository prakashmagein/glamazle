<?php

namespace Meetanshi\ShippingRestrictions\Model\Rule\Condition;

use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Directory\Model\Config\Source\Allregion;
use Magento\Shipping\Model\Config\Source\Allmethods as shippingMethods;
use Magento\Payment\Model\Config\Source\Allmethods as paymentMethods;
use Magento\Rule\Model\Condition\Context;
use Magento\Framework\Model\AbstractModel;

class Address extends AbstractCondition
{
    protected $directoryCountry;
    protected $directoryAllregion;
    protected $shippingAllmethods;
    protected $paymentAllmethods;

    public function __construct(Context $context, Country $directoryCountry, Allregion $directoryAllregion, shippingMethods $shippingAllmethods, paymentMethods $paymentAllmethods, array $data = [])
    {
        parent::__construct($context, $data);
        $this->directoryCountry = $directoryCountry;
        $this->directoryAllregion = $directoryAllregion;
        $this->shippingAllmethods = $shippingAllmethods;
        $this->paymentAllmethods = $paymentAllmethods;
    }

    public function loadAttributeOptions()
    {
        $attributes = [
            'base_subtotal_with_discount' => __('Subtotal (Excl. Tax)'),
            'base_subtotal' => __('Subtotal'),
            'total_qty' => __('Total Items Quantity'),
            'weight' => __('Total Weight'),
            'payment_method' => __('Payment Method'),
            'shipping_method' => __('Shipping Method'),
            'postcode' => __('Shipping Postcode'),
            'region' => __('Shipping Region'),
            'region_id' => __('Shipping State/Province'),
            'country_id' => __('Shipping Country'),
        ];
        $this->setAttributeOption($attributes);

        return $this;
    }

    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);
        return $element;
    }

    public function getValueElementType()
    {
        switch ($this->getAttribute()) {
            case 'dest_country_id':
            case 'dest_region_id':
            case 'shipping_method':
            case 'payment_method':
            case 'country_id':
            case 'region_id':
                return 'select';
        }

        return 'text';
    }

    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            switch ($this->getAttribute()) {
                case 'country_id':
                case 'dest_country_id':
                    $options = $this->directoryCountry->toOptionArray();
                    break;

                case 'region_id':
                case 'dest_region_id':
                    $options = $this->directoryAllregion->toOptionArray();
                    break;

                case 'shipping_method':
                    $options = $this->shippingAllmethods->toOptionArray();
                    break;

                case 'payment_method':
                    $options = $this->paymentAllmethods->toOptionArray();
                    break;

                default:
                    $options = [];
            }
            $this->setData('value_select_options', $options);
        }
        return $this->getData('value_select_options');
    }

    public function getOperatorSelectOptions()
    {
        $operators = $this->getOperatorOption();
        if ($this->getAttribute() == 'dest_street') {
            $operators = [
                '{}' => __('contains'),
                '!{}' => __('does not contain'),
                '{%' => __('starts from'),
                '%}' => __('ends with'),
            ];
        }

        $type = $this->getInputType();
        $operator = [];
        $operatorByType = $this->getOperatorByInputType();
        foreach ($operators as $k => $v) {
            if (!$operatorByType || in_array($k, $operatorByType[$type])) {
                $operator[] = ['value' => $k, 'label' => $v];
            }
        }
        return $operator;
    }

    public function getInputType()
    {
        switch ($this->getAttribute()) {
            case 'package_value':
            case 'package_weight':
            case 'package_qty':
            case 'base_subtotal':
            case 'weight':
            case 'total_qty':
                return 'numeric';

            case 'dest_country_id':
            case 'dest_region_id':
            case 'shipping_method':
            case 'payment_method':
            case 'country_id':
            case 'region_id':
                return 'select';
        }

        return 'string';
    }

    public function getDefaultOperatorInputByType()
    {
        $op = parent::getDefaultOperatorInputByType();
        $op['string'][] = '{%';
        $op['string'][] = '%}';
        return $op;
    }

    public function getDefaultOperatorOptions()
    {
        $op = parent::getDefaultOperatorOptions();
        $op['{%'] = __('starts from');
        $op['%}'] = __('ends with');

        return $op;
    }

    private function getQuoteAttributeName($conditionAttribute, $quote)
    {
        switch ($conditionAttribute) {
            case 'total_qty':
                return $quote->isVirtual() ? 'items_qty' : 'total_qty';
            default:
                return $conditionAttribute;
        }
    }

    public function validate(AbstractModel $model)
    {
        if ($model instanceof \Magento\Quote\Model\Quote\Address) {
            $quote = $model->getQuote();
            $attribute = $this->getQuoteAttributeName($this->getAttribute(), $quote);

            if ($quote->isVirtual()) {
                $model = $quote;
            }
        }

        if (!$model->hasData($attribute)) {
            $model->load($model->getId());
        }

        $value = $model->getData($attribute);

        return $this->validateAttribute($value);
    }

    public function validateAttribute($data)
    {
        if (is_object($data)) {
            return false;
        }

        if (is_string($data)) {
            $data = strtoupper($data);
        }

        $value = $this->getValueParsed();
        if (is_string($value)) {
            $value = strtoupper($value);
        }

        $operator = $this->getOperatorForValidate();

        if ($this->isArrayOperatorType() xor is_array($value)) {
            return false;
        }

        $result = false;
        switch ($operator) {
            case '{%':
                if (!is_scalar($data)) {
                    return false;
                } else {
                    $result = substr($data, 0, strlen($value)) == $value;
                }
                break;
            case '%}':
                if (!is_scalar($data)) {
                    return false;
                } else {
                    $result = substr($data, -strlen($value)) == $value;
                }
                break;
            default:
                return parent::validateAttribute($data);
                break;
        }
        return $result;
    }
}
