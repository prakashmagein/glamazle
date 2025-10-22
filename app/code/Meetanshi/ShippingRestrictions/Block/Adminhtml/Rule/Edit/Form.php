<?php

namespace Meetanshi\ShippingRestrictions\Block\Adminhtml\Rule\Edit;

use Magento\Backend\Block\Widget\Form\Generic;

class Form extends Generic
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('shippingrestrictions_rule_edit');
        $this->setTitle(__('Shipping Rules Information'));
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('*/*/save'),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
