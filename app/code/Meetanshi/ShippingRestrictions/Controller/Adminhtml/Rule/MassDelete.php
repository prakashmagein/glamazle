<?php

namespace Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

use Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

class MassDelete extends Rule
{
    public function execute()
    {
        $ids = $this->getRequest()->getParam('rules');

        if ($ids) {
            try {
                foreach ($ids as $id) {
                    $model = $this->ruleFactory->create()->load($id);
                    $model->delete();
                }

                $message = __('Total of %1 record(s) were successfully deleted.', count($ids));

                $this->messageManager->addSuccessMessage($message);
                $this->_redirect('*/*/');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Unable to complete your request.') . $e->getMessage());
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->messageManager->addErrorMessage(__('Unable to find a rule to delete.'));
        $this->_redirect('*/*/');
    }
}
