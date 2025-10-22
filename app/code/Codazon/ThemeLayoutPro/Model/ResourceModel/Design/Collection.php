<?php
/**
 * Copyright © 2022 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Model\ResourceModel\Design;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Codazon\ThemeLayoutPro\Model\Design', 'Codazon\ThemeLayoutPro\Model\ResourceModel\Design');
    }
}
