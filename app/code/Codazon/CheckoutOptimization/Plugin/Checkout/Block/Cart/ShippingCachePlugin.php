<?php

namespace Codazon\CheckoutOptimization\Plugin\Checkout\Block\Cart;

use Magento\Checkout\Block\Cart\Shipping;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote as QuoteEntity;
use Magento\Store\Model\StoreManagerInterface;

class ShippingCachePlugin
{
    private const CACHE_LIFETIME = 600;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Json
     */
    private $serializer;

    public function __construct(
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        Json $serializer
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
    }

    public function beforeToHtml(Shipping $subject): array
    {
        if ($subject->getCacheLifetime() !== null) {
            return [];
        }

        $quote = $this->checkoutSession->getQuote();
        if (!$quote || !$quote->getId()) {
            return [];
        }

        $subject->setCacheLifetime(self::CACHE_LIFETIME);
        $subject->setData('cache_key', $this->buildCacheKey($quote));
        $subject->setData('cache_tags', $this->getCacheTags($quote));

        return [];
    }

    private function buildCacheKey(QuoteEntity $quote): string
    {
        $address = $quote->getShippingAddress();
        $itemsSignature = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $itemsSignature[] = [
                'id' => (int) $item->getId(),
                'sku' => (string) $item->getSku(),
                'qty' => (float) $item->getQty(),
                'row_total' => (float) $item->getRowTotal(),
                'updated_at' => (string) $item->getUpdatedAt(),
            ];
        }

        $addressData = [];

        if ($address) {
            $addressData = [
                'country_id' => (string) $address->getCountryId(),
                'region_id' => (string) $address->getRegionId(),
                'postcode' => (string) $address->getPostcode(),
                'city' => (string) $address->getCity(),
                'shipping_method' => (string) $address->getShippingMethod(),
                'collect_shipping_rates' => (int) $address->getCollectShippingRates(),
                'shipping_amount' => (float) $address->getShippingAmount(),
                'base_shipping_amount' => (float) $address->getBaseShippingAmount(),
                'shipping_tax_amount' => (float) $address->getShippingTaxAmount(),
            ];
        }

        return implode('|', [
            'CHECKOUT_SHIPPING_BLOCK',
            (int) $this->storeManager->getStore()->getId(),
            $this->storeManager->getStore()->getCurrentCurrencyCode(),
            $this->customerSession->getCustomerGroupId(),
            (int) $quote->getId(),
            md5($this->serializer->serialize($itemsSignature)),
            md5($this->serializer->serialize($addressData)),
            md5($this->serializer->serialize([
                'subtotal' => (float) $quote->getSubtotal(),
                'grand_total' => (float) $quote->getGrandTotal(),
                'items_qty' => (float) $quote->getItemsQty(),
            ])),
        ]);
    }

    private function getCacheTags(QuoteEntity $quote): array
    {
        $tags = [QuoteEntity::CACHE_TAG];

        if ($quote->getId()) {
            $tags[] = QuoteEntity::CACHE_TAG . '_' . $quote->getId();
        }

        return $tags;
    }
}
