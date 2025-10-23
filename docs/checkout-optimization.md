# Checkout Performance Notes

The checkout profile provided in the request highlights the following hot spots:

| Section | Time (s) | Notes |
| --- | ---: | --- |
| `Magento\Framework\View\Layout::render` | 1.08 | Rendering the full page dominates the request, with the onepage template accounting for ~0.80 s. |
| `Magento_Customer::account/authentication-popup.phtml` | 0.13 | The authentication popup is rendered for every visitor even though it is only used when a shopper wants to sign in. |
| `Mageplaza_SocialLogin::form/checkout-social.phtml` | 0.0017 | The template retrieves configuration and customer session data on every request. While cheap individually, the additional helpers add object creation overhead during the hot checkout render. |

## Changes in this commit

* Replace the checkout social-login block with an optimized implementation that avoids the `ObjectManager`, caches the social provider list for the duration of the request, and short-circuits rendering when the module is disabled or the shopper is logged in.
* Harden the template by escaping output and relying on the new helper methods, which trims work from Magento's layout engine and reduces PHP allocations.

## Recommended follow-ups

1. **Authentication popup lazy-loading** – render a lightweight placeholder and bootstrap the popup when a shopper clicks "Sign In". This removes the 0.13 s server-side cost for guests that never use the popup.
2. **Theme layout audit** – the checkout layout still loads a significant number of third-party blocks (Google Tag Manager, Page Builder widgets, etc.). Disabling unused observers and widgets on `checkout_index_index` can further reduce the 0.29 s spent generating layout elements.
3. **Persistent session cleanup** – `persistent_synchronize` accounts for 0.049 s in the router stage. If persistent cart is not required, disabling the feature removes the observer and its EAV lookups.
4. **JavaScript payload review** – `onepage.phtml` bootstraps large RequireJS bundles. Bundling critical checkout scripts and deferring non-essential modules (analytics, marketing) will shorten time-to-interactive for shoppers.
5. **Full page cache warm-up** – ensure the checkout-specific customer-data sections are primed so the UI components do not block on AJAX calls during render.

Documenting these items now keeps future work focused on the most expensive portions of the checkout request.
