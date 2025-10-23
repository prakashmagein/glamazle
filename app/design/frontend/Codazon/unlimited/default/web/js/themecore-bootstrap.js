/**
 * Copyright © 2017 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([], function () {
    'use strict';

    // Pages where the base theme script should not be loaded automatically.
    var SKIP_PAGE_CLASSES = [
        'checkout-index-index',
        'checkout-cart-index'
    ];

    function hasClass(node, className) {
        if (!node) {
            return false;
        }
        if (node.classList) {
            return node.classList.contains(className);
        }
        return (' ' + node.className + ' ').indexOf(' ' + className + ' ') !== -1;
    }

    function shouldSkip(body) {
        if (window.codazon && window.codazon.skipThemeCore === true) {
            return true;
        }
        if (body && body.dataset && body.dataset.skipThemecore === 'true') {
            return true;
        }
        return SKIP_PAGE_CLASSES.some(function (className) {
            return hasClass(body, className);
        });
    }

    function bootstrap() {
        var body = document.body;

        if (!body || shouldSkip(body)) {
            return;
        }

        require(['js/themecore']);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
        bootstrap();
    }
});
