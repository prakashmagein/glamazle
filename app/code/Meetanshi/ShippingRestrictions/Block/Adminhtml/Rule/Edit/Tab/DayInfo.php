<?php

namespace Meetanshi\ShippingRestrictions\Block\Adminhtml\Rule\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Store\Model\System\Store;
use Meetanshi\ShippingRestrictions\Helper\Data;

class DayInfo extends Generic implements TabInterface
{
    protected $systemStore;
    protected $helper;
    protected $registry;
    protected $formFactory;

    public function __construct(Context $context, Registry $registry, FormFactory $formFactory, Store $systemStore, Data $helper)
    {
        $this->systemStore = $systemStore;
        $this->helper = $helper;
        $this->registry = $registry;
        $this->formFactory = $formFactory;
        parent::__construct($context, $registry, $formFactory);
    }

    public function getTabLabel()
    {
        return __('Days & Time');
    }

    public function getTabTitle()
    {
        return __('Days & Time');
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

        $fieldset = $form->addFieldset('dayinfo', ['legend' => __('Days and Time')]);

        $fieldset->addField(
            'days',
            'multiselect',
            [
                'label' => __('Days of the Week'),
                'name' => 'days[]',
                'values' => $this->helper->getDays(),
                'note' => __('Leave empty or select all to apply the rule every day'),
            ]
        );

        $fieldset->addField(
            'from_time',
            'select',
            [
                'label' => __('Time From:'),
                'name' => 'from_time',
                'values' => $this->helper->getTime(),
            ]
        );

        $fieldset->addField(
            'to_time',
            'select',
            [
                'label' => __('Time To:'),
                'name' => 'to_time',
                'values' => $this->helper->getTime(),
            ]
        );

        $form->setValues($model->getData());
        $form->addValues(['id' => $model->getId()]);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
