<?php
/**
 * Copyright © 2022 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Model\Design;

use Codazon\ThemeLayoutPro\Model\ResourceModel\Design\CollectionFactory as CollectionFactory;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Request\DataPersistorInterface;

class DataProvider extends \Codazon\Core\Ui\DataProvider\Form\AbstractDataProvider
{   
    protected $_isEav = false;
    
    protected $_entityTypeCode = 'themelayout_design';
    
    protected $_registryName = 'themelayout_design';
    
    protected $_hasMultiScopes = false;
    
    protected $_imageTypes = ['preview'];
    
    protected $_imageUploaderClass = 'Codazon\Core\Model\Form\ImageUploader';
    
    protected $_imgBasePath = \Codazon\ThemeLayoutPro\Model\Design::BASE_IMAGE_PATH;
    
    protected $_collectionFactoryClass = '\Codazon\ThemeLayoutPro\Model\ResourceModel\Design\CollectionFactory';
    
    protected function getFieldsMap()
    {
        return [
            'general' => [
                'title',
                'identifier',
                'preview',
                'design_group'
            ],
        ];
    }
    
	protected function filterFields(array $rawData)
    {
        $data = $rawData;
        $imagesTypes = $this->_imageTypes;
        $imageUploader = $this->getImageUploader();
        foreach ($imagesTypes as $image)
        {
            if (isset($data[$image])) {
                $imageUploader->setBasePath($this->_imgBasePath . '/' . $image);
                $imageName = (string)$data[$image];
                unset($data[$image]);
                $data[$image][0]['name'] = $imageName;
                $data[$image][0]['url'] = $this->_getImageUrl($imageName);
                $data[$image][0]['size'] = $imageUploader->getBaseFileSize($imageName);
            }
        }
        
        if (empty($data['design_group'])) {
            $data['design_group'] = \Codazon\ThemeLayoutPro\Model\CodazonTheme::DEFAULT_NAMESPACE;
        }
        
        return $data;
    }
    
    protected function _getImageUrl($imageName) {
        return $this->getImageUploader()->getImageUrl($imageName);
    }
}

