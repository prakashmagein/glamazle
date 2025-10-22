define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'telr_applepay',
                component: 'Telr_TelrPayments/js/view/payment/method-renderer/telr-applepay'
            }
        );
        return Component.extend({});
    }
 );
