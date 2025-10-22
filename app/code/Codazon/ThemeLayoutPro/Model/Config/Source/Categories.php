<?php
/**
 *
 * Copyright © 2017 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Codazon\ThemeLayoutPro\Model\Config\Source;

class Categories implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    protected $model = 'Codazon\ThemeLayoutPro\Model\MainContentFactory';
    
    protected $_init = false;
    
    protected $categoryBlock;
    
    public function toOptionArray()
    {
        $this->_init();
        $options = [
            ['value' => '', 'label' => __('-')],
        ];
        $options = array_merge($options, $this->categoryBlock->getCategoryList());
        return $options;
    }
    
    public function toArray()
    {
        return $this->toOptionArray();
    }
    
    protected function _init()
    {
        if ($this->_init === false) {
            $this->_init = true;
            $this->categoryBlock = \Magento\Framework\App\ObjectManager::getInstance()->get('Codazon\ThemeLayoutPro\Block\SearchByCategory');
        }
    }
    
    public function getCategoryList() {
        
    }
    
    protected function _addCategoriesToList()
    {
        
    }
}
