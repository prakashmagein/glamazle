<?php

namespace Meetanshi\ShippingRestrictions\Block\Adminhtml\Rule\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Rule\Block\Actions;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;

class Products extends Generic implements TabInterface
{
    protected $rendererFieldset;
    protected $actions;

    public function __construct(Context $context, Registry $registry, FormFactory $formFactory, Actions $actions, Fieldset $rendererFieldset)
    {
        $this->rendererFieldset = $rendererFieldset;
        $this->actions = $actions;
        parent::__construct($context, $registry, $formFactory);
    }

    public function getTabLabel()
    {
        return __('Products Conditions');
    }

    public function getTabTitle()
    {
        return __('Products Conditions');
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
        $model = $this->_coreRegistry->registry('current_shippingrestrictions_rule');
        $form = $this->_formFactory->create();

        $renderer = $this->rendererFieldset->setTemplate(
            'Magento_CatalogRule::promo/fieldset.phtml'
        )->setNewChildUrl(
            $this->getUrl('*/*/newActionHtml/form/rule_actions_fieldset')
        );

        $fieldset = $form->addFieldset(
            'rule_actions_fieldset',
            [
                'legend' => __(
                    'Select products or leave blank for all products.'
                )
            ]
        )->setRenderer(
            $renderer
        );

        $fieldset->addField(
            'actions',
            'text',
            ['name' => 'actions', 'label' => __('Conditions'), 'title' => __('Conditions')]
        )->setRule(
            $model
        )->setRenderer(
            $this->actions
        );

        $form->setValues($model->getData());
        $form->addValues(['id' => $model->getId()]);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
