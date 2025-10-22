<?php
/**
 * Copyright © 2018 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Codazon\ThemeLayoutPro\Model\Config\Backend;

class Logo extends File
{
    /**
     * The tail part of directory path for uploading
     *
     */
    const UPLOAD_DIR = 'codazon/logo'; // Folder save image

    /**
     * Return path to directory for upload file
     *
     * @return string
     * @throw \Magento\Framework\Exception\LocalizedException
     */
    protected function _getUploadDir()
    {
        return $this->_mediaDirectory->getAbsolutePath($this->_appendScopeInfo(self::UPLOAD_DIR));
    }

    /**
     * Makes a decision about whether to add info about the scope.
     *
     * @return boolean
     */
    protected function _addWhetherScopeInfo()
    {
        return true;
    }

    /**
     * Getter for allowed extensions of uploaded files.
     *
     * @return string[]
     */
    protected function _getAllowedExtensions()
    {
        return ['jpg', 'jpeg', 'gif', 'png', 'svg'];
    }
    
    public function beforeSave()
    {
        $value = $this->getValue();
        $deleteFlag = is_array($value) && !empty($value['delete']);
        $fileTmpName = $_FILES['groups']['tmp_name'][$this->getGroupId()]['fields'][$this->getField()]['value'];
        $fileName = !empty($value['value']) ? $value['value'] : null;
        if ($fileName && ($fileTmpName || $deleteFlag)) {
            $this->_mediaDirectory->delete(self::UPLOAD_DIR . '/' . $fileName);
        }
        return parent::beforeSave();
    }
}