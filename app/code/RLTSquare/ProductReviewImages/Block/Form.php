<?php
/**
 * NOTICE OF LICENSE
 * You may not sell, distribute, sub-license, rent, lease or lend complete or portion of software to anyone.
 *
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future.
 *
 * @package   RLTSquare_ProductReviewImages
 * @copyright Copyright (c) 2022 RLTSquare (https://www.rltsquare.com)
 * @contacts  support@rltsquare.com
 * @license  See the LICENSE.md file in module root directory
 */

namespace RLTSquare\ProductReviewImages\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Url;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Review\Helper\Data;
use Magento\Review\Model\RatingFactory;

/**
 * Class Form
 *
 * @package RLTSquare\ProductReviewImages\Block
 * @author Umar Chaudhry <umarch@rltsquare.com>
 */
class Form extends \Magento\Review\Block\Form
{
    /**
     * Form constructor.
     * @param Context $context
     * @param EncoderInterface $urlEncoder
     * @param Data $reviewData
     * @param ProductRepositoryInterface $productRepository
     * @param RatingFactory $ratingFactory
     * @param ManagerInterface $messageManager
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param Url $customerUrl
     * @param array $data
     * @param Json|null $serializer
     */
    public function __construct(
        Context $context,
        EncoderInterface $urlEncoder,
        Data $reviewData,
        ProductRepositoryInterface $productRepository,
        RatingFactory $ratingFactory,
        ManagerInterface $messageManager,
        \Magento\Framework\App\Http\Context $httpContext,
        Url $customerUrl,
        array $data = [],
        Json $serializer = null
    ) {
        parent::__construct($context, $urlEncoder, $reviewData, $productRepository, $ratingFactory, $messageManager, $httpContext, $customerUrl, $data, $serializer);
    }

    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('RLTSquare_ProductReviewImages::form.phtml');
    }
}
