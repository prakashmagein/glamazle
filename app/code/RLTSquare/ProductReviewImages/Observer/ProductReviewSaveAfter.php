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
use Magento\Framework\Message\ManagerInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use RLTSquare\ProductReviewImages\Model\ReviewMediaFactory;
use RLTSquare\ProductReviewImages\Model\ResourceModel\ReviewMedia;

/**
 * Class ProductReviewSaveAfter
 *
 * @package RLTSquare\ProductReviewImages\Observer
 */
class ProductReviewSaveAfter implements ObserverInterface
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
     * @var UploaderFactory
     */
    protected UploaderFactory $fileUploaderFactory;
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
     * ProductReviewSaveAfter constructor.
     * @param RequestInterface $request
     * @param Filesystem $filesystem
     * @param UploaderFactory $fileUploaderFactory
     * @param ReviewMediaFactory $reviewMediaFactory
     * @param ReviewMedia $reviewMediaResourceModel
     * @param ManagerInterface $messageManager
     * @param DirectoryList $directoryList
     */
    public function __construct(
        RequestInterface $request,
        Filesystem $filesystem,
        UploaderFactory $fileUploaderFactory,
        ReviewMediaFactory $reviewMediaFactory,
        ReviewMedia $reviewMediaResourceModel,
        ManagerInterface $messageManager,
        DirectoryList $directoryList
    ) {
        $this->request = $request;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->reviewMediaFactory = $reviewMediaFactory;
        $this->reviewMediaResourceModel = $reviewMediaResourceModel;
        $this->filesystem = $filesystem;
        $this->messageManager = $messageManager;
        $this->directoryList = $directoryList;
    }

    /**
     * function
     * executed after a product review is saved
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $reviewId = $observer->getEvent()->getObject()->getReviewId();
        $media = $this->request->getFiles('review_media');
        $target = $this->filesystem
            ->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath('review_images');

        if ($media) {
            try {
                for ($i = 0; $i < count($media); $i++) {
                    $uploader = $this->fileUploaderFactory->create(['fileId' => 'review_media[' . $i . ']']);
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $uploader->setAllowCreateFolders(true);

                    $result = $uploader->save($target);

                    $reviewMedia = $this->reviewMediaFactory->create();
                    $reviewMedia->setMediaUrl($result['file']);
                    $reviewMedia->setReviewId($reviewId);
                   // $reviewMedia->save();
                    $this->reviewMediaResourceModel->save($reviewMedia);
                }
            } catch (\Exception $e) {
                if ($e->getCode() == 0) {
                    $this->messageManager->addErrorMessage("Something went wrong while saving review attachment(s).");
                }
            }
        }
    }
}
