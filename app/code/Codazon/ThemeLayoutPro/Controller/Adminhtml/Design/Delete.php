<?php
/**
 * Copyright © 2022 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Controller\Adminhtml\Design;

use Magento\Backend\App\Action;
use \Magento\Store\Model\Store;

class Delete extends AbstractDesign
{
    protected $eventName = 'themelayoutpro_themelayout_design_prepare_delete';
    
    protected $_updateMsg = 'Codazon Design was deleted.';
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Codazon_ThemeLayoutPro::themelayout_design_save');
    }
    
    public function execute()
	{
        $request = $this->getRequest();
        $data = $request->getParams();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $model = $this->_objectManager->create($this->modelClass);
            $id = $this->getRequest()->getParam($this->primary);
            $store = (int)$request->getParam('store', Store::DEFAULT_STORE_ID);
            $this->_eventManager->dispatch(
				$this->eventName,
				['model' => $model, 'request' => $this->getRequest()]
			);
            try {
                $model->setStoreId($store)->load($id);
				$result = $model->delete();
                $this->messageManager->addSuccess($this->_updateMsg);
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
				$this->messageManager->addError($e->getMessage());
			} catch (\RuntimeException $e) {
				$this->messageManager->addError($e->getMessage());
			} catch (\Exception $e) {
				$this->messageManager->addException($e, $e->getMessage());
			}
            
			return $resultRedirect->setPath('*/*/edit', [$this->primary => $this->getRequest()->getParam($this->primary)]);
        }
    }   
}
