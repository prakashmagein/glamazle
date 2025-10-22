<?php

namespace Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

use Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

class NewActionHtml extends Rule
{
    public function execute()
    {
        $this->newConditions('actions');
    }
}
