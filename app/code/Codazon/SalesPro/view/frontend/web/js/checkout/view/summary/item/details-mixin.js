/**
 * Copyright © Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'escaper',
    'uiComponent',
    'Magento_Customer/js/model/authentication-popup',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/model/resource-url-manager',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'mage/url',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'underscore',
    'uiRegistry',
    'mage/cookies'
], function($, escaper, Component, authenticationPopup, customerData, quote, getTotalsAction, shippingService, rateRegistry, resourceUrlManager, storage, errorProcessor, url, alert, confirm, _, uiRegistry) {
    'use strict';
    return function(Component) {
        return Component.extend({
            shoppingCartUrl: window.checkout.shoppingCartUrl,
            defaults: {
                template: 'Codazon_SalesPro/checkout/summary/item/details',
                allowedTags: ['b', 'strong', 'i', 'em', 'u']
            },
            initialize: function () {
                this._super();
                if (window.hbQuoteTotalUpdate == undefined) {
                    window.hbQuoteTotalUpdate = true;
                    var cartItems = uiRegistry.get(this.parentName);
                    if (cartItems) {
                        cartItems.getItemsQty = function() {
                            return parseFloat(quote.totals().items_qty);
                        }
                    }
                    $('.page-header a.action.showcart').css('pointer-events','none');
                    $('#desk_cart-wrapper, #mobi_cart-wrapper').on('click', function() {
                        $('html,body').animate({scrollTop: $('.opc-sidebar .block.items-in-cart').offset().top - 60});
                    });
                }
            },
            getNameUnsanitizedHtml: function (quoteItem) {
                var txt = document.createElement('textarea');
                txt.innerHTML = quoteItem.name;
                return escaper.escapeHtml(txt.value, this.allowedTags);
            },
            updateItemQtyCheckout: function(data, event) {
                var itemId = event.currentTarget.dataset.cartItem, step = parseFloat(event.currentTarget.dataset.step),
                $qty = $('#osc-cart-item-' + itemId + '-qty');
                $qty.val(parseFloat($qty.val()) + step);
                if ((step < 0) && $qty.val() == '0') {
                    var productData = this._getProductById(Number(itemId));
                    if (!_.isUndefined(productData)) {
                        this._ajax(url.build('checkout/sidebar/removeItem'), {
                            'item_id': itemId
                        }, $qty, this._removeItemAfter);
                        if (window.location.href === this.shoppingCartUrl) {
                            window.location.reload(false);
                        }
                    }
                } else {
                    this._ajax(url.build('checkout/sidebar/updateItemQty'), {
                        'item_id': itemId,
                        'item_qty': $qty.val()
                    }, $qty, this._updateItemQtyAfter);
                }
            },
            _getProductById: function(productId) {
                return _.find(customerData.get('cart')().items, function(item) {
                    return productId === Number(item['item_id']);
                });
            },
            _updateItemQtyAfter: function($qty) {
                var productData = this._getProductById(Number($qty.data('cart-item')));
                if (!_.isUndefined(productData)) {
                    $(document).trigger('ajax:updateCartItemQty');
                    if (window.location.href === this.shoppingCartUrl) {
                        window.location.reload(false);
                    }
                }
                this._customerData();
            },
            _customerData: function() {
                var deferred = $.Deferred();
                getTotalsAction([], deferred);
                var sections = ['cart'];
                customerData.invalidate(sections);
                customerData.reload(sections, true);
                this._estimateTotalsAndUpdateRatesCheckout();
            },
            _ajax: function(url, data, $qty, callback) {
                var self = this;
                $.extend(data, {
                    'form_key': $.mage.cookies.get('form_key')
                });
                $.ajax({
                    url: url,
                    data: data,
                    type: 'post',
                    dataType: 'json',
                    context: this,
                    beforeSend: function() {
                        $qty.attr('disabled', 'disabled');
                    },
                    complete: function() {
                        $qty.attr('disabled', null);
                    }
                }).done(function(response) {
                    var msg;
                    if (response.success) {
                        callback.call(this, $qty, response);
                    } else {
                        msg = response['error_message'];
                        if (msg) {
                            alert({content: msg});
                            $qty.val($qty.attr('data-item-qty'));
                        }
                    }
                }).fail(function(error) {
                    console.log(JSON.stringify(error));
                });
            },
            _removeItemAfter: function($qty) {
                var productData = this._getProductById(Number($qty.data('cart-item')));
                if (!_.isUndefined(productData)) {
                    $(document).trigger('ajax:removeFromCart', {
                        productIds: [productData['product_id']]
                    });
                    setTimeout(function() {
                        if (customerData.get('cart')().items.length == 0) {
                            window.location.reload();
                        }
                    }, 2000);
                    if (window.location.href.indexOf(this.shoppingCartUrl) == 0) {
                        window.location.reload();
                    }
                }
                this._customerData();
            },
            _estimateTotalsAndUpdateRatesCheckout: function() {
                var serviceUrl, payload = {address:{}}, address = quote.shippingAddress();
                shippingService.isLoading(true);
                serviceUrl = resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress(quote);
                function camelToUnderscore(key) {
                   return key.replace( /([A-Z])/g, " $1" ).split(' ').join('_').toLowerCase();
                }
                $.each(address, function(name, value) {
                    if (typeof value != 'function') {
                        let key = camelToUnderscore(name);
                        if (typeof key == 'string') {
                            payload.address[key] = value;
                        }
                    }
                });
                payload = JSON.stringify(payload);
                storage.post(
                    serviceUrl, payload, false
                ).done(function(result) {
                    rateRegistry.set(address.getCacheKey(), result);
                    shippingService.setShippingRates(result);
                }).fail(function(response) {
                    shippingService.setShippingRates([]);
                    errorProcessor.process(response);
                }).always(function() {
                    shippingService.isLoading(false);
                });
            }
        });
    }
});