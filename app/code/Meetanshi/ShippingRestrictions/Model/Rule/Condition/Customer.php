<?php

namespace Meetanshi\ShippingRestrictions\Model\Rule\Condition;

use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Customer\Model\Session;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;
use Magento\Framework\Model\AbstractModel;
use Magento\Customer\Model\Customer as CustomerModel;

class Customer extends AbstractCondition
{
    const CUSTOMER_EAV_ATTRIBUTES = 'customer_attributes';

    const VALUE_SELECT_OPTIONS = 'value_select_options';

    const EXCLUDE_ATTRIBUTES = [
        'created_in',
        'disable_auto_group_change',
        'default_billing',
        'default_shipping',
        'lock_expires',
        'first_failure',
        'group_id',
        'failures_num',
        'confirmation',
    ];
    const ALLOWED_INPUT_TYPES = [
        'checkbox',
        'checkboxes',
        'date',
        'fieldset',
        'multiselect',
        'radio',
        'radios',
        'select',
        'text',
        'textarea'
    ];
    const INPUT_TYPES = ['string', 'numeric', 'date', 'select', 'multiselect', 'grid', 'boolean'];


    private $remoteAddress;
    private $customerResource;

    private $customerFactory;

    private $customerSession;

    public function __construct(
        Context $context,
        CustomerResourceModel $customerResource,
        CustomerFactory $customerFactory,
        Session $customerSession,
        RemoteAddress $remoteAddress,
        array $data = []
    )
    {
        $this->customerResource = $customerResource;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->remoteAddress = $remoteAddress;
        parent::__construct($context, $data);
    }

    public function loadAttributeOptions()
    {
        parent::loadAttributeOptions();
        $attributesByCode = $this->customerResource
            ->loadAllAttributes()
            ->getAttributesByCode();

        $attributes = [];

        foreach ($attributesByCode as $attri) {
            if (!($attri->getFrontendLabel()) || !($attri->getAttributeCode())) {
                continue;
            }

            if (in_array($attri->getAttributeCode(), self::EXCLUDE_ATTRIBUTES)) {
                continue;
            }
            $attributes[$attri->getAttributeCode()] = $attri->getFrontendLabel();
        }

        $this->addCustomAttributes($attributes);
        asort($attributes);
        $this->setAttributeOption($attributes);

        return $this;
    }

    private function addCustomAttributes(array &$attr)
    {
        $attr['id'] = __('Customer ID');
        $attr['ip_address'] = __('Customer IP');

        return $this;
    }

    public function getAttributeElement()
    {
        $ele = parent::getAttributeElement();
        $ele->setShowAsText(true);

        return $ele;
    }

    public function getValue()
    {
        if ($this->getInputType() == 'date' && !$this->getIsValueParsed()) {
            $this->setValue(
                (new \DateTime($this->getData('value')))->format('Y-m-d')
            );
            $this->setIsValueParsed(true);
        }

        return $this->getData('value');
    }

    public function getInputType()
    {
        if ($this->getAttribute() === 'entity_id') {
            return 'string';
        }

        $attributeObject = $this->getAttributeObject();

        if (!$attributeObject) {
            return parent::getInputType();
        }

        return $this->getInputTypeFromAttribute($attributeObject);
    }

    private function getAttributeObject()
    {
        return $this->customerResource->getAttribute($this->getAttribute());
    }

    private function getInputTypeFromAttribute($attributeObject)
    {
        if (!is_object($attributeObject)) {
            $attributeObject = $this->getAttributeObject();
        }

        if (in_array($attributeObject->getFrontendInput(), self::INPUT_TYPES)) {
            return $attributeObject->getFrontendInput();
        }

        switch ($attributeObject->getFrontendInput()) {
            case 'gallery':
            case 'media_image':
        }

        return 'string';
    }

    public function getValueElement()
    {
        $ele = parent::getValueElement();

        switch ($this->getInputType()) {
            case 'date':
                $ele->setClass('hasDatepicker');
                $ele->setExplicitApply(true);
                break;
        }

        return $ele;
    }

    public function getValueElementType()
    {
        $attributeObject = $this->getAttributeObject();

        if ($this->getAttribute() === 'entity_id') {
            return 'text';
        }

        if (!is_object($attributeObject)) {
            return parent::getValueElementType();
        }

        if (in_array($attributeObject->getFrontendInput(), self::ALLOWED_INPUT_TYPES)) {
            return $attributeObject->getFrontendInput();
        }

        switch ($attributeObject->getFrontendInput()) {
            case 'boolean':
                return 'select';

                return 'multiselect';
        }

        return parent::getValueElementType();
    }

    public function getValueSelectOptions()
    {
        $options = [];
        $attribute = $this->getAttributeObject();

        if (is_object($attribute) && $attribute->usesSource()) {
            $option = true;
            if ($attribute->getFrontendInput() === 'multiselect') {
                $option = false;
            }
            $options = $attribute->getSource()->getAllOptions($option);
        }

        if (!$this->hasData(self::VALUE_SELECT_OPTIONS)) {
            $this->setData(self::VALUE_SELECT_OPTIONS, $options);
        }

        return $this->getData(self::VALUE_SELECT_OPTIONS);
    }

    public function validate(AbstractModel $model)
    {
        $customer = $model;
        if (!$customer instanceof CustomerModel) {
            $customer = $model->getQuote()->getCustomer();
            if (!$customer->getId()) {
                $customer = $this->customerSession->getCustomer();
            }

            $attribute = $this->getAttribute();
            $allAttributes = $customer instanceof CustomerModel
                ? $customer->getData()
                : $customer->__toArray();
            if ($attribute === 'ip_address') {
                $allAttributes[$attribute] = $this->remoteAddress->getRemoteAddress();
            }

            if ($attribute !== 'entity_id' && !array_key_exists($attribute, $allAttributes)) {
                if (isset($allAttributes[self::CUSTOMER_EAV_ATTRIBUTES])
                    && array_key_exists($attribute, $allAttributes[self::CUSTOMER_EAV_ATTRIBUTES])
                ) {
                    $customAttribute = $this->customerResource->getAttribute($attribute);
                    $attributeValue = $allAttributes[self::CUSTOMER_EAV_ATTRIBUTES][$attribute]['value'];

                    if ($customAttribute->getFrontendInput() === 'multiselect') {
                        $attributeValue = explode(',', $attributeValue);
                    }

                    $allAttributes[$attribute] = $attributeValue;
                } else {
                    $address = $model->getQuote()->getBillingAddress();
                    $allAttributes[$attribute] = $address->getData($attribute);
                }
            }

            $customer = $this->customerFactory->create()->setData($allAttributes);
        }

        return parent::validate($customer);
    }
}

