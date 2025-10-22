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

namespace RLTSquare\ProductReviewImages\Observer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Message\ManagerInterface;
use RLTSquare\ProductReviewImages\Model\ReviewMediaFactory;
use RLTSquare\ProductReviewImages\Model\ResourceModel\ReviewMedia\CollectionFactory;

/**
 * Class AdminProductReviewDeleteBefore
 *
 * @package RLTSquare\ProductReviewImages\Observer
 */
class AdminProductReviewDeleteBefore implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var ReviewMediaFactory
     */
    protected ReviewMediaFactory $reviewMediaFactory;

    /**
     * @var DirectoryList
     */
    protected DirectoryList $directoryList;

    /**
     * @var File
     */
    protected File $fileHandler;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $collectionFactory;
    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * AdminProductReviewDeleteBefore constructor.
     * @param RequestInterface $request
     * @param Filesystem $filesystem
     * @param File $fileHandler
     * @param ReviewMediaFactory $reviewMediaFactory
     * @param ManagerInterface $messageManager
     * @param DirectoryList $directoryList
     */
    public function __construct(
        RequestInterface $request,
        Filesystem $filesystem,
        File $fileHandler,
        ReviewMediaFactory $reviewMediaFactory,
        ManagerInterface $messageManager,
        DirectoryList $directoryList,
        CollectionFactory $collectionFactory
    ) {
        $this->request = $request;
        $this->fileHandler = $fileHandler;
        $this->reviewMediaFactory = $reviewMediaFactory;
        $this->filesystem = $filesystem;
        $this->messageManager = $messageManager;
        $this->directoryList = $directoryList;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * function
     * executes before a review is deleted
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        // single record deletion
        $reviewId = $this->request->getParam('id', false);
        if ($reviewId) {
            $this->deleteReviewMedia($reviewId);
            return;
        }

        // mass deletion
        $reviewIds = $this->request->getParam('reviews', false);
        if ($reviewIds) {
            foreach ($reviewIds as $id) {
                $this->deleteReviewMedia($id);
            }
            return;
        }
    }

    /**
     * function
     * delete media against a review
     *
     * @param $reviewId
     * @return void
     */
    private function deleteReviewMedia($reviewId): void
    {
        // Folder containing images
        $target = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('review_images');

        try {
            $thisReviewMediaCollection = $this->collectionFactory->create()->addFieldToFilter('review_id', $reviewId);

            foreach ($thisReviewMediaCollection as $m) {
                $path = $target . $m->getMediaUrl();
                if ($this->fileHandler->isExists($path)) {
                    $this->fileHandler->deleteFile($path);
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting review(s) attachment(s).'));
        }
    }
}
