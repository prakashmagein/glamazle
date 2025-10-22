<?php
/**
* Copyright © 2018 Codazon. All rights reserved.
* See COPYING.txt for license details.
*/

namespace Codazon\ThemeLayoutPro\Controller\Ajax;

class Datetime extends \Magento\Framework\App\Action\Action
{
    protected $block;
    
	protected $helper;
    
    protected $timezone;
    
	public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
		parent::__construct($context);
    }
    
    public function execute()
    {
        return $this->getResponse()->representJson(
            json_encode(['now' => $this->timezone->date()->format('Y-m-d H:i:s')])
        );
    }
}