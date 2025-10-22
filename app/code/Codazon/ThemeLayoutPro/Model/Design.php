<?php
/**
 * Copyright © 2022 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Model;

class Design extends \Magento\Framework\Model\AbstractModel
{
    protected $coreHelper;
    
    protected $fileHelper;
    
    protected $styleHelper;
    
    const XML_PATH_THEME_ID = 'themelayoutpro/design/theme_id';
    
    const PREVIEW_IMAGE_PATH = 'codazon/design/preview';
    
    protected $previewDir = 'codazon/design/preview';
    
    const BASE_IMAGE_PATH = 'codazon/design';
    
    protected $configFile = 'theme_config.xml';
    
    const PARENT_DIR = 'codazon/design/project';
    
    protected $parentDir = 'codazon/design/project';
    
    protected $parentPath;
    
    protected $imageHelper;
    
    protected $previewImagePath;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Codazon\Core\Helper\Data $coreHelper,
        \Codazon\Core\Helper\FileManager $fileHelper,
        \Codazon\Core\Helper\Styles $styleHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, null, null, $data);
        $this->coreHelper = $coreHelper;
        $this->fileHelper = $fileHelper;
        $this->styleHelper = $styleHelper;
        $this->parentPath = $styleHelper->getMediaDir($this->parentDir);
    }

    const ENTITY = 'themelayout_design';
    
    protected function _construct()
    {
        $this->_init('Codazon\ThemeLayoutPro\Model\ResourceModel\Design');
    }
    
    public function getProjectPath($file = '')
    {
        return $this->parentPath . '/' . $this->getData('design_group') . '/' .
            $this->getData('identifier') . ($file ? '/' . $file : '');
    }
    
    public function getConfigFilePath()
    {
        return $this->getProjectPath($this->configFile);
    }
    
    public function getPreviewImagePath()
    {
        if ($preview = $this->getData('preview')) {
            return $this->styleHelper->getMediaDir($this->previewDir . '/' . $preview);
        }
        return null;
    }
    
    public function getPreviewImageUrl($width = null, $height = null)
    {
        if ($preview = $this->getData('preview')) {
            $file = $this->previewDir . '/' . $preview;
            if ($width !== null || $height !== null) {
                $imageHelper = $this->getImageHelper();
                return $imageHelper->init($file)->resize($width, $height)->getUrl();
            } else {
                return $this->coreHelper->getMediaUrl($file);
            }
        } else {
            return $this->coreHelper->getMediaUrl($this->previewDir.'.jpg');
        }
    }
    
    public function getThemeTitle()
    {
        return $this->getData('title');
    }
    
    public function beforeSave()
    {
        if ($this->isObjectNew()) {
            $identifier = $this->getData('identifier');
            $group = $this->getData('design_group');
            $collection = $this->getCollection()
                ->addFieldToFilter('identifier', $identifier)
                ->addFieldToFilter('design_group', $group);
            if ($collection->count()) {
                throw new \Exception(__("There is another object with the same identifier and design group: \"%1\" & \"%2\".", $identifier, $group));
            }
        }
        return parent::beforeSave();
    }
    
    public function afterSave()
    {
        $projectPath = $this->getProjectPath();
        if (!$this->fileHelper->fileExists($projectPath)) {
            $this->fileHelper->getIo()->mkdir($projectPath);
        }
        $configFile = $this->getConfigFilePath();
        if (!$this->fileHelper->fileExists($configFile)) {
            $this->fileHelper->getIo()->cp($this->parentPath . '/' . $this->configFile, $configFile);
        }
        return parent::afterSave();
    }
    
    public function beforeDelete()
    {
        $this->previewImagePath = $this->getPreviewImagePath();
        return parent::beforeDelete();
    }
    
    public function afterDelete()
    {
        
        $projectPath = $this->getProjectPath();
        $this->fileHelper->getIo()->rmdirRecursive($projectPath);
        if ($this->previewImagePath !== null) {
            $this->fileHelper->getIo()->rm($this->previewImagePath);
        }
        return parent::afterDelete();
    }
    
    public function getImageHelper()
    {
        if ($this->imageHelper === null) {
            $this->imageHelper = $this->coreHelper->getImageHelper();
        }
        return $this->imageHelper;
    }
}
