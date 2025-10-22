<?php

namespace Meetanshi\ShippingRestrictions\Model;

use Magento\Rule\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Meetanshi\ShippingRestrictions\Model\Rule\Condition\Combine as ConditionCombine;
use Magento\SalesRule\Model\Rule\Condition\Product\Combine as ProductCombine;

class Rule extends AbstractModel
{
    protected $objectManager;
    protected $storeManager;
    const PRODUCT_CONDITION = 'Magento\\SalesRule\\Model\\Rule\\Condition\\Product';
    protected $serializer;
    protected $conditionCombine;
    protected $productCombine;

    public function __construct(Context $context, Registry $registry, FormFactory $formFactory, TimezoneInterface $localeDate, Json $serializer, StoreManagerInterface $storeManager, ObjectManagerInterface $objectManager, ConditionCombine $conditionCombine, ProductCombine $productCombine, array $data = [])
    {
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->conditionCombine = $conditionCombine;
        $this->productCombine = $productCombine;
        parent::__construct($context, $registry, $formFactory, $localeDate, null, null, $data);
    }

    public function validate(DataObject $object, $items = null)
    {
        return parent::validate($object);
    }

    public function getConditionsInstance()
    {
        return $this->objectManager->create('Meetanshi\ShippingRestrictions\Model\Rule\Condition\Combine');
    }

    public function getActionsInstance()
    {
        return $this->objectManager->create('Magento\SalesRule\Model\Rule\Condition\Product\Combine');
    }

    public function massChangeStatus($ids, $status)
    {
        return $this->getResource()->massChangeStatus($ids, $status);
    }

    public function restrictMethod($rate)
    {
        $carriers = explode(',', $this->getShippingCarriers());

        if (in_array($rate->getCarrier(), $carriers)) {
            return true;
        }
        $methods = $this->getShippingMethods();

        if (!$methods) {
            return false;
        }
        $methods = array_unique(explode(',', $methods));
        $carrierCode = $rate->getCarrier() . '_' . $rate->getMethod();

        foreach ($methods as $name) {
            if ($carrierCode == $name) {
                return true;
            }
        }

        return false;
    }


    public function afterSave()
    {
        $productAttributes = array_merge(
            $this->_getUsedAttributes($this->getConditionsSerialized()),
            $this->_getUsedAttributes($this->getActionsSerialized())
        );

        if (count($productAttributes)) {
            $this->getResource()->saveAttributes($this->getId(), $productAttributes);
        }

        return parent::afterSave();
    }

    protected function _getUsedAttributes($serializedString)
    {
        $result = [];
        $serializedString = $this->serializer->unserialize($serializedString);

        if (is_array($serializedString) && array_key_exists('conditions', $serializedString)) {
            $result = $this->recursiveAttributes($serializedString);
        }

        return $result;
    }

    protected function recursiveAttributes($loop)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($loop)
        );

        $result = [];
        $attribute = false;
        foreach ($iterator as $key => $value) {
            if ($key == 'type' && $value == self::PRODUCT_CONDITION) {
                $attribute = true;
            }

            if ($key == 'attribute' && $attribute) {
                $result[] = $value;
                $attribute = false;
            }
        }

        return $result;
    }

    public function beforeSave()
    {
        $this->_setWebsiteIds();
        return parent::beforeSave();
    }

    protected function _setWebsiteIds()
    {
        $websites = [];

        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $websites[$store->getId()] = $website->getId();
                }
            }
        }

        $this->setOrigData('website_ids', $websites);
    }

    public function beforeDelete()
    {
        $this->_setWebsiteIds();
        return parent::beforeDelete();
    }

    protected function _construct()
    {
        $this->_init('Meetanshi\ShippingRestrictions\Model\ResourceModel\Rule');
        parent::_construct();
    }
}
