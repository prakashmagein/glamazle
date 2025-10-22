define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/place-order',
        'Magento_Customer/js/customer-data',
        'Magento_Catalog/js/price-utils'
    ],
    function (ko, $, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder, customerData, priceUtils) {
        'use strict';
        var promise = '';
console.log('kkkkkk');
        return Component.extend({
            defaults: {
                template: 'Telr_TelrPayments/payment/applepay'
            },
            getCode: function () {
                return 'telr_applepay';
            },
            isActive: function () {
                return true;
            },
            context: function () {
                return this;
            },
            redirectAfterPlaceOrder: false,
            beforeApplePay: function () {  console.log('kkkkkk');
                document.getElementById("telrApplePay").disabled = false;
                if (window.ApplePaySession) {
                    if (ApplePaySession.canMakePayments) {
                        document.getElementById("telrApplePay").style.display = "block";
                    } else {
                        $(".telr-apple-err").text('');
                        $("#telrApplePay").remove();
                        $("#payment_method_telr_applepay").remove();
                    }
                } else {
                    $(".telr-apple-err").text('');
                    $("#telrApplePay").remove();
                    $("#payment_method_telr_applepay").remove();
                }
            },
            afterPlaceOrder : function () {  console.log('hello');
                window.currentTelrContext = this;
                var totals = quote.totals();
                var orderTotal = (totals ? totals : quote)['grand_total'];
                var currencyCode = (totals ? totals : quote)['quote_currency_code'];

                var countryCode = window.checkoutConfig.payment.telr_applepay.storeCountryCode;
                var storeName = window.checkoutConfig.payment.telr_applepay.storeName;
                 
                console.log(currencyCode);
                console.log(storeName);
                console.log(orderTotal);
                var paymentRequest = {
                    currencyCode: currencyCode,
                    countryCode: countryCode,
                    total: {
                        label: storeName,
                        amount: orderTotal
                    },
                    //supportedNetworks: window.checkoutConfig.payment.apsFort.aps_apple.appleSupportedNetwork.split(','),
                    supportedNetworks: ['amex', 'masterCard', 'visa'],
                    merchantCapabilities: [ 'supports3DS' ]
                };

                //var supportedNetworks = window.checkoutConfig.payment.apsFort.aps_apple.appleSupportedNetwork.split(',');
                /*if(supportedNetworks.indexOf('mada') >= 0) {
                    var session = new ApplePaySession(5, paymentRequest);
                } else {
                    var session = new ApplePaySession(3, paymentRequest);
                }*/

                var session = new ApplePaySession(3, paymentRequest);

                session.onvalidatemerchant = function (event) {
                    var promise = performValidation(event.validationURL);
                    promise.then(function (merchantSession) {
                        session.completeMerchantValidation(merchantSession);
                    }).catch(function (validationErr) {
                        $(".telr-apple-err").text('Unable to process Apple Pay payment. Please reload the page and try again. Error Code: E004');
                        setTimeout(function(){
                            $(".telr-apple-err").text('');
                        }, 5000);
                        session.abort();
                    });
                }

                function performValidation(valURL)
                {
                    return new Promise(function (resolve, reject) {
                        var xhr = new XMLHttpRequest();
                        xhr.onload = function () {
                            var data = JSON.parse(this.responseText);
                            resolve(data);
                        };
                        xhr.onerror = reject;
                        xhr.open('POST',window.checkoutConfig.payment.telr_applepay.appleValidationUrl);
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        xhr.send('valURL=' + valURL);
                    }).catch(function (validationErr) {
                        $(".telr-apple-err").text('Unable to process Apple Pay payment. Please reload the page and try again. Error Code: E001');
                        setTimeout(function(){
                            $(".telr-apple-err").text('');
                        }, 5000);
                        session.abort();
                    });
                }

                var paymentData = {};
                session.onpaymentauthorized = function (event) {
                    var promise = sendPaymentToken(event.payment.token);
                    promise.then(function (success) {
                        var status;
                        if (success) {
                            status = ApplePaySession.STATUS_SUCCESS;
                            sendPaymentToTelr(paymentData);
                        } else {
                            status = ApplePaySession.STATUS_FAILURE;
                        }
                        session.completePayment(status);
                    }).catch(function (validationErr) {
                        $(".telr-apple-err").text('Unable to process Apple Pay payment. Please reload the page and try again. Error Code: E002');
                        setTimeout(function(){
                            $(".telr-apple-err").text('');
                        }, 5000);
                        session.abort();
                    });
                }

                session.oncancel = function (event) {
                    //window.location.href = window.checkoutConfig.payment.telr_applepay.appleFailedUrl;
                }
                
                function sendPaymentToken(paymentToken)
                {
                    window.currentTelrContext.placeOrder();
                    return new Promise(function (resolve, reject) {
                        paymentData = paymentToken;
                        resolve(true);
                    }).catch(function (validationErr) {
                        $(".telr-apple-err").text('Unable to process Apple Pay payment. Please reload the page and try again. Error Code: E003');
                        setTimeout(function(){
                            $(".telr-apple-err").text('');
                        }, 5000);
                        session.abort();
                    });
                }
            
                function sendPaymentToTelr(data)
                {
                    var formId = 'telr_submit_apple_pay_form';
                    if (jQuery("#"+formId).length > 0) {
                        jQuery("#"+formId).remove();
                    }

                    $('<form id="'+formId+'" action="#" method="POST"></form>').appendTo('body');
                    var response = {};
                    response.data = JSON.stringify({ "data" : data});
                    $.each(response, function (k, v) {
                        $('<input>').attr({
                            type: 'hidden',
                            id: k,
                            name: k,
                            value: v
                        }).appendTo($('#'+formId));
                    });
                    
                    $('#'+formId).attr('action', window.checkoutConfig.payment.telr_applepay.applePaymentProcessUrl);
                    $('#'+formId).submit();
                }
                
                session.begin();
            },





        });
    }
);
