<?php

namespace Meetanshi\ShippingRestrictions\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class Rule extends Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_rule';
        $this->_blockGroup = 'Meetanshi_ShippingRestrictions';
        $this->_headerText = __('Shipping Restriction Rules');
        $this->_addButtonLabel = __('Add Rule');
        parent::_construct();
    }
}
