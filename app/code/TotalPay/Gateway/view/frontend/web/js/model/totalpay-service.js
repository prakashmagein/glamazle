define(
    [
        'underscore',
        'ko',
        'TotalPay_Gateway/js/action/restore-cart',
        'Magento_Checkout/js/model/quote',
        'jquery'
    ],
    function(_, ko, restoreCartAction, quote, $) {
        'use strict';

        var isInAction = ko.observable(false);
        var isLightboxReady = ko.observable(false);
        var iframeHeight = ko.observable('100%');
        var iframeWidth = ko.observable('100%');

        return {
            isInAction: isInAction,
            isLightboxReady: isLightboxReady,
            iframeHeight: iframeHeight,
            iframeWidth: iframeWidth,
            stopEventPropagation: function(event) {
                event.stopImmediatePropagation();
                event.preventDefault();
            },
            leaveEmbeddedIframe: function() {
                // restoreCartAction();
                isInAction(false);
                isLightboxReady(false);
            },
            leaveIframeForLinks: function(event) {
                //Was the click on a link?
                if ($(event.target).closest('a, span, button, input').length) {
                    //restore the cart and close the iframe
                    // restoreCartAction();
                    isInAction(false);
                    isLightboxReady(false);
                } else {
                    //stop the click from propagating.
                    event.stopImmediatePropagation();
                    event.preventDefault();
                }
            },

            iframeResize: function(event) {
                var data = JSON.parse(event);
                if (data.iframe && window.checkoutConfig.payment[quote.paymentMethod().method].iframeEnabled === '1') {
                    if (this.iframeHeight() != data.iframe.height && data.iframe.height != '0px') {
                        this.iframeHeight(data.iframe.height);
                    }
                    if (this.iframeWidth() != data.iframe.width) {
                        this.iframeWidth(data.iframe.width);
                    }
                }
            }
        };
    }
);
