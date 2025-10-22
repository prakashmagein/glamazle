<?php
/**
 * Copyright © 2022 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Controller\Adminhtml\Design;

use Magento\Backend\App\Action;

class Save extends AbstractDesign
{
    protected $eventName = 'themelayoutpro_themelayout_design_prepare_save';
    
    protected $_updateMsg = 'You saved this design.';
    
    protected $imageUploader;
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Codazon_ThemeLayoutPro::themelayout_design_save');
    }
    
    protected function filterData(array $rawData)
    {
        $data = $rawData;
        $imagesType = ['preview'];
        foreach ($imagesType as $image) {
            if (isset($data[$image]) && is_array($data[$image])) {
                if (!empty($data[$image]['delete'])) {
                    $data[$image] = false;
                } else {
                    if (isset($data[$image][0]['name']) && isset($data[$image][0]['tmp_name'])) {
                        $data[$image] = $data[$image][0]['name'];
                        $data[$image] = $this->moveFileFromTmp($data[$image], $image);
                    } else {
                        unset($data[$image]);
                    }
                }
            } else {
                $data[$image] = false;
            }
        }
        return $data;
    }
    
    private function getImageUploader()
    {
        if ($this->imageUploader === null) {
            $this->imageUploader = $this->_objectManager->get(
                \Codazon\Core\Model\Form\ImageUploader::class
            )->setBaseTmpPath('codazon_cache/design/tmp')
            ->setBasePath('codazon_image/design');
        }
        return $this->imageUploader;
    }
    
    protected function moveFileFromTmp($image, $imagesType)
    {
        return $this->getImageUploader()
            ->setBaseTmpPath("codazon_cache/design/{$imagesType}/tmp")
            ->setBasePath("codazon/design/{$imagesType}")
            ->moveFileFromTmp($image);
    }
    
    public function execute()
	{
        $request = $this->getRequest();
        $data = $request->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $model = $this->_objectManager->create($this->modelClass);
            $id = $this->getRequest()->getParam($this->primary);
            if ($id) {
				$model->setStoreId((int)$request->getParam('store'))->load($id);
			} else {
                unset($data[$this->primary]);
            }
            if ($store = $request->getParam('store')) {
                $data['store_id'] = $store;
            } else {
                $data['store_id'] = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            }
            
            if (isset($data['use_default']) && is_array($data['use_default'])) {
                foreach ($data['use_default'] as $attributeCode => $useDefault) {
                    if ($useDefault) {
                        $data[$attributeCode] = false;
                    }
                }
            }
            $data = $this->filterData($data);
            $model->addData($data);          
            
            $this->_eventManager->dispatch(
				$this->eventName,
				['model' => $model, 'request' => $this->getRequest()]
			);
            
            try {
				$result = $model->save();
                $this->messageManager->addSuccess($this->_updateMsg);
                if ($request->getParam('back') == 'edit') {
                    $returnParams = [$this->primary => $model->getId(), '_current' => true, 'back' => false];
                    if ($store) {
                        $returnParams['store'] = $store;
                    }
					return $resultRedirect->setPath('*/*/edit', $returnParams);
				} elseif ($request->getParam('back') == 'new') {
                    return $resultRedirect->setPath('*/*/new', []);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
				$this->messageManager->addError($e->getMessage());
			} catch (\RuntimeException $e) {
				$this->messageManager->addError($e->getMessage());
			} catch (\Exception $e) {
				$this->messageManager->addException($e, $e->getMessage());
			}
            
            $this->_getSession()->setFormData($data);
			return $resultRedirect->setPath('*/*/edit', [$this->primary => $this->getRequest()->getParam($this->primary)]);
        }
    }   
}
