<?php
/**
 *
 * Copyright © 2017 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Codazon\ThemeLayoutPro\Model\Config\Source;

class CategoryDescriptionPosition implements \Magento\Framework\Option\ArrayInterface
{  
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('Use default (Top of page)')],
            ['value' => 'above_product_list', 'label' => __('Directly Above Product List')],
            ['value' => 'below_product_list', 'label' => __('Directly Below Product List')],
        ];
    }
    
    public function toArray()
    {
        return $this->toOptionArray();
    }
}
