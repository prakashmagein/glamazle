<?php

namespace Meetanshi\ShippingRestrictions\Block\Adminhtml\Rule;

use Magento\Backend\Block\Widget\Form\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

class Edit extends Container
{
    protected $registry = null;

    public function __construct(Context $context, Registry $registry, array $data = [])
    {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    public function getHeaderText()
    {
        $model = $this->registry->registry('current_shippingrestrictions_rule');
        if ($model->getId()) {
            $title = __('Edit Shipping Rule `%1`', $model->getName());
        } else {
            $title = __("Add new Shipping Rule");
        }
        return $title;
    }

    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_rule';
        $this->_blockGroup = 'Meetanshi_ShippingRestrictions';

        parent::_construct();

        $this->buttonList->add(
            'save_and_continue_edit',
            [
                'class' => 'save',
                'label' => __('Save and Continue Edit'),
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                ]
            ],
            10
        );
    }
}
