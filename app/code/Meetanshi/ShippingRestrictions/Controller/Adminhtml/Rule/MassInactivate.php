<?php

namespace Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

use Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

class MassInactivate extends Rule
{
    public function execute()
    {
        $ids = $this->getRequest()->getParam('rules');
        if ($ids) {
            try {
                $this->ruleFactory->create()->massChangeStatus($ids, 0);
                $this->messageManager->addSuccessMessage(__('You have deactivated the rules.'));
                $this->_redirect('*/*/');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Unable to complete your request.') . $e->getMessage());
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->messageManager->addErrorMessage(__('Unable to find a rule to deactivated'));
        $this->_redirect('*/*/');
    }
}
