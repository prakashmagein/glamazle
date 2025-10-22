<?php
/**
 * Copyright © 2022 Codazon. All rights reserved.
 * See COPYING.txt for license details.
*/

namespace Codazon\Core\Ui\DataProvider\Form;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Eav\Model\Entity\Type;
use Magento\Ui\Component\Form\Field;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\App\ObjectManager;

class AbstractDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{   
    protected $collection;
    
    protected $dataPersistor;
    
    protected $request;
    
    protected $loadedData;
    
    protected $storeId;
    
    protected $registry;
        
    protected $imageUploader = null;
    
    protected $requestScopeFieldName = 'store';
    
    protected $entityType = null;
    
    /* Declare */
    protected $_isEav = false;
    
    protected $_hasMultiScopes = true;
    
    protected $_entityTypeCode = '';
    
    protected $_registryName = '';
    
    protected $_imageTypes = ['thumbnail', 'cover'];
    
    protected $_imageUploaderClass = 'Codazon\Core\Model\Form\ImageUploader';
    
    protected $_collectionFactoryClass;
    
    protected $_imgBasePath;
    /* End declare */
    
    private $eavConfig;
    
    protected $arrayManager;
    
    protected $cacheManager;
    
    protected $serializer;
    
    protected $storeManager;
        
    const CONTAINER_PREFIX = 'container_';
    
    /**
     * @return array
     */
    protected function getFieldsMap()
    {
        return [
            'general' => [
                'name',
                'is_active'
            ],
            'content' => [
                'thumbnail',
                'cover',
                'description'
            ],
            'search_engine_optimization' => [
                'url_key',
                'meta_title',
                'meta_keywords',
                'meta_description'
            ]
        ];
    }
    
    /**
     * Form element mapping
     *
     * @var array
     */
    protected $formElement = [
        'text' => 'input',
        'boolean' => 'checkbox',
    ];
    protected $metaProperties = [
        'dataType' => 'frontend_input',
        'visible' => 'is_visible',
        'required' => 'is_required',
        'label' => 'frontend_label',
        'sortOrder' => 'sort_order',
        'notice' => 'note',
        'default' => 'default_value',
        'size' => 'multiline_count',
    ];
    
	public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        DataPersistorInterface $dataPersistor,
        RequestInterface $request,
        Config $eavConfig,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        ArrayManager $arrayManager,
        $collectionFactory = null,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory ? $collectionFactory->create() :
            ObjectManager::getInstance()->get($this->_collectionFactoryClass)->create();
        $this->dataPersistor = $dataPersistor;
        $this->request = $request;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->storeId = $this->request->getParam($this->requestScopeFieldName, Store::DEFAULT_STORE_ID);
        $this->eavConfig = $eavConfig;
        $this->arrayManager = $arrayManager;
        if ($this->_hasMultiScopes) {
            $this->entityType = $this->eavConfig->getEntityType($this->_entityTypeCode);
        }
        if ($this->_isEav) {
            $this->collection->addAttributeToSelect('*');
        }
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->meta = $this->prepareMeta($this->meta);
    }
    
	public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        
        $item = $this->getCurrentItem();
        $data = $item->getData();
        if ($this->_hasMultiScopes) {
            $data = $this->addUseDefaultSettings($item, $data);
        }
        $data = $this->filterFields($data);
        $this->loadedData[$item->getId()] = $data;
        $data = $this->dataPersistor->get($this->_entityTypeCode);
        if (!empty($data)) {
            $item = $this->collection->getNewEmptyItem();
            $item->setData($data);
            $this->loadedData[$item->getId()] = $item->getData();
            $this->dataPersistor->clear($this->_entityTypeCode);
        }
        return $this->loadedData;
    }
    
    public function getCurrentItem()
    {
        $item = $this->registry->registry($this->_registryName);
        if ($item) {
            return $item;
        }
        $requestId = $this->request->getParam($this->requestFieldName);
        $requestScope = $this->request->getParam($this->requestScopeFieldName, Store::DEFAULT_STORE_ID);
        if ($requestId) {
            $item = $this->collection->addFieldToFilter($this->primaryFieldName, $requestId)->getFirstItem();
            if (!$item->getId()) {
                throw NoSuchEntityException::singleField('id', $requestId);
            }
        }
        return $item;
    }
    
    protected function addUseDefaultSettings($item, $data)
    {
        $attributeCollection = $this->entityType->getAttributeCollection();
        foreach($attributeCollection as $attribute) {
            $code = $attribute->getAttributeCode();
            if ($item->getExistsStoreValueFlag($code) ||
                $item->getStoreId() === Store::DEFAULT_STORE_ID
            ) {
                $data['isUseDefault'][$code] = false;
            } else {
                $data['isUseDefault'][$code] = true;
            }
        }
        return $data;
    }
    
    protected function filterFields(array $rawData)
    {
        $data = $rawData;
        $imagesTypes = $this->_imageTypes;
        $imageUploader = $this->getImageUploader();
        foreach ($imagesTypes as $image)
        {
            if (isset($data[$image])) {
                $imageName = (string)$data[$image];
                unset($data[$image]);
                $data[$image][0]['name'] = $imageName;
                $data[$image][0]['url'] = $this->_getImageUrl($imageName);
                //$stat = $imageUploader->getBaseFileStat($imageName);
                //$data[$image][0]['size'] = isset($stat) ? $stat['size'] : 0;
                $data[$image][0]['size'] = $imageUploader->getBaseFileSize($imageName);
            }
        }
        return $data;
    }
    
    protected function _getImageUrl($imageName) {
        return $this->getImageUploader()->getImageUrl($imageName);
    }
    
    protected function getImageUploader()
    {
        if ($this->imageUploader === null) {
            $this->imageUploader = ObjectManager::getInstance()->get(
                $this->_imageUploaderClass
            );
            if ($this->_imgBasePath) {
                $this->imageUploader->setBasePath($this->_imgBasePath);
            }
        }
        return $this->imageUploader;
    }
        
     /**
     * Prepare meta data
     *
     * @param array $meta
     * @return array
     */
    public function prepareMeta($meta)
    {
        if ($this->_hasMultiScopes) {
            $meta = array_replace_recursive($meta, $this->prepareFieldsMeta(
                $this->getFieldsMap(),
                $this->getAttributesMeta($this->entityType)
            ));
        }
        return $meta;
    }
    
    protected function prepareFieldsMeta($fieldsMap, $fieldsMeta)
    {
        $result = [];
        foreach ($fieldsMap as $fieldSet => $fields) {
            foreach ($fields as $field) {
                if (isset($fieldsMeta[$field])) {
                    $result[$fieldSet]['children'][$field]['arguments']['data']['config'] = $fieldsMeta[$field];
                }
            }
        }
        return $result;
    }
    
    public function getAttributesMeta(Type $entityType)
    {
        $meta = [];
        $attributes = $entityType->getAttributeCollection();
        $isScopeStore = $this->request->getParam($this->requestScopeFieldName, Store::DEFAULT_STORE_ID) != Store::DEFAULT_STORE_ID;
        $item = $this->getCurrentItem(); 
        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();
            $meta[$code]['scopeLabel'] = $this->getScopeLabel($attribute);
            $meta[$code]['componentType'] = Field::NAME;
            
            if ($isScopeStore) {
                $meta[$code]['imports'] = [
                    'isUseDefault' => '${ $.provider }:data.isUseDefault.'.$code
                ];
                if ($item) {
                    if ($item->getExistsStoreValueFlag($code) ||
                        $item->getStoreId() === Store::DEFAULT_STORE_ID
                    ) {
                        $meta[$code]['disabled'] = false;
                    } else {
                        $meta[$code]['disabled'] = true;
                    }
                }
                $meta[$code]['service'] = [
                    'template' => 'ui/form/element/helper/service'
                ];
            }
        }
        $result = [];
        foreach ($meta as $key => $item) {
            $result[$key] = $item;
        }
        $result = $this->getDefaultMetaData($result);
        return $result;
    }
    
    public function getScopeLabel($attribute)
    {
        $html = '';
        if (!$attribute || $this->storeManager->isSingleStoreMode()
            || $attribute->getFrontendInput() === AttributeInterface::FRONTEND_INPUT
        ) {
            return $html;
        }
        if ($attribute->isScopeGlobal()) {
            $html .= __('[GLOBAL]');
        } elseif ($attribute->isScopeWebsite()) {
            $html .= __('[WEBSITE]');
        } elseif ($attribute->isScopeStore()) {
            $html .= __('[STORE VIEW]');
        }

        return $html;
    }
    
    public function getDefaultMetaData($result)
    {
        return $result;
    }
}