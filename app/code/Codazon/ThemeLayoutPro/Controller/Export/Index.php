<?php
/**
 * Copyright © 2017 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Controller\Export;

use Codazon\ThemeLayoutPro\Helper\Data as ThemeHelper;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $helper;
    
    protected $themeData;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        ThemeHelper $helper,
        \Codazon\ThemeLayoutPro\Model\Data $themeData
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->themeData = $themeData;
        if ($version = $this->getRequest()->getParam('version')) {
            $this->themeData->setVersion($version);
        }
    }
    
    public function execute()
    {
        set_time_limit(3600);
        $request = $this->getRequest();
        if ($request->getParam('export_product_images')) {
            $this->exportProductImages();
        } elseif ($request->getParam('export_full')) {
            $this->exportFull();
        } elseif ($request->getParam('export_patch')) {
            $this->exportPatch();
        } elseif ($request->getParam('build_assets')) {
            $this->buildAssets();
        } else {
            if (!$request->getParam('only_pack_theme')) {
                $this->exportData();
                $this->packTheme();
            } else {
                $this->packTheme();
            }
        }
        die();
    }
    
    public function buildAssets()
    {
        $onlyMainContent = $this->getRequest()->getParam('only_main_content', false);
        echo "<pre>"; print_r($this->themeData->buildAssets($onlyMainContent)); echo "</pre>";
    }
    public function exportPatch()
    {
        if ($patchList = $this->getRequest()->getParam('patch_list')) {
            $patchName = $this->getRequest()->getParam('patch_name', false);
            $version = $this->getRequest()->getParam('version', false);
            if (!$patchName) {
                $patchName = str_replace('.phtml', '.zip', $patchList);
            }
            $result = $this->themeData->exportPatch($patchList, $patchName, $version);
        } else {
            echo "<p>Patch List not found.</p>";
        }
    }
    
    public function exportProductImages()
    {
        $result = $this->themeData->packProductImages();
    }
    
    public function exportFull()
    {
        echo "<h1 class='titlte'>Export Full Package</h1>";
        if ($this->getRequest()->getParam('skip_export_database')) {
            $this->themeData->setData('skip_export_database', 1);
        }
        $result = $this->themeData->packFull();
    }
    
    protected function exportData()
    {
        echo "<style type='text/css'>";
        echo ".ex-list{display:flex; align-items: stretch; flex-wrap: wrap;} .ex-item{width: 25%;float: left;border: 1px solid #e2e516;box-sizing: border-box;padding:10px;}";
        echo "</style>";
        echo "<div class='ex-list'>";        
        $this->printAction('Export Main Content', $this->themeData->exportMainContent());
        $this->printAction('Export Header', $this->themeData->exportHeader());
        $this->printAction('Export Export Footer', $this->themeData->exportFooter());
        $this->printAction('Export CMS Block', $this->themeData->exportCMSBlock());
        $this->printAction('Export CMS Page', $this->themeData->exportCMSPage());
        $this->printAction('Export Template Set', $this->themeData->exportTemplateSet());
        $this->printAction('Export Template', $this->themeData->exportTemplate());
        $this->printAction('Export Blog Categories', $this->themeData->exportBlogCategories());
        $this->printAction('Export Blog Tags', $this->themeData->exportBlogTags());
        $this->printAction('Export Blog Posts', $this->themeData->exportBlogPosts());
        $this->printAction('Export Menu', $this->themeData->exportMenus());
        echo "</div>";
    }
    
    protected function printAction($title, $result) {
        echo "<div class='ex-item'><p><strong style='color:darkcyan'>$title</strong></p>";
        echo "<ul>";
        foreach ($result['items'] as $item) {
            echo "<li>(" . $item['id'] . ') ' . $item['name'] . "</li>";
        }
        echo "</ul></div>";
    }
    
    protected function packTheme()
    {
        $this->themeData->packTheme();
    }
}