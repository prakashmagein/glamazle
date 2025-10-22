<?php

namespace Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

use Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

class Duplicate extends Rule
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('rule_id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('Please select a shipping rule to duplicate.'));
            return $this->_redirect('*/*');
        }
        try {
            $ruleModel = $this->ruleFactory->create()->load($id);
            if (!$ruleModel->getId()) {
                $this->messageManager->addErrorMessage(__('Rule no longer exists.'));
                $this->_redirect('*/*');
                return;
            }

            $rule = clone $ruleModel;
            $rule->setIsActive(0);
            $rule->setId(null);
            $rule->save();
            $this->messageManager->addSuccess(__('Rule has been duplicated. Please activate it.'));

            return $this->_redirect('*/*/edit', ['id' => $rule->getId()]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to duplicate rule..'));
            $this->_redirect('*/*');
            return;
        }
    }
}
