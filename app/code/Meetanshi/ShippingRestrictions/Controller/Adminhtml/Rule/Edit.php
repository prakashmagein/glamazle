<?php

namespace Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

use Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;
use Meetanshi\ShippingRestrictions\Model\Rule as RuleModel;

class Edit extends Rule
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $ruleModel = $this->ruleFactory->create();

        if ($id) {
            $ruleModel->load($id);
            if (!$ruleModel->getId()) {
                $this->messageManager->addErrorMessage(__('Rule no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }

        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $ruleModel->setData($data);
        } else {
            $this->_prepareModel($ruleModel);
        }
        $this->registry->register('current_shippingrestrictions_rule', $ruleModel);
        $this->_initAction();
        if ($ruleModel->getId()) {
            $title = __('Edit Shipping Restrictions Rule `%1`', $ruleModel->getName());
        } else {
            $title = __("Add new Shipping Restrictions Rule");
        }
        $this->_view->getPage()->getConfig()->getTitle()->prepend($title);
        $this->_view->renderLayout();
    }

    public function _prepareModel(RuleModel $ruleModel)
    {
        $fields = ['stores', 'customer_groups', 'shipping_carriers', 'days'];
        foreach ($fields as $field) {
            $value = $ruleModel->getData($field);
            if (!is_array($value)) {
                $ruleModel->setData($field, explode(',', $value));
            }
        }

        $ruleModel->getConditions()->setJsFormObject('rule_conditions_fieldset');
        return true;
    }
}
