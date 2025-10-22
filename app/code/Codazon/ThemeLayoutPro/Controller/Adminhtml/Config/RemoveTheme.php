<?php
/**
 * Copyright © 2017 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Controller\Adminhtml\Config;

use Magento\Config\Controller\Adminhtml\System\AbstractConfig;
use \Codazon\ThemeLayoutPro\Model\CodazonTheme as MainTheme;
use Magento\Theme\Model\Data\Design\Config as DesignConfig;

class RemoveTheme extends \Magento\Backend\App\Action
{
    protected $resultJsonFactory;
    
    protected $coreHelper;
    
    protected $fixDataHelper;
    
    protected function _initAction()
	{
		$resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Codazon_ThemeLayoutPro::edit');
		return $resultPage;
	}
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\Json $resultJsonFactory,
        \Codazon\Core\Helper\Data $coreHelper,
        \Codazon\ThemeLayoutPro\Helper\FixData $fixDataHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->coreHelper = $coreHelper;
        $this->fixDataHelper = $fixDataHelper;
        parent::__construct($context);
    }
    
    public function execute()
    {
        $result = [
            'success' => false,
            'message' => ''
        ];
        $themeId = $this->getRequest()->getParam('theme_id');
        try {
            $themeData = $this->fixDataHelper->removeTheme($themeId);
            $result['success'] = true;
            $result['message'] = __('The theme %1 (id: %2) was removed successfully.', "<strong>{$themeData['theme_title']}</strong>", $themeData['theme_id']);
        } catch(\Exception $e) {
            $result['message'] = $e->getMessage();
        }
        return $this->resultJsonFactory->setData($result);
    }
    
    protected function reindexGrid()
    {
        $this->indexerRegistry->get(DesignConfig::DESIGN_CONFIG_GRID_INDEXER_ID)->reindexAll();
    }
}