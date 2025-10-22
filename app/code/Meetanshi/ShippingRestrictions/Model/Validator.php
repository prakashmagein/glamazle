<?php

namespace Meetanshi\ShippingRestrictions\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Model\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\State;
use Magento\Backend\App\Area\FrontNameResolver;
use Meetanshi\ShippingRestrictions\Model\ResourceModel\Rule\Collection as RulesCollection;

class Validator
{
    protected $rateAdjustments = [];

    protected $objectManager;

    private $appState;

    protected $collection;

    private $rulesCollection;

    private $context;


    public function __construct(Context $context, ObjectManagerInterface $objectManager, State $appState, RulesCollection $rulesCollection)
    {
        $this->objectManager = $objectManager;
        $this->appState = $appState;
        $this->context = $context;
        $this->rulesCollection = $rulesCollection;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     *
     * @return array
     */
    public function getAllRules($request)
    {
        /** @var \Magento\Quote\Model\Quote\Item[] $all */
        $all = $request->getAllItems();
        if (!$all) {
            return [];
        }
        $allItems = current($all);
        /** @var \Magento\Quote\Model\Quote\Address $address */
        $address = $allItems->getAddress();
        $address->setItemsToValidateRestrictions($request->getAllItems());

        if (is_null($this->collection)) {
            $this->collection = $this->rulesCollection
                ->isActive()
                ->storeFilter($request->getStoreId())
                ->customerGroupFilter($this->getCustomerGroupId($request))
                ->daysFilter();

            if ($this->appState->getAreaCode() == FrontNameResolver::AREA_CODE) {
                $this->collection->addFieldToFilter('is_admin', 1);
            }
            $this->collection->load();
        }

        $rules = [];
        /** @var \Meetanshi\ShippingRestrictions\Model\Rule $rule */
        foreach ($this->collection as $rule) {
            $rule->afterLoad();

            if ($rule->validate($address, $all)
            ) {
                $rule->setErrorMessage($rule->getErrorMessage());
                $rules[] = $rule;
            }
        }

        if ($this->appState->getAreaCode() == FrontNameResolver::AREA_CODE
        ) {
            //$address->addData($address->getOrigData());
        }

        $subtotal = $address->getSubtotal();
        $baseSubtotal = $address->getBaseSubtotal();

        $address->setSubtotal($subtotal);
        $address->setBaseSubtotal($baseSubtotal);
        return $rules;
    }

    public function getCustomerGroupId($request)
    {
        $customerGroupId = 0;

        $items = current($request->getAllItems());
        if ($items->getQuote()->getCustomerId()) {
            $customerGroupId = $items->getQuote()->getCustomer()->getGroupId();
        }

        return $customerGroupId;
    }
}
