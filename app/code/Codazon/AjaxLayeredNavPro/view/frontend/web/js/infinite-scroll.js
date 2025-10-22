/**
 * Copyright © 2018 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery', 'mage/template', 'jquery-ui-modules/widget'
], function($, mageTmpl) {
    $.widget('codazon.prdLstInfScroll', {
        options: {
            autoLoadMore: true,
            link: {
                next: '.pages-item-next a.next',
                prev: '.pages-item-previous a.previous'
            },
            itemsWrap: '.products.items.product-items',
            toolbar: '.toolbar.toolbar-products',
            pager: '.toolbar.toolbar-products .pages',
            loaderTmpl: '#cdz-lyn-loader'
        },
        _prepareAll: function () {
            this.$list = $(this.options.itemsWrap);
            if (this.$list.length) {
                this._prepareHtml();
            } else if (this.interval) {
                clearInterval(this.interval);
            }
        },
        _create: function() {
            this._prepareAll();
            $('body').on('layeredNavLoaded', this._prepareAll.bind(this));
        },
        _getUrlParams: function(url) {
            var urlPaths = url.split('?'),
            baseUrl = urlPaths[0], urlParams = urlPaths[1] ? urlPaths[1].split('&') : [],
            paramData = {},
            params, i, decode = window.decodeURIComponent;
            for (i = 0; i < urlParams.length; i++) {
                params = urlParams[i].split('=');
                paramData[decode(params[0])] = params[1] !== undefined ?
                decode(params[1].replace(/\+/g, '%20')) : '';
            }
            return paramData;
        },
        _getValueFromUrl: function(url, code, offset) {
            var params = this._getUrlParams(url);
            if (params[code]) {
                var temp = params[code].split('-');
                return temp[offset] ? parseFloat(temp[offset]) : parseFloat(temp[0]);
            }
            return false;
        },
        _addParamsToUrl: function(url, params) {
            var oldParams = this._getUrlParams(url), urlPaths = url.split('?'), baseUrl = urlPaths[0],
            prStr = [], params = $.extend(oldParams, params);
            $.each(params, function(name, value) {
                prStr.push(name+'='+value);
            });
            return baseUrl + '?' + prStr.join("&");
        },
        _prepareHtml: function() {
            var self = this, conf = this.options;
            this.$pool = $('#infscr-pool');
            if (!this.$pool.length) {
                this.$pool = $('<div id="infscr-pool" class="infscr-pool">').insertAfter(this.$list).hide();
                this.$loadPrev = this.$loadNext = this.$after = false;
                this.processing = false;
                if (conf.autoLoadMore) {
                    this.$after = $('<div class="cdz-infscr-placeholder" role="infscr-after" style="height:1px">').insertAfter(this.$list);
                } else if ($(conf.pager).last().find(conf.link['next']).length) {
                    this.$loadNext = $(mageTmpl('#cdz-lyn-view-next', {})).insertAfter(this.$list);
                }
                this.loader = {}; this.pageItems = [];
                this.loader['next'] = $(mageTmpl(conf.loaderTmpl, {})).hide().insertAfter(this.$list);
                this.prePage = parseInt(this._getValueFromUrl(document.URL, 'p'));
                this.prePage = this.prePage ? this.prePage : 1;
                this.pageUrl = document.URL;
                var $items = this.$list.children();
                this.pageItems.push($items.first().attr('data-pageurl', this.pageUrl));
                this.pageItems.push($items.last().attr('data-pageurl', this.pageUrl));
                if (this.prePage > 1) {
                    this.$loadPrev = $(mageTmpl('#cdz-lyn-view-prev', {})).insertBefore(this.$list);
                    this.loader['prev'] = $(mageTmpl(conf.loaderTmpl, {})).hide().insertAfter(this.$loadPrev);
                }
                this._bindEvents();
            }
        },
        _bindEvents: function() {
            var self = this, conf = this.options;
            if (this.interval) {
                clearInterval(this.interval);
            }
            this.interval = setInterval(function() {
                if (self.$after && !self.processing) {
                    if (self._checkVisible(self.$after)) self._loadNext();
                }
                $.each(self.pageItems, function(i, $item) {
                    if (self._checkVisible($item)) {
                        if ($item.attr('data-pageUrl') != self.pageUrl) {
                            self.pageUrl = $item.attr('data-pageUrl');
                            window.history.replaceState({url: self.pageUrl},"", self.pageUrl);
                        }
                    }
                });
            }, 500);
            if (this.$loadNext) this.$loadNext.find('button').on('click', self._loadNext.bind(this));
            if (this.$loadPrev) this.$loadPrev.find('button').on('click', self._loadPrev.bind(this));
        },
        _loadPrev: function() {
            if (this.prePage > 1) {
                var $page = $(this.options.pager).last().find('a').first();
                if ($page.length) this._ajaxLoad('prev', this._addParamsToUrl($page.attr('href'), {p: this.prePage - 1}));
            }
        },
        _loadNext: function() {
            this._ajaxLoad('next');
        },
        _ajaxLoad: function(type, ajaxUrl) {
            this.processing = true;
            var self = this, conf = this.options;
            if (!ajaxUrl) {
                var $link = $(conf.pager).last().find(conf.link[type]);
                if ($link.length) {
                    ajaxUrl = $link.attr('href');
                }
            }       
            if (ajaxUrl) {
                this.loader[type].show();
                $.ajax({
                    url: ajaxUrl,
                    data: {ajax_nav: 1},
                    type: 'GET',
                    cache: true,
                    success: function(rs) {
                        if (rs.category_products) {
                            var $itemsWrap = $(conf.itemsWrap),
                            moveType = (type == 'next') ? 'appendTo' : 'prependTo',
                            $toolbarBottom = $(conf.toolbar).last();
                            self.$pool.html(rs.category_products);
                            var $newWrap = self.$pool.find(conf.itemsWrap);
                            if ($newWrap.length) {
                                var $items = $newWrap.children()[moveType]($itemsWrap),
                                pageUrl = rs.updated_url ? rs.updated_url : ajaxUrl;
                                self.pageItems.push($items.first().attr('data-pageurl', pageUrl));
                                self.pageItems.push($items.last().attr('data-pageurl', pageUrl));
                            }
                            if (type == 'prev') {
                                self.prePage--;
                                if (self.prePage <= 1) self.$loadPrev.hide();
                            } else {
                                var $newToolbarBottom = self.$pool.find(conf.toolbar).last();
                                if ($newToolbarBottom.length) $toolbarBottom.replaceWith($newToolbarBottom.removeAttr('data-mage-init'));
                            }
                            self.$pool.empty();
                            if ($(conf.toolbar).last().find(conf.link[type]).length == 0) {
                                if (self.$loadNext) self.$loadNext.hide();
                            }
                            setTimeout(function() {
                                $('body').trigger('contentUpdated');
                            }, 100);
                        }
                    }
                }).always(function() {
                    setTimeout(function() {
                        self.loader[type].hide();
                        self.processing = false;
                    }, 100);
                });
            } else {
                self.processing = false;
            }
        },
        _checkVisible: function($el){
            var cond1 = ($el.get(0).offsetWidth > 0) && ($el.get(0).offsetHeight > 0),
            cond2 = ($el.is(':visible')),
            winTop = window.pageYOffset,
            winBot = winTop + window.innerHeight,
            elTop = $el.offset().top,
            elHeight = $el.outerHeight(true),
            elBot = elTop + elHeight,
            cond3 = (elTop <= winTop) && (elBot >= winTop),
            cond4 = (elTop >= winTop) && (elTop <= winBot),
            cond5 = (elTop >= winTop) && (elBot <= winBot),
            cond6 = (elTop <= winBot) && (elBot >= winBot);
            return cond1 && cond2 && (cond3 || cond4 || cond5 || cond6);
        }
    });
    return $.codazon.prdLstInfScroll;
});