<?php

namespace Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

use Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

class NewConditionHtml extends Rule
{
    public function execute()
    {
        $this->newConditions('conditions');
    }
}
