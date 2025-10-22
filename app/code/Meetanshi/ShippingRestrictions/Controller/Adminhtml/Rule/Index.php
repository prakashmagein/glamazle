<?php

namespace Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

use Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

class Index extends Rule
{
    public function execute()
    {
        $this->_view->loadLayout();
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Meetanshi_ShippingRestrictions::rule');
        $resultPage->getConfig()->getTitle()->prepend(__('Shipping Restrictions'));
        $resultPage->addBreadcrumb(__('Shipping Restrictions'), __('Shipping Restrictions'));
        $this->_addContent($this->_view->getLayout()->createBlock('\Meetanshi\ShippingRestrictions\Block\Adminhtml\Rule'));
        $this->_view->renderLayout();
    }
}
