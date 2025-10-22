<?php

namespace Meetanshi\ShippingRestrictions\Model\Rule\Condition;

use Magento\Rule\Model\Condition\Context;
use Magento\Rule\Model\Condition\Combine as ConditionCombine;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject;
use Meetanshi\ShippingRestrictions\Model\Rule\Condition\Customer as CustomerAttributes;

class Combine extends ConditionCombine
{
    protected $objectManager;

    protected $eventManager = null;
    protected $customerAttributes;

    public function __construct(Context $context, ObjectManagerInterface $objectManager, ManagerInterface $eventManager, CustomerAttributes $customerAttributes, array $data = [])
    {
        parent::__construct($context, $data);
        $this->objectManager = $objectManager;
        $this->eventManager = $eventManager;
        $this->customerAttributes = $customerAttributes;
        $this->setType('Meetanshi\ShippingRestrictions\Model\Rule\Condition\Combine');
    }

    public function getNewChildSelectOptions()
    {
        $addressCondition = $this->objectManager->create('Meetanshi\ShippingRestrictions\Model\Rule\Condition\Address');
        $addressAttributes = $addressCondition->loadAttributeOptions()->getAttributeOption();

        $attributes = [];
        foreach ($addressAttributes as $code => $label) {
            $attributes[] = ['value' => 'Meetanshi\ShippingRestrictions\Model\Rule\Condition\Address|' . $code, 'label' => $label];
        }

        $customerAttri = $this->customerAttributes->loadAttributeOptions()->getAttributeOption();
        $custAttributes = [];
        foreach ($customerAttri as $code => $label) {
            $custAttributes[] = ['value' => 'Meetanshi\ShippingRestrictions\Model\Rule\Condition\Customer|' . $code, 'label' => $label];
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive($conditions, [
            [
                'value' => \Magento\SalesRule\Model\Rule\Condition\Product\Found::class,
                'label' => __('Product attribute combination')
            ],
            ['value' => 'Meetanshi\ShippingRestrictions\Model\Rule\Condition\Product\Subselect', 'label' => __('Products subselection')],
            ['label' => __('Conditions combination'), 'value' => $this->getType()],
            ['label' => __('Cart Attribute'), 'value' => $attributes],
            ['label' => __('Customer Attribute'), 'value' => $custAttributes]
        ]);

        $additional = new DataObject();
        $this->eventManager->dispatch('salesrule_rule_condition_combine', ['additional' => $additional]);
        $additionalConditions = $additional->getConditions();
        if ($additionalConditions) {
            $conditions = array_merge_recursive($conditions, $additionalConditions);
        }

        return $conditions;
    }

    public function validateNotModel($entity)
    {
        if (!$this->getConditions()) {
            return true;
        }

        $aggregator = $this->getAggregator() === 'all';
        $true = (bool)$this->getValue();

        foreach ($this->getConditions() as $condition) {
            if ($entity instanceof AbstractModel) {
                $validated = $condition->validate($entity);
            } elseif ($entity instanceof DataObject
                && method_exists($condition, 'validateNotModel')
            ) {
                $validated = $condition->validateNotModel($entity);
            } elseif ($entity instanceof DataObject) {
                $attribute = $entity->getData($condition->getAttribute());
                $validated = $condition->validateAttribute($attribute);
            } else {
                $validated = $condition->validateByEntityId($entity);
            }
            if ($aggregator && $validated !== $true) {
                return false;
            } elseif (!$aggregator && $validated === $true) {
                return true;
            }
        }
        return $aggregator ? true : false;
    }
}
