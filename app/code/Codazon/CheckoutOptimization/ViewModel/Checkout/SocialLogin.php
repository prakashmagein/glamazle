<?php
namespace Codazon\CheckoutOptimization\ViewModel\Checkout;

use Codazon\ThemeLayoutPro\Helper\Data as ThemeLayoutHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Mageplaza\SocialLogin\Block\Popup\Social as SocialBlock;

class SocialLogin implements ArgumentInterface
{
    /** @var ThemeLayoutHelper */
    private $themeLayoutHelper;

    /** @var CustomerSession */
    private $customerSession;

    /**
     * @var array<string, array<int|string, mixed>>
     */
    private $cachedSocials = [];

    public function __construct(
        ThemeLayoutHelper $themeLayoutHelper,
        CustomerSession $customerSession
    ) {
        $this->themeLayoutHelper = $themeLayoutHelper;
        $this->customerSession = $customerSession;
    }

    public function shouldRender(SocialBlock $block): bool
    {
        if (!$block->canShow()) {
            return false;
        }

        if (!$this->isDisplayButtonsEnabled()) {
            return false;
        }

        if ($this->customerSession->isLoggedIn()) {
            return false;
        }

        return (bool) count($this->getPreparedSocials($block));
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getPreparedSocials(SocialBlock $block): array
    {
        $hash = spl_object_hash($block);
        if (!isset($this->cachedSocials[$hash])) {
            $socials = $block->getAvailableSocials();
            $this->cachedSocials[$hash] = is_array($socials) ? $socials : [];
        }

        return $this->cachedSocials[$hash];
    }

    public function isDisplayButtonsEnabled(): bool
    {
        return (bool) $this->themeLayoutHelper->getConfig('checkout/general/display_social_login_buttons');
    }
}
