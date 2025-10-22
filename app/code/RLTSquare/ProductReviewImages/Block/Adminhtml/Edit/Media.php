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

namespace RLTSquare\ProductReviewImages\Block\Adminhtml\Edit;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use RLTSquare\ProductReviewImages\Model\ResourceModel\ReviewMedia\CollectionFactory;

/**
 * Class Media
 *
 * @package RLTSquare\ProductReviewImages\Block\Adminhtml\Edit
 */
class Media extends Template
{

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $collectionFactory;

    /**
     * Media constructor
     *
     * \Magento\Backend\Block\Template\Context $context
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->setTemplate("media.phtml");
        parent::__construct($context);
    }

    /**
     * function
     * get media collection for a review
     *
     * @return object
     */
    public function getMediaCollection(): object
    {
        return $this->collectionFactory->create()->addFieldToFilter('review_id', $this->getRequest()->getParam('id'));
    }

    /**
     * function
     * get review_images directory path
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getReviewMediaUrl(): string
    {
        return $this->_storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'review_images';
    }
}
