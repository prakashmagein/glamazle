<?php

namespace Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

use Magento\Framework\App\ResponseInterface;
use Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

class NewAction extends Rule
{
    public function execute()
    {
        $this->_forward('edit');
    }
}
