<?php
namespace TRS\ClearCookies\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Psr\Log\LoggerInterface;

class ClearCustomerCookies implements ObserverInterface
{
    const EVENT_TRIGGERED_COOKIE = 'trs_guest_event_triggered';

    protected $cookieManager;
    protected $cookieMetadataFactory;
    protected $request;
	/**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        Http $request,
        CacheManager $cacheManager,
        LoggerInterface $logger
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->request = $request;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
		try {
            // Clear all caches
            $this->cacheManager->flush($this->cacheManager->getAvailableTypes());
            $this->logger->info('Cache cleared on page visit.');
        // Check if the event_triggered cookie is already set for the guest
        if (!$this->cookieManager->getCookie(self::EVENT_TRIGGERED_COOKIE)) {
            // Your custom logic here (e.g., special offer, message, etc.)
			// List of cookies you want to clear (example cookies)
			$cookiesToClear = [
				'PHPSESSID',            // Session cookie
				'form_key',             // Form key cookie
				'mage-cache-sessid',    // Cache session ID
				'mage-cache-storage',   // Cache storage
				'mage-cache-storage-section-invalidation',
				'mage-translation-storage', 
				'mage-translation-file-version',
				'section_data_ids',     // Cart, wishlist, customer data sections
				'mage-messages',        // Magento messages
			];

			// Iterate through cookies and delete them
			foreach ($cookiesToClear as $cookieName) {
				$metadata = $this->cookieMetadataFactory
					->createPublicCookieMetadata()
					->setPath('/')
					->setDuration(0);

				$this->cookieManager->deleteCookie($cookieName, $metadata);
			}
            // Set a cookie to track that the event has been triggered for this guest customer
            $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
                ->setPath('/')
                ->setDuration(2147483647);  // Cookie duration

            $this->cookieManager->setPublicCookie(self::EVENT_TRIGGERED_COOKIE, '1', $metadata);
       }
		} catch (\Exception $e) {
            $this->logger->error('Error clearing cache: ' . $e->getMessage());
        }
		
    }
}
