<?php

namespace Meetanshi\ShippingRestrictions\Block\Adminhtml\Rule\Edit\Tab;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store as SystemStore;
use Meetanshi\ShippingRestrictions\Helper\Data;

class General extends Form
{
    protected $systemStore;
    protected $formFactory;
    protected $registry;
    protected $context;
    protected $helper;

    public function __construct(SystemStore $systemStore, FormFactory $formFactory, Registry $registry, Context $context, Data $helper)
    {
        $this->systemStore = $systemStore;
        $this->formFactory = $formFactory;
        $this->registry = $registry;
        $this->context = $context;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function getTabLabel()
    {
        return __('General');
    }

    public function getTabTitle()
    {
        return __('General');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    protected function _prepareForm()
    {
        $model = $this->registry->registry('current_shippingrestrictions_rule');

        $form = $this->formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('general', ['legend' => __('General')]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
        $fieldset->addField(
            'name',
            'text',
            ['name' => 'name', 'label' => __('Rule Name'), 'title' => __('Rule Name'), 'required' => true]
        );

        $fieldset->addField(
            'is_active',
            'select',
            [
                'label' => __('Rule Status'),
                'title' => __('Rule Status'),
                'name' => 'is_active',
                'options' => $this->helper->getStatuses(),
            ]
        );
        $fieldset->addField('shipping_methods', 'multiselect', [
            'label' => __('Shipping Carriers and Methods'),
            'title' => __('Shipping Carriers and Methods'),
            'name' => 'shipping_methods[]',
            'required' => true,
            'values' => $this->helper->getAllMethods(),
        ]);

        $fieldset->addField('stores', 'multiselect', [
            'label' => __('Stores'),
            'name' => 'stores[]',
            'values' => $this->systemStore->getStoreValuesForForm(),
            'note' => __('Leave empty or select all to apply the rule to any'),
        ]);

        $fieldset->addField('customer_groups', 'multiselect', [
            'name' => 'customer_groups[]',
            'label' => __('Customer Groups'),
            'values' => $this->helper->getAllGroups(),
            'note' => __('Leave empty or select all to apply the rule to any group'),
        ]);
        $fieldset->addField('is_admin', 'select', ['label' => __('For Admin'), 'name' => 'is_admin', 'options' => $this->helper->getAdminStatus(),
            'note' => __('Set YES to apply the restriction rule for the backend orders.')]);

        $fieldset->addField('error_message', 'text', [
            'label' => __('Error Message'),
            'name' => 'error_message',
            'note' => __('Specify the restriction error message which is displayed at shipping method blocks.'),
        ]);

        $form->setValues($model->getData());
        $form->addValues(['id' => $model->getId()]);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
