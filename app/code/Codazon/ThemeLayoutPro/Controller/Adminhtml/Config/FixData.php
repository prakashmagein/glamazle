<?php
/**
 * Copyright © 2017 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Controller\Adminhtml\Config;

class FixData extends \Magento\Backend\App\Action
{   
    protected $fixDataHelper;
    
    protected $importModel;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Codazon\ThemeLayoutPro\Helper\FixData $fixDataHelper,
        \Codazon\ThemeLayoutPro\Model\Import $importModel,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);
        $this->fixDataHelper = $fixDataHelper;
        $this->importModel = $importModel;
    }
    
    public function execute()
    {
        try {
            $request = $this->getRequest();
            $problem = $request->getParam('problem') ? : 'fix_data';
            switch ($problem) {
                case 'fix_template':
                    $this->fixDataHelper->fixTemplate();
                    $this->messageManager->addSuccess(__('The main content templates were updated.'));
                    break;
                case 'fix_design':
                    $this->fixDataHelper->fixDesign();
                    $this->importModel->importDesign(); //Import new design data
                    $this->messageManager->addSuccess(__('The theme design was checked and fixed.'));
                    break;
                default:
                    $this->fixDataHelper->fixData();
                    $this->messageManager->addSuccess(__('The theme data was checked and fixed.'));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $messages = explode("\n", $e->getMessage());
            foreach ($messages as $message) {
                $this->messageManager->addError($message);
            }
        } catch (\Exception $e) {
            $this->messageManager->addException(
                $e,
                __('Something went wrong while the process.') . ' ' . $e->getMessage()
            );
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath(
            'themelayoutpro/config/index',
            [
                '_nosid' => true
            ]
        );
    }
}