<?php
/**
* Copyright © 2023 Codazon. All rights reserved.
* See COPYING.txt for license details.
*/

namespace Codazon\ThemeLayoutPro\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Codazon\ThemeLayoutPro\Model\CodazonTheme;

class Boostrap extends \Codazon\ThemeLayoutPro\Helper\FixData
{
    protected $conversion = [
        '24' => '12',
        '23'  => '12',
        '22' => '11',
        '21' => '11',
        '20' => '10',
        '19' => '10',
        '18' => '9',
        '17' => '9',
        '16' => '8',
        '15' => '8',
        '14' => '7',
        '13' => '7',
        '12' => '6',
        '11' => '6',
        '10' => '5',
        '9'  => '5',
        '8'  => '4',
        '7'  => '4',
        '6'  => '3',
        '5'  => '3',
        '4'  => '2',
        '3'  => '2',
        '2'  => '1',
        '1'  => '1'
    ];
    
    protected $colPrefix = [
        'col-lg-' => 'col-xl-',
        'col-md-' => 'col-lg-',
        'col-sm-' => 'col-md-',
        'col-xs-'  => 'col-',
    ];
    
    public function convertColumns()
    {
        $collectionFactory = \Codazon\ThemeLayoutPro\Model\ResourceModel\MainContent\CollectionFactory::class;
        $fields = ['themelayout_content', 'store_options'];
        $this->_convertColumns($collectionFactory, $fields, true);
        
        $collectionFactory = \Codazon\ThemeLayoutPro\Model\ResourceModel\Header\CollectionFactory::class;
        $fields = ['layout_xml', 'content'];
        $this->_convertColumns($collectionFactory, $fields);
        
        $collectionFactory = \Codazon\ThemeLayoutPro\Model\ResourceModel\Header\CollectionFactory::class;
        $fields = ['layout_xml', 'content'];
        $this->_convertColumns($collectionFactory, $fields);
        
        $collectionFactory = \Codazon\ThemeLayoutPro\Model\ResourceModel\Template\CollectionFactory::class;
        $fields = ['content'];
        $this->_convertColumns($collectionFactory, $fields);
        
        $collectionFactory = \Codazon\MegaMenu\Model\ResourceModel\Megamenu\CollectionFactory::class;
        $fields = ['content'];
        $this->_convertColumns($collectionFactory, $fields);
        
        $collectionFactory = \Magento\Cms\Model\ResourceModel\Block\CollectionFactory::class;
        $fields = ['content'];
        $this->_convertColumns($collectionFactory, $fields);
    }
    
    protected function _convertColumns($collectionFactory, $fields = [], $changeColWidth = false)
    {
        $objectManager = $this->importHelper->getObjectManager();
        $collection = $objectManager->get($collectionFactory)->create();
        foreach ($collection as $model) {
            $model = $model->load($model->getId());
            foreach ($fields as $field) {
                $beforeValue = $model->getData($field);
                $afterValue = $beforeValue;
                if ($changeColWidth) {
                    foreach ($this->conversion as $c0 => $c1) {
                        $afterValue = str_replace('"width":"'.$c0.'"','"width":"temp-'.$c1.'"',$afterValue);
                    }
                    foreach ($this->conversion as $c0 => $c1) {
                        $afterValue = str_replace('"width":"temp-'.$c1.'"', '"width":"'.$c1.'"', $afterValue);
                    }
                }
                foreach ($this->colPrefix as $pr0 => $pr1) {
                    //try {
                        foreach ($this->conversion as $c0 => $c1) {
                            $afterValue = str_replace($pr0.$c0, $pr0.'-temp-' . $c1, $afterValue);
                        }
                        foreach ($this->conversion as $c0 => $c1) {
                            $afterValue = str_replace($pr0.'-temp-'.$c1, $pr0 . $c1, $afterValue);
                        }
                        $afterValue = str_replace($pr0, $pr1, $afterValue);
                    /* } catch (\Exception $e) {
                        echo $e->getMessage();
                        a($field);
                        a($afterValue);
                    } */
                }
                $model->set($field, $afterValue);
            }
            $model->save();
        }
    }
}