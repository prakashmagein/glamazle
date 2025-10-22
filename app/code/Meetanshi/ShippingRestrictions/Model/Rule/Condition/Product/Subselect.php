<?php

namespace Meetanshi\ShippingRestrictions\Model\Rule\Condition\Product;

use Magento\Rule\Model\Condition\Context;
use Magento\SalesRule\Model\Rule\Condition\Product;
use Magento\SalesRule\Model\Rule\Condition\Product\Subselect as ProductSubSelect;
use Magento\SalesRule\Model\Rule\Condition\Product\Combine;
use Magento\Framework\Model\AbstractModel;

class Subselect extends ProductSubSelect
{
    public function __construct(Context $context, Product $ruleConditionProduct, array $data = [])
    {
        parent::__construct($context, $ruleConditionProduct, $data);
        $this->setType('Meetanshi\ShippingRestrictions\Model\Rule\Condition\Product\Subselect')->setValue(null);
    }

    public function loadAttributeOptions()
    {
        $this->setAttributeOption(
            [
                'qty' => __('total quantity'),
                'base_row_total' => __('total amount excl. tax'),
                'base_row_total_incl_tax' => __('total amount incl. tax'),
                'row_weight' => __('total weight'),
            ]
        );
        return $this;
    }

    public function validate(AbstractModel $object)
    {
        return $this->validateNotModel($object);
    }

    public function validateNotModel($object)
    {
        $attribute = $this->getAttribute();
        $total = 0;
        $items = $object->getAllItems() ? $object->getAllItems() : $object->getItemsToValidateRestrictions();
        if ($items) {
            $itemIds = [];
            foreach ($items as $item) {
                if ($item->getProduct()->getTypeId() == 'configurable') {
                    $item->getProduct()->setTypeId('skip');

                    foreach ($item->getChildren() as $child) {
                        $data[$child->getId()] = [
                            'base_row_total' => $child->getBaseRowTotal(),
                            'price' => $child->getPrice(),
                        ];

                        $child->setBaseRowTotal(
                            $item->getBaseRowTotal()
                        )->setPrice(
                            $item->getPrice()
                        );
                    }
                }

                if (Combine::validate($item)) {
                    if (!($itemParentId = $item->getParentItemId())) {
                        $itemIds[] = $item->getItemId();
                    } else {
                        if (in_array($itemParentId, $itemIds)) {
                            continue;
                        } else {
                            $itemIds[] = $itemParentId;
                        }
                    }

                    $total += $item->getData($attribute);
                }

                if ($item->getProduct()->getTypeId() === 'skip') {
                    $item->getProduct()->setTypeId('configurable');
                }

                if (isset($data[$item->getId()])) {
                    $item->setBaseRowTotal(
                        $data[$item->getId()]['base_row_total']
                    )->setPrice(
                        $data[$item->getId()]['price']
                    );
                }
            }
        }

        return $this->validateAttribute($total);
    }
}
