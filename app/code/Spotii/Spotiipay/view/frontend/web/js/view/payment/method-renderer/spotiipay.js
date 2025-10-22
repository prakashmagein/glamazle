/*
 * @category    Spotii
 * @package     Spotii_Spotiipay
 * @copyright   Copyright (c) Spotii (https://www.spotii.me/)
 */

var isSuccess = false;
var isFail = false;
var isDeclined = false;
var failedCheckOutStatus = 'FAILED';
var submittedCheckOutStatus = 'SUBMITTED';
var successCheckOutStatus = 'SUCCESS';
var toggleFlag = true;
var jsonData;
var rejectUrl;
var confirmUrl;
var popup = true;
const root = document.getElementsByTagName('body')[0];
var hidePopup = false;
var buttonOnce = true;
//Build lightbox component
var button1 = document.createElement('button');
button1.style.display='none';
button1.id = 'closeclick';
button1.textContent = 'set overlay closeClick to false';
var bodyTag=document.getElementsByTagName('body')[0];
bodyTag.appendChild(button1);

var button2 = document.createElement('button');
button2.style.display='none';
button2.id = 'closeiframebtn';
button2.textContent = 'set overlay closeClick to false';
bodyTag.appendChild(button2);

var a1 = document.createElement('a');
a1.id = 'fancy';
a1.style.display='none';
a1.classList= 'fancy-box lightbox';
a1.textContent ='open lightbox';
a1.href='';
bodyTag.appendChild(a1);

var LoadCSS = function (filename) {
  var fileref = document.createElement("link");
  fileref.setAttribute("rel", "stylesheet");
  fileref.setAttribute("type", "text/css");
  fileref.setAttribute("href", filename);
  document.getElementsByTagName("head")[0].appendChild(fileref);
};
LoadCSS("https://widget.spotii.me/v1/javascript/iframe-lightbox.min.css");
var script = document.createElement('script');
script.type = 'text/javascript';
script.src = 'https://widget.spotii.me/v1/javascript/iframe-lightbox.min.js';
document.getElementsByTagName('body')[0].appendChild(script);
//-----------------

//Check if browser support the popup
const thirdPartySupported = root => {
  return new Promise((resolve, reject) => {
    const receiveMessage = function(evt) {
      if (evt.data === 'MM:3PCunsupported') {
        reject();
      } else if (evt.data === 'MM:3PCsupported') {
        resolve();
      }
    };
    window.addEventListener('message', receiveMessage, false);
    const frame = createElement('iframe', {
      src: 'https://mindmup.github.io/3rdpartycookiecheck/start.html',
    });
    frame.style.display = 'none';
    root.appendChild(frame);
  });
};

//Redirect to Spotii
const redirectToSpotiiCheckout = function(checkoutUrl, timeout) {
  setTimeout(function() {
    window.location = checkoutUrl;
  }, timeout); // 'milli-seconds'
};
//Check if it's a safari broswer
function isMobileSafari() {
  const ua = (window && window.navigator && window.navigator.userAgent) || '';
  const iOS = !!ua.match(/iPad/i) || !!ua.match(/iPhone/i);
  const webkit = !!ua.match(/WebKit/i);
  return iOS && webkit && !ua.match(/CriOS/i);
}

//needed functions for the loadin page
function createElement(tagName, attributes, content) {
  const el = document.createElement(tagName);

  if (attributes) {
      Object.keys(attributes).forEach(function(attr) {
          el[attr] = attributes[attr];
      });
  }

  if (content && content.nodeType === Node.ELEMENT_NODE) {
      el.appendChild(content);
  } else {
      el.innerHTML = content;
  }

  return el;
}

function Spinner() {
  const span = createElement('span');
  span.className = 'sptii-loading-icon';
  span.innerHTML =
      '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 1024 1024"><path d="M988 548c-19.9 0-36-16.1-36-36 0-59.4-11.6-117-34.6-171.3a440.45 440.45 0 0 0-94.3-139.9 437.71 437.71 0 0 0-139.9-94.3C629 83.6 571.4 72 512 72c-19.9 0-36-16.1-36-36s16.1-36 36-36c69.1 0 136.2 13.5 199.3 40.3C772.3 66 827 103 874 150c47 47 83.9 101.8 109.7 162.7 26.7 63.1 40.2 130.2 40.2 199.3.1 19.9-16 36-35.9 36z" fill="orange" /></svg>';
  return span;
}
function Logo() {
  const span = createElement("span");
  span.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 574.97 155.42"><defs><style>.cls-1{fill:#858585;}.cls-2{fill:#333;}</style></defs><title>Spotii_dark_logo</title><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><g id="Spotii_dark_logo"><path class="cls-1" d="M93.19,42.93l23.28-23.28A65.93,65.93,0,0,0,13.11,32.76,65.9,65.9,0,0,1,93.19,42.93Z"/><path class="cls-1" d="M93.19,42.93,23.28,112.84A65.93,65.93,0,0,0,103.37,123,65.93,65.93,0,0,0,93.19,42.93Z"/><path class="cls-2" d="M23.28,112.84,0,136.12A66,66,0,0,0,103.37,123,65.93,65.93,0,0,1,23.28,112.84Z"/><path class="cls-2" d="M23.28,112.84,93.19,42.93A65.9,65.9,0,0,0,13.11,32.76,65.9,65.9,0,0,0,23.28,112.84Z"/><path class="cls-2" d="M228,94.14c0,14.57-11.15,28.8-34,28.8-26.75,0-35.32-17.31-36-26.74l22.12-4c.35,5.83,4.46,11.49,13.37,11.49,6.69,0,9.95-3.6,9.95-7.37,0-3.09-2.06-5.66-8.4-7l-9.77-2.23c-18.18-3.94-25.38-14.23-25.38-26.23,0-15.6,13.72-28.29,32.75-28.29C217.33,32.59,225.9,48,226.76,58l-21.61,3.95c-.68-5.66-4.28-10.46-12.17-10.46-5,0-9.26,2.91-9.26,7.37,0,3.6,2.92,5.66,6.69,6.34l11.31,2.23C219.39,71,228,81.62,228,94.14ZM425.84,77.72a45.23,45.23,0,1,1-45.23-45.23A45.22,45.22,0,0,1,425.84,77.72Zm-26,0c0-11.73-8.6-21.23-19.2-21.23S361.4,66,361.4,77.72s8.6,21.22,19.21,21.22S399.81,89.44,399.81,77.72ZM518.92,0a13,13,0,1,0,13,13A13,13,0,0,0,518.92,0Zm-13,122.94H532V46.8L505.89,32.48ZM561.94,49.7a13,13,0,1,0-13-13A13,13,0,0,0,561.94,49.7Zm-13,6.43v66.81H575V70.45ZM447.18,32.48H431.49V58.64h15.69V94.21a28.73,28.73,0,0,0,28.74,28.73h13V96.88h-3.49c-6.74,0-12.2-6-12.2-13.48V58.64h15.69V32.48H473.24V14.53L447.1,0ZM265.33,115.93v39.49L239.26,141.1V32.48h26.07v7a39.48,39.48,0,0,1,22.42-7c23.18,0,42,20.25,42,45.23s-18.79,45.23-42,45.23A39.56,39.56,0,0,1,265.33,115.93Zm0-37.48c.36,11.38,8.79,20.48,19.16,20.48,10.61,0,19.21-9.5,19.21-21.22s-8.6-21.23-19.21-21.23c-10.37,0-18.8,9.11-19.16,20.48Z"/></g></g></g></svg>';
  return span;
}
function SpinTextNode() {
  const text = isMobileSafari() ? 'Redirecting you to Spotii...' : 'Checking your payment status with Spotii...';
  const first= createElement('p', {}, text);
  const cont = createElement('span', {className: 'sptii-text'}, first);
  const spinner = createElement('span', { className: 'sptii-loading' }, Spinner());
  const spinText = createElement('span', { className: 'sptii-spinnerText' }, cont);
  spinText.appendChild(spinner);
  return spinText;
}
//--------------------

//Show the loading page
function showOverlay() {
  const overlay = createElement('div', {className: 'sptii-overlay'}, '');
  const logo = createElement('span', { className: 'sptii-logo' }, Logo());
  document.getElementsByTagName("body")[0].appendChild(overlay);
  overlay.appendChild(logo);
  overlay.appendChild(SpinTextNode());
}

//Remove the loading page
function removeOverlay() {
  var overlay = document.getElementsByClassName("sptii-overlay")[0];
  document.getElementsByTagName("body")[0].removeChild(overlay);
}

//Google tag manager 
function onCheckout() {
  if (typeof dataLayer !== 'undefined') {
    // the variable is defined
    dataLayer.push({
      'event': 'checkout',
      'ecommerce': {
        'checkout': {
          'actionField': { 'step': 3, 'option': 'Spotiipay' }
        }
      }
    }
    );
    dataLayer.push({
      'event': 'checkoutOption',
      'ecommerce': {
        'checkout_option': {
          'actionField': { 'step': 3, 'option': 'Spotiipay' }
        }
      }
    });
  }
}

//Handle the response Decline/Accept
window.closeIFrameOnCompleteOrder = function (message) {
  var status = message.status;
  rejectUrl = message.rejectUrl;
  confirmUrl = message.confirmUrl;
  hidePopup = message.hidePopup;

  switch (status) {
    case successCheckOutStatus: {
      document.cookie='spt_mgt_mpt_crt_scs_msg=true';
      console.log("cookie saved");
      if (!isSuccess) {
        isSuccess = true;
        if (typeof dataLayer !== 'undefined') {
          var params = confirmUrl.split('/');
          var reference = params[params.length - 2];
          var ids = reference.split('-');
          var id = ids[1];

          dataLayer.push({
            'event': 'purchase',
            'ecommerce': {
              'purchase': {
                'actionField': {
                  'id': id,                // Transaction ID. Required for purchases and refunds.
                  'affiliation': 'Spotii',
                }
              }
            }
          });
        }
        location.href = confirmUrl;
        document.getElementById('closeiframebtn').onclick = function () {
          location.href = confirmUrl;
        };
        removeOverlay();
      }
      break;
    }
    case failedCheckOutStatus: {
      document.cookie="spt_mgt_mpt_crt_scs_msg=false";

      if (hidePopup && popup) {
        popup = false;
        document.getElementById('closeiframebtn').click();
      }
      if (!isFail) {
        isFail = true;
        isDeclined = true;

        document.getElementById('closeiframebtn').onclick = function () {
          if (buttonOnce) {
            buttonOnce = false;
            var rejectUrlSubmitted = rejectUrl.substring(0, rejectUrl.length - 2) + "1/";
            location.href = rejectUrlSubmitted;
          }
        };
        removeOverlay();
      }

      break;
    }
    case submittedCheckOutStatus: {
      removeOverlay();
      break;
    }
    default: {
      removeOverlay();
      break;
    }
  }
};

define([
  "Magento_Customer/js/model/customer",
  "Magento_Checkout/js/model/resource-url-manager",
  "mage/storage",
  "Magento_Checkout/js/view/payment/default",
  "jquery",
  "Magento_Checkout/js/model/payment/additional-validators",
  "Magento_Checkout/js/action/set-payment-information",
  "mage/url",
  "mage/translate",
  "Magento_Checkout/js/checkout-data",
  "Magento_Checkout/js/action/select-payment-method",
  "Magento_Ui/js/model/messageList",
  "Magento_Checkout/js/model/quote",
], function (
  customer,
  resourceUrlManager,
  storage,
  Component,
  $,
  additionalValidators,
  setPaymentInformationAction,
  mageUrl,
  $t,
  checkoutData,
  selectPaymentMethodAction,
  globalMessageList,
  quote,
  fancy
) {
  "use strict";
  return Component.extend({
    defaults: {
      template: "Spotii_Spotiipay/payment/spotiipay",
    },

    getSpotiipayImgSrc: function () {
      return "data:image/png;base64,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";
    },

    /**
     * Get Grand Total of the current cart
     * @returns {*}
     */
    getGrandTotal: function () {
      var total = quote.getCalculatedTotal();
      var format = window.checkoutConfig.basePriceFormat.pattern;

      storage
        .get(resourceUrlManager.getUrlForCartTotals(quote), false)
        .done(function (response) {
          var amount = response.base_grand_total;
          var installmentFee = response.base_grand_total / 4;
          var installmentFeeLast =
            amount -
            installmentFee.toFixed(
              window.checkoutConfig.basePriceFormat.precision
            ) *
              3;

          $(".spotii-grand-total").text(
            "Total : " +
            format.replace(
              /%s/g,
              amount.toFixed(window.checkoutConfig.basePriceFormat.precision)
            )
          );
          $(".spotii-installment-amount").text(
            format.replace(
              /%s/g,
              installmentFee.toFixed(
                window.checkoutConfig.basePriceFormat.precision
              )
            )
          );
          $(".spotii-installment-amount.final").text(
            format.replace(
              /%s/g,
              installmentFeeLast.toFixed(
                window.checkoutConfig.basePriceFormat.precision
              )
            )
          );

          return format.replace(/%s/g, amount);
        })
        .fail(function (response) {
          //do your error handling

          return "Error";
        });
    },
    /**
     * Get Checkout Message based on the currency
     * @returns {*}
     */
    getPaymentText: function () {
      return "Payment Schedule";
    },
    getTotalInvalidText: function () {	   
      var curr = window.checkoutConfig.quoteData.base_currency_code;	      
      var min="200 AED";	    
      switch(curr){	   
        case "AED":	     
          min="200 AED";	
          break;	
        case "SAR":	
          min="200 SAR";	
          break;	
        case "BHD":	
          min="20 BHD";	
          break;
        case "KWD":
          min="15 KWD";
          break;
        case "OMR":
          min="10 OMR";
          break;	
      }	
        return (this.isTotalValid() ? '':"You don't quite have enough in your basket: Spotii is available for purchases over "+min+" (excluding shipping). With a little more shopping, you can split your payment over 4 cost-free instalments.");	
    },
    getQtyInvaildText: function () {
      document.getElementById('total-benchmark-info').textContent = "One or more of the items in your cart are out of stock.";
    },
    redirectToSpotiipayController: function (data) {
      if (!isDeclined) {
        // Make a post request to redirect
        var renderPopup = function (url) {
          openIframeSpotiiCheckout(url);
        };

        var openIframeSpotiiCheckout = function (checkoutUrl) {

          $('.lightbox').attr('href', checkoutUrl).attr("data-src", checkoutUrl);
          loadIFrame();
        };

        if (toggleFlag) {

          onCheckout();

          var url = mageUrl.build("spotiipay/standard/redirect");
          var x = this;
          $.ajax({
            url: url,
            method: "post",
            showLoader: true,
            data: data,
            success: function (response) {
              toggleFlag = false;
              // Send this response to spotii api
              // This would redirect to spotii
              try{
              jsonData = $.parseJSON(response);
              if (jsonData.redirectURL) {
                if (isMobileSafari()) {
                  redirectToSpotiiCheckout(jsonData.redirectURL, 2500);
                } else {
                  thirdPartySupported(root).then(() => {
                    renderPopup(jsonData.redirectURL);
                  })
                    .catch(() => {
                      redirectToSpotiiCheckout(jsonData.redirectURL, 2500);
                    });
                }
              } else if (typeof jsonData["message"] !== "undefined") {
                globalMessageList.addErrorMessage({
                  message: jsonData["message"],
                });
              }
            }catch(e){
              removeOverlay();
              x.getQtyInvaildText();
            }},
          });
        } else {
          loadIFrame();
        }
      }
    },
    handleRedirectAction: function () {
      var data = $("#co-shipping-form").serialize();
      if (!customer.isLoggedIn()) {
        var email = quote.guestEmail;
        data += "&email=" + email;
      }

      this.redirectToSpotiipayController(data);
    },

    continueToSpotiipay: function () {
      var url = mageUrl.build("spotiipay/standard/checkinventory");
      var finalResult = [];
      var itemsFromQuote = window.checkoutConfig.quoteItemData;
      for (var i = 0; i < itemsFromQuote.length; i++) {
        var tempQty = itemsFromQuote[i].qty;
        var tempSku = itemsFromQuote[i].sku;
        finalResult.push({ qty: tempQty, sku: tempSku });
      }
      var jsonString = JSON.stringify(finalResult);
      var x = this;
      var y = additionalValidators;
      $.ajax({
        url: url,
        method: "post",
        showLoader: true,
        //async: true,
        data: { "items": jsonString },
        success: function (response) {
          var jsonItems = $.parseJSON(response);
          if (
            x.validate() &&
            y.validate() &&
            x.isTotalValid() && jsonItems.isInStock
          ) {
            showOverlay();
            x.handleRedirectAction();
          }
          else if(!x.isTotalValid()){
            x.getTotalInvalidText();
          }
          else {
            console.log("redirect failed");
            x.getQtyInvaildText();
          }
        }
      });
    },

    isTotalValid: function () {
      var total = this.getGrandTotal() ? this.getGrandTotal() : window.checkoutConfig.quoteData.grand_total;
      var curr = window.checkoutConfig.quoteData.quote_currency_code;
      var min=200;
      switch(curr){
        case "USD":
          total= total*3.6730;
          break;
        case "BHD":
          min= 20;
          break;
        case "KWD":
          min=15;
          break;
        case "OMR":
          min=10;
          break;
      }
      console.log(total+curr);
      if (total >= min) return true;
      else return false;
    },

    placeOrder: function (data, event) {
      this.continueToSpotiipay();
    },
  });
});