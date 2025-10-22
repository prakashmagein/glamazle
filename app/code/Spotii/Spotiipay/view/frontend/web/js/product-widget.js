/*
 * @category    Spotii
 * @package     Spotii_Spotiipay
 * @copyright   Copyright (c) Spotii (https://www.spotii.me/)
 */
define([
    'jquery',
    'ko',
    'uiComponent',
    'domReady!'
], function ($, ko, Component) {
    'use strict';

    return Component.extend({
        initialize: function () {
            //initialize parent Component
            this._super();
            this.processSpotiiDocument();
        },

        processSpotiiDocument: function() {
            console.log("rendering started");
            var self = this;
            console.log(self.jsConfig);
            document.spotiiConfig = self.jsConfig;

            if (!document.spotiiConfig) {
                console.warn('SpotiiPay: document.spotiiConfig is not set, cannot render widget');
                return;
            }

            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = 'https://widget.spotii.me/v1/javascript/price-widget?uuid=' + document.spotiiConfig.merchantID;
            $("head").append(script);

            console.log("dom loaded");
        }
    });
});
