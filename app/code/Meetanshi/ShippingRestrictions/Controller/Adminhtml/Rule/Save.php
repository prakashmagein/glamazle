<?php

namespace Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

use Meetanshi\ShippingRestrictions\Controller\Adminhtml\Rule;

class Save extends Rule
{
    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            try {
                $ruleModel = $this->ruleFactory->create();
                $data = $this->getRequest()->getPostValue();
                $filter = new \Zend_Filter_Input([], [], $data);
                $data = $filter->getUnescaped();
                $id = $this->getRequest()->getParam('id');
                if ($id) {
                    $ruleModel->load($id);
                    if ($id != $ruleModel->getId()) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('The wrong item is specified.'));
                    }
                }
                if (isset($data['rule']['conditions'])) {
                    $data['conditions'] = $data['rule']['conditions'];
                }

                unset($data['rule']);

                $ruleModel->addData($data);
                $ruleModel->loadPost($data);
                $this->_prepareData($ruleModel);

                $session = $this->_objectManager->get('Magento\Backend\Model\Session');
                $session->setPageData($ruleModel->getData());
                $ruleModel->save();
                $this->messageManager->addSuccessMessage(__('You have successfully saved the rule.'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['id' => $ruleModel->getId()]);
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while saving the item data.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    protected function _prepareData($ruleModel)
    {
        $fields = ['stores', 'customer_groups', 'shipping_methods', 'days'];
        foreach ($fields as $field) {
            $value = $ruleModel->getData($field);
            if($field == 'shipping_methods')
            {
                foreach($value as $val){
                    $parts = preg_split('~_(?=[^_]*$)~', $val);
                    $carriers[] = $parts[0];
                }
                $ruleModel->setData('shipping_carriers', '');
                $ruleModel->setData('shipping_methods', '');
                if (is_array($value)) {
                    $ruleModel->setData('shipping_methods', ',' . implode(',', $value) . ',');
                    $ruleModel->setData('shipping_carriers', ',' . implode(',', array_unique($carriers)) . ',');
                }
            }
            else{
                $ruleModel->setData($field, '');
                if (is_array($value)) {
                    $ruleModel->setData($field, ',' . implode(',', $value) . ',');
                }
            }

        }
        return true;
    }
}
