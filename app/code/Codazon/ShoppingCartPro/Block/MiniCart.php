<?php
/**
 * Copyright © 2016 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Codazon\ShoppingCartPro\Block;

use Codazon\ShoppingCartPro\Helper\Data as ShoppingCartHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote as QuoteEntity;

class MiniCart extends Template implements IdentityInterface
{
    private const CACHE_LIFETIME = 600;
    public const CACHE_TAG = 'CODAZON_MINICARTPRO_MINICART';

    /**
     * @var ShoppingCartHelper
     */
    protected $helper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var Json
     */
    private $serializer;

    public function __construct(
        Context $context,
        ShoppingCartHelper $helper,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        Json $serializer,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->serializer = $serializer;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setCacheLifetime(self::CACHE_LIFETIME);
    }

    public function getHelper(): ShoppingCartHelper
    {
        return $this->helper;
    }

    public function getIdentities(): array
    {
        $quoteId = $this->getQuoteId();
        $identities = [self::CACHE_TAG];

        if ($quoteId) {
            $identities[] = self::CACHE_TAG . '_' . $quoteId;
            $identities[] = 'quote_' . $quoteId;
        }

        return $identities;
    }

    public function getCacheKeyInfo(): array
    {
        $quote = $this->getQuote();
        $quoteId = $quote ? (int) $quote->getId() : 0;
        $itemsHash = $quote ? md5($this->serializer->serialize($this->buildItemsSignature($quote))) : '';
        $totalsHash = $quote
            ? md5($this->serializer->serialize([
                'subtotal' => (float) $quote->getSubtotal(),
                'grand_total' => (float) $quote->getGrandTotal(),
                'items_qty' => (float) $quote->getItemsQty(),
            ]))
            : '';

        return [
            'CODAZON_MINICARTPRO',
            (int) $this->_storeManager->getStore()->getId(),
            $this->_storeManager->getStore()->getCurrentCurrencyCode(),
            $this->customerSession->getCustomerGroupId(),
            $quoteId,
            $itemsHash,
            $totalsHash,
        ];
    }

    private function getQuoteId(): ?int
    {
        $quote = $this->getQuote();
        return $quote ? (int) $quote->getId() : null;
    }

    private function getQuote(): ?QuoteEntity
    {
        $quote = $this->checkoutSession->getQuote();
        return $quote instanceof QuoteEntity ? $quote : null;
    }

    private function buildItemsSignature(QuoteEntity $quote): array
    {
        $items = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $items[] = [
                'id' => (int) $item->getId(),
                'sku' => (string) $item->getSku(),
                'qty' => (float) $item->getQty(),
                'row_total' => (float) $item->getRowTotal(),
                'updated_at' => (string) $item->getUpdatedAt(),
            ];
        }

        return $items;
    }
}
