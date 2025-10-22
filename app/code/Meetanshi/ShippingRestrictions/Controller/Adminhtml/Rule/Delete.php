<?php

namespace Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

use Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

class Delete extends Rule
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $ruleModel = $this->ruleFactory->create();
                $ruleModel->load($id);
                $ruleModel->delete();
                $this->messageManager->addSuccessMessage(__('You have successfully deleted the item.'));
                $this->_redirect('*/*/');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Unable to delete item right now.'));
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->messageManager->addErrorMessage(__('Can\'t find a item to delete.'));
        $this->_redirect('*/*/');
    }
}
