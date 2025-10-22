<?php
/**
 * Copyright © 2022 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Model\ResourceModel;

class Design extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
	{
		$this->_init('themelayout_design', 'theme_id');
	}
}
