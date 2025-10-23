<?php
declare(strict_types=1);

namespace Codazon\ThemeLayoutPro\Block\Html;

use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;

class StoreLanguageCurrency extends Template
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Template\Context $context,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setCacheLifetime(86400);
    }

    public function getCacheKeyInfo(): array
    {
        $store = $this->storeManager->getStore();

        return [
            'CDZ_STORE_LANGUAGE_CURRENCY',
            $store->getId(),
            $store->getWebsiteId(),
            $store->getCurrentCurrencyCode(),
            $this->_request->getFullActionName(),
        ];
    }

    public function getStoreName(): string
    {
        return (string) $this->storeManager->getStore()->getName();
    }

    public function getCurrencyCode(): string
    {
        return (string) $this->storeManager->getStore()->getCurrentCurrencyCode();
    }
}
