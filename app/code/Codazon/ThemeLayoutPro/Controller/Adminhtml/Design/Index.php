<?php
/**
 * Copyright © 2022 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Controller\Adminhtml\Design;

use Magento\Backend\App\Action;

class Index extends AbstractDesign
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
	}
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Codazon_ThemeLayoutPro::themelayout_design');
    }
    
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addBreadcrumb(__('Theme Layout Pro'), __('Theme Layout Pro'));
        $resultPage->addBreadcrumb(__('Codazon Design'), __('Codazon Design'));
        $resultPage->setActiveMenu('Codazon_ThemeLayoutPro::themelayout_design');
        $resultPage->getConfig()->getTitle()->prepend(__('Codazon Design'));
        return $resultPage;
    }
}

