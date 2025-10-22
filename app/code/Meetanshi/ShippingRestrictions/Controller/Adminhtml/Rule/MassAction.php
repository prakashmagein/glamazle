<?php

namespace Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

use Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

class MassAction extends Rule
{
    public function execute()
    {
        $ids = $this->getRequest()->getParam('rules');
        $action = $this->getRequest()->getParam('action');
        if ($ids && in_array($action, ['activate', 'inactivate', 'delete'])) {
            try {
                $status = -1;
                switch ($action) {
                    case 'delete':
                        $collection = $this->_objectManager->create('Meetanshi\ShippingRestrictions\Model\ResourceModel\Rule\Collection');
                        $collection->addFieldToFilter('rule_id', ['in' => $ids]);
                        $collection->walk($action);
                        $status = -1;
                        $message = __('You have deleted the rules.');
                        break;
                    case 'activate':
                        $status = 1;
                        $message = __('You have activated the rules.');
                        break;
                    case 'inactivate':
                        $status = 0;
                        $message = __('You have deactivated the rules.');
                        break;
                }

                if ($status > -1) {
                    $this->ruleFactory->create()->massChangeStatus($ids, $status);
                }

                $this->messageManager->addSuccessMessage($message);
                $this->_redirect('*/*/');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Unable to complete your request.') . $e->getMessage());
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->messageManager->addErrorMessage(__('Unable to find a rule to delete/activate/deactivate.'));
        $this->_redirect('*/*/');
    }
}
