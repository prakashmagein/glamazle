<?php
/**
 * @category    WeltPixel
 * @package     WeltPixel_EnhancedEmail
 * @copyright   Copyright (c) 2018 Weltpixel
 * @author      Nagy Attila @ Weltpixel TEAM
 */

namespace WeltPixel\EnhancedEmail\Block\Items;

/**
 * Class AbstractItems
 * @package WeltPixel\EnhancedEmail\Block\Order\Email
 */
class AbstractItems extends \Magento\Sales\Block\Items\AbstractItems
{
    /**
     * @var \WeltPixel\EnhancedEmail\Helper\Data
     */
    protected $_wpHelper;

    /**
     * AbstractItems constructor.
     * @param \WeltPixel\EnhancedEmail\Helper\Data $wpHelper
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \WeltPixel\EnhancedEmail\Helper\Data $wpHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    )
    {
        $this->_wpHelper = $wpHelper;
        parent::__construct($context, $data);

    }

    /**
     * @return mixed
     */
    public function canShowProductImage()
    {
        return $this->_wpHelper->canShowProductImage();
    }
}