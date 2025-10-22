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
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Message\ManagerInterface;
use RLTSquare\ProductReviewImages\Model\ReviewMediaFactory;
use RLTSquare\ProductReviewImages\Model\ResourceModel\ReviewMedia;

/**
 * Class AdminProductReviewSaveAfter
 *
 * @package RLTSquare\ProductReviewImages\Observer
 */
class AdminProductReviewSaveAfter implements ObserverInterface
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
     * @var ReviewMedia
     */
    protected ReviewMedia $reviewMediaResourceModel;
    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * AdminProductReviewSaveAfter constructor.
     * @param RequestInterface $request
     * @param Filesystem $filesystem
     * @param File $fileHandler
     * @param ReviewMediaFactory $reviewMediaFactory
     * @param ReviewMedia $reviewMediaResourceModel
     * @param ManagerInterface $messageManager
     * @param DirectoryList $directoryList
     */
    public function __construct(
        RequestInterface $request,
        Filesystem $filesystem,
        File $fileHandler,
        ReviewMediaFactory $reviewMediaFactory,
        ReviewMedia $reviewMediaResourceModel,
        ManagerInterface $messageManager,
        DirectoryList $directoryList
    ) {
        $this->request = $request;
        $this->fileHandler = $fileHandler;
        $this->reviewMediaFactory = $reviewMediaFactory;
        $this->reviewMediaResourceModel = $reviewMediaResourceModel;
        $this->filesystem = $filesystem;
        $this->messageManager = $messageManager;
        $this->directoryList = $directoryList;
    }


    /**
     * function
     * executes after review is saved
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $target = $this->filesystem
            ->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath('review_images');

        $deletedMediaString = $this->request->getParam('deleted_media');

        if ($deletedMediaString)
            try {
                $ids = explode(",", trim($deletedMediaString, ","));
                foreach ($ids as $id) {
                    $reviewMedia = $this->reviewMediaFactory->create()->load($id);
                    $path = $target . $reviewMedia->getMediaUrl();
                    if ($this->fileHandler->isExists($path)) {
                        $this->fileHandler->deleteFile($path);
                    }
                    //$reviewMedia->delete();
                    $this->reviewMediaResourceModel->delete($reviewMedia);
                }
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while updating review attachment(s).'));
            }
    }
}
