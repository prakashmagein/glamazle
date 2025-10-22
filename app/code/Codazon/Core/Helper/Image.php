<?php
/**
 * Copyright © 2021 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\Core\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Blog image helper
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Image extends AbstractHelper
{
    /**
     * Default quality value (for JPEG images only).
     *
     * @var int
     */
    protected $_quality = 90;
    protected $_keepAspectRatio = true;
    protected $_keepFrame = true;
    protected $_keepTransparency = true;
    protected $_constrainOnly = true;
    protected $_backgroundColor = [255, 255, 255];
    protected $_baseFile;
    protected $_newFile;
    protected $_rootDir = 'codazon_cache';
    protected $_baseDir = 'core';
    protected $_mediaUrl;
    protected $_imageFactory;
    protected $_mediaDirectory;
    protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Image\Factory $imageFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_imageFactory = $imageFactory;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    public function init($baseFile, $baseDir = 'core')
    {
        $this->_newFile = '';
        $this->_backgroundColor = [255, 255, 255];
        $this->_rootDir = 'codazon_cache';
        $this->_baseFile = $baseFile;
        $this->_baseDir = $baseDir;
        $this->_keepFrame = true;
        return $this;
    }
    
    public function setBackgroundColor($value)
    {
        $this->_backgroundColor = $value;
        return $this;
    }
    
    
    public function setRootDir($value)
    {
        $this->_rootDir = $value;
        return $this;
    }
    
    
    public function setBaseDir($value)
    {
        $this->_baseDir = $value;
        return $this;
    }
    
    public function keepFrame($keepFrame)
    {
        $this->_keepFrame = $keepFrame;
        return $this;
    }

    public function resize($width, $height = null)
    {
        if ($this->_baseFile){
            $path = $this->_baseDir ? $this->_rootDir . '/' . $this->_baseDir . '/' . round((float)$width) . 'x' . round((float)$height)
                : $this->_rootDir . '/' . round((float)$width) . 'x' . round((float)$height) ;
            $this->_newFile = $path. '/' . $this->_baseFile;
            if (!$this->fileExists($this->_newFile)) {
                $this->resizeBaseFile($width, $height);
            }
        }
        return $this;
    }

    protected function resizeBaseFile($width, $height)
    {
        if (!$this->fileExists($this->_baseFile)) {
            $this->_baseFile = null;
            return $this;
        }

        $processor = $this->_imageFactory->create(
            $this->_mediaDirectory->getAbsolutePath($this->_baseFile)
        );
        $processor->keepAspectRatio($this->_keepAspectRatio);
        $processor->keepFrame($this->_keepFrame);
        $processor->keepTransparency($this->_keepTransparency);
        $processor->constrainOnly($this->_constrainOnly);
        $processor->backgroundColor($this->_backgroundColor);
        $processor->quality($this->_quality);
        
        if ($height === null) {
            $height = round($processor->getOriginalHeight()*(float)$width/$processor->getOriginalWidth());
        }
        
        $processor->resize(round((float)$width), (int)$height);

        $newFile = $this->_mediaDirectory->getAbsolutePath($this->_newFile);
        $processor->save($newFile);
        unset($processor);

        return $this;
    }

    protected function fileExists($filename)
    {
        return $this->_mediaDirectory->isFile($filename);
    }

    public function __toString()
    {
        $url = "";
        if ($this->_baseFile){
            $url = $this->_storeManager->getStore()->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . $this->_newFile;
        }
        return $url;
    }
    
    public function getUrl()
    {
        return $this->__toString();
    }
    
    public function getMediaUrl($file = '')
    {
        if ($this->_mediaUrl === null) {
            $this->_mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        }
        return $this->_mediaUrl . $file;
    }
}
