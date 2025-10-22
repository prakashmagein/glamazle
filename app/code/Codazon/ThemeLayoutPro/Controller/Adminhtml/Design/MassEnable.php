<?php
/**
 * Copyright © 2022 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Controller\Adminhtml\Design;

use Magento\Backend\App\Action;
use \Magento\Store\Model\Store;

class MassEnable extends \Codazon\ThemeLayoutPro\Controller\Adminhtml\MassStatusAbstract
{
    protected $primary = 'theme_id';
    
    protected $modelClass = 'Codazon\ThemeLayoutPro\Model\Design';
       
    protected $_updateMsg = 'A total of %1 record(s) have been enabled.';
    
    protected $status = 1;
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Codazon_ThemeLayoutPro::themelayout_design_save');
    }
}

