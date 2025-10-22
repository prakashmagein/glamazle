<?php
/**
 * Copyright © 2022 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Controller\Adminhtml\Design;

use Magento\Backend\App\Action;
use \Magento\Store\Model\Store;

class MassDelete extends AbstractDesign
{
    protected $eventName = 'themelayoutpro_themelayout_design_prepare_delete';
    
    protected $_updateMsg = 'A total of %1 record(s) have been deleted.';
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Codazon_ThemeLayoutPro::themelayout_design_save');
    }
    
    public function execute()
	{
        $selected = $this->getRequest()->getParam('selected');
        $excluded = $this->getRequest()->getParam('excluded');
        
        try {
            if ($selected) {
                $collection = $this->_objectManager->create($this->modelClass)->getCollection();
                $collection->addFieldToFilter($this->primary, ['in' => $selected]);
                $count = 0;
                if ($collection->count()) {
                    foreach ($collection->getItems() as $model) {
                        $model->delete();
                        $count++;
                    }
                }
            }
            $this->messageManager->addSuccess(__($this->_updateMsg, $count));
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/');
    }
}
