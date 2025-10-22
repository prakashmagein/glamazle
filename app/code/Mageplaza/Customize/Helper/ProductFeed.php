<?php
 
namespace Mageplaza\Customize\Helper;

use Mageplaza\ProductFeed\Helper\Data;
use Magento\Framework\Url as UrlAbstract;
use Magento\Framework\DataObject;
use Exception;
 
class ProductFeed extends Data
{    
    public function getProductsData($feed, $productAttributes = [], $productIds = [])
    {
        $campaignUrl = '';
        $campaignUrl .= $feed->getCampaignSource() ? '?utm_source=' . $feed->getCampaignSource() : '';
        $campaignUrl .= $feed->getCampaignMedium() ? '&utm_medium=' . $feed->getCampaignMedium() : '';
        $campaignUrl .= $feed->getCampaignName() ? '&utm_campaign=' . $feed->getCampaignName() : '';
        $campaignUrl .= $feed->getCampaignTerm() ? '&utm_term=' . $feed->getCampaignTerm() : '';
        $campaignUrl .= $feed->getCampaignContent() ? '&utm_content=' . $feed->getCampaignContent() : '';

        $categoryMap = $this->unserialize($feed->getCategoryMap());

        $allCategory = $this->categoryCollectionFactory->create();
        $allCategory->setStoreId($feed->getStoreId())->addAttributeToSelect('name');
        $categoriesName = [];
        /** @var $item Category */
        foreach ($allCategory as $item) {
            $categoriesName[$item->getId()] = $item->getName();
        }

        $allSelectProductAttributes = $this->prdAttrCollectionFactory->create()
            ->addFieldToFilter('frontend_input', ['in' => ['multiselect', 'select']])
            ->getColumnValues('attribute_code');

        $matchingProductIds = !empty($productIds) ? $productIds : $feed->getMatchingProductIds();
        $productCollection  = $this->productFactory->create()->getCollection()
            ->addAttributeToSelect($productAttributes)->addStoreFilter($feed->getStoreId())
            ->addFieldToFilter('entity_id', ['in' => $matchingProductIds])->addMediaGalleryData();

        $storeId = $feed->getStoreId() ?: $this->storeManager->getDefaultStoreView()->getId();

        $result = [];
        /** @var $product Product */
        foreach ($productCollection as $product) {
            $typeInstance           = $product->getTypeInstance();
            $childProductCollection = $typeInstance->getAssociatedProducts($product);
            if ($childProductCollection) {
                $associatedData = [];
                foreach ($childProductCollection as $item) {
                    $associatedData = $item->getData();
                }
                $product->setAssociatedProducts($associatedData);
            } else {
                $product->setAssociatedProducts([]);
            }

            $stockItem       = $this->stockState->getStockItem(
                $product->getId(),
                $storeId
            );
            $qty             = $stockItem->getQty();
            $categories      = $product->getCategoryCollection()->addAttributeToSelect('*');
            $relatedProducts = [];
            foreach ($product->getRelatedProducts() as $item) {
                $relatedProducts[] = $item->getData();
            }
            $crossSellProducts = [];
            foreach ($product->getCrossSellProducts() as $item) {
                $crossSellProducts[] = $item->getData();
            }
            $upSellProducts = [];
            foreach ($product->getUpSellProducts() as $item) {
                $upSellProducts[] = $item->getData();
            }
            $oriProduct = $this->productRepository->getById($product->getId(), false, $storeId);
            $finalPrice = $this->convertPrice($oriProduct->getFinalPrice(), $storeId);
            $product->setStoreId($storeId);
            $productLink = $this->getProductUrl($oriProduct, $storeId) . $campaignUrl;
            $imageLink   = $oriProduct->getImage() ? $this->storeManager->getStore($storeId)
                    ->getBaseUrl(UrlAbstract::URL_TYPE_MEDIA)
                . 'catalog/product/' . $oriProduct->getImage() : '';
            $images      = $product->getMediaGalleryImages()->getSize() ? $product->getMediaGalleryImages() : [[]];
            if (is_object($images)) {
                $imagesData = [];
                foreach ($images->getItems() as $item) {
                    $imagesData[] = $item->getData();
                }
                $images = $imagesData;
            }
            /** @var $category Category */
            $lv             = 0;
            $categoryPath   = '';
            $cat            = new DataObject();
            $categoriesData = [];
            foreach ($categories as $category) {
                if ($lv < $category->getLevel()) {
                    $lv  = $category->getLevel();
                    $cat = $category;
                }
                $categoriesData[] = $category->getData();
            }
            $mapping = '';
            if (isset($categoryMap[$cat->getId()])) {
                $mapping = $categoryMap[$cat->getId()];
            }
            $catPaths = array_reverse(explode(',', $cat->getPathInStore()));
            foreach ($catPaths as $index => $catId) {
                if ($index === (count($catPaths) - 1)) {
                    $categoryPath .= isset($categoriesName[$catId]) ? $categoriesName[$catId] : '';
                } else {
                    $categoryPath .= (isset($categoriesName[$catId]) ? $categoriesName[$catId] : '') . ' > ';
                }
            }

            $oriProduct->isAvailable() ? $product->setData('quantity_and_stock_status', 'in stock')
                : $product->setData('quantity_and_stock_status', 'out of stock');

            $noneAttr = [
                'categoryCollection',
                'relatedProducts',
                'crossSellProducts',
                'upSellProducts',
                'final_price',
                'link',
                'image_link',
                'images',
                'category_path',
                'mapping',
                'qty',
            ];

            // Convert attribute value to attribute text
            foreach ($productAttributes as $attributeCode) {
                try {
                    if ($attributeCode === 'quantity_and_stock_status'
                        || in_array($attributeCode, $noneAttr, true)
                        || !in_array($attributeCode, $allSelectProductAttributes, true)
                        || !$product->getData($attributeCode)
                    ) {
                        continue;
                    }
                    $attributeText = $product->getResource()->getAttribute($attributeCode)
                        ->setStoreId($feed->getStoreId())->getFrontend()->getValue($product);
                    if (is_array($attributeText)) {
                        $attributeText = implode(',', $attributeText);
                    }
                    if ($attributeText) {
                        $product->setData($attributeCode, $attributeText);
                    }
                } catch (Exception $e) {
                    continue;
                }
            }

            $product->setData('categoryCollection', $categoriesData);
            $product->setData('relatedProducts', $relatedProducts);
            $product->setData('crossSellProducts', $crossSellProducts);
            $product->setData('upSellProducts', $upSellProducts);
            $product->setData('final_price', $finalPrice);
            $product->setData('link', $productLink);
            $product->setData('image_link', $imageLink);
            $product->setData('images', $images);
            $product->setData('category_path', $categoryPath);
            $product->setData('mapping', $mapping);
            $product->setData('qty', $qty);
            $result[] = self::jsonDecode(self::jsonEncode($product->getData()));

            if ($this->useGoogleShoppingApi($feed)) {
                $this->syncProductToGoogleShopping($feed, $product);
            }
        }

        return $result;
    }

    public function getProductUrl($product, $storeId)
    {
        return $product->getUrlModel()->getUrl($product);
    }
}