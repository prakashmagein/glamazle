<?php

namespace Meetanshi\ShippingRestrictions\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Rule\Model\Condition\AbstractCondition;
use Meetanshi\ShippingRestrictions\Model\RuleFactory;
use Magento\CatalogRule\Model\Rule as CatalogRule;

abstract class Rule extends Action
{
    protected $registry;
    protected $resultForwardFactory;
    protected $resultPageFactory;
    protected $ruleFactory;
    protected $catalogRule;

    public function __construct(Context $context, Registry $registry, ForwardFactory $resultForwardFactory, PageFactory $resultPageFactory, RuleFactory $ruleFactory, CatalogRule $catalogRule)
    {
        $this->registry = $registry;
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->ruleFactory = $ruleFactory;
        $this->catalogRule = $catalogRule;
    }

    public function newConditions($prefix)
    {
        $id = $this->getRequest()->getParam('id');
        $request = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $request[0];

        $model = $this->_objectManager->create($type)
            ->setId($id)
            ->setType($type)
            ->setRule($this->catalogRule)
            ->setPrefix($prefix);
        if (!empty($request[1])) {
            $model->setAttribute($request[1]);
        }

        if ($model instanceof AbstractCondition) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }

    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Meetanshi_ShippingRestrictions::rule')->_addBreadcrumb(__('Shipping Rules'), __('Shipping Rules'));
        return $this;
    }

    protected function _isAllowed()
    {
        return true;
    }
}
