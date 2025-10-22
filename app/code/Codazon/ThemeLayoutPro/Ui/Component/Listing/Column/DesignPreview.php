<?php
/**
 * Copyright © 2022 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class DesignPreview extends \Magento\Ui\Component\Listing\Columns\Column
{
    protected $_editUrl = 'themelayoutpro/design/edit';
    
    protected $_primary = 'theme_id';
    
    protected $_imageHelper;
    
    protected $_imageUploader;
    
    protected $_basePath = 'codazon/design/preview';
    
    protected $_collectionClass = 'Codazon\ThemeLayoutPro\Model\ResourceModel\Design\Collection';
    
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
		$this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$this->_imageHelper = $this->_objectManager->get(\Codazon\Core\Helper\Image::class);
        $this->_urlBuilder = $urlBuilder;
    }
    
    public function prepareDataSource(array $dataSource)
    {	
   	    if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                if ($item['preview']) {
                    $file = $this->_basePath . '/'.$item['preview'];
                } else {
                    $file = $this->_basePath . '.jpg';
                }
				$original = $this->_imageHelper->getMediaUrl($file);
                $thumbnail = $this->_imageHelper->init($file, 'design')->resize(75, 75)->getUrl();              
                $item[$fieldName . '_src'] = $thumbnail;
                $item[$fieldName . '_alt'] = $item['title'];
                $item[$fieldName . '_link'] = $this->_urlBuilder->getUrl($this->_editUrl, [$this->_primary => $item[$this->_primary]]);
                $item[$fieldName . '_orig_src'] = $original;
            }
        }
        return $dataSource;
    }
    
}