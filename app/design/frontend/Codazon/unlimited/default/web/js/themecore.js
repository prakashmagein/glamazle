/**
 * Copyright © 2017 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
require(['jquery', 'owlslider'], function($, owlSlider) {
/* Common value */
var mBreakpoint = 768, $body = $('body'),
$win = $(window), winwidth = window.innerWidth, deskPrefix = 'desk_', mobiPrefix = 'mobi_', disHovImg = $body.hasClass('product-disable-hover-img'),
deskEvent = 'cdz_desktop', mobiEvent = 'cdz_mobile', winWidthChangedEvent = 'cdz_window_width_changed', itOneSec = false;
if (typeof window.windowLoaded == 'undefined') window.windowLoaded = false;
window.intervalOneSec = (func) => {
    if (!itOneSec) itOneSec = setInterval(() => {
        $win.trigger('itOneSec');
    }, 1000);
    $win.on('itOneSec', func);
};
!function(t){var i=t(window),e=!1,r={};function n(e,r){var n=e.filter(function(){var e=t(this);if(!e.is(":hidden")){var n=i.scrollTop(),a=n+i.height(),s=e.offset().top;return s+e.height()>=n-r&&s<=a+r}});e=e.not(n.trigger("unveil"))}t.fn.cdzLazy=function(a,s){var o=Math.random(),h=a||0,u=window.devicePixelRatio>1?"data-src-retina":"data-src";return r[o]={img:this,th:h},this.one("unveil",function(){var i=this.getAttribute(u),e=this.tagName.toLowerCase();(i=i||this.getAttribute("data-src"))&&("img"===e||"iframe"===e?this.setAttribute("src",i):t(this).css("background-image",`url(${i})`),"function"==typeof s&&s.call(this),delete r[o])}),n(this,h),e||(e=()=>{t.each(r,(t,i)=>{n(i.img,i.th)})},i.on("scroll.unveil resize.unveil lookup.unveil",e),intervalOneSec(e)),this}}(jQuery);
    if (window.codazon === undefined) window.codazon = {};    
    $body.on('contentUpdated', () => {
        require(['mage/apply/main'], (mage) => {
            if (mage) mage.apply();
        });
    }); 
    /* jQuery functions */
    $.fn.searchToggle = function(options) {
        var defaultConf = {
            toggleBtn: '[data-role=search_toggle]',
            searchForm: '[data-role=search_form]',
            toggleClass: 'input-opened',
            mbClass: 'mb-search'
        }, conf = $.extend({}, defaultConf, options);
        return this.each(function() {
            var $el = $(this),
            $searchForm = $(conf.searchForm, $el),
            $searchBtn = $(conf.toggleBtn, $el).on('click', () => {
                $el.toggleClass(conf.toggleClass);
            }), mbSearch = () => {
                $el.addClass(conf.mbClass);
                $searchForm.removeClass('hidden-xs');
            }, dtSearch = () => {
                $el.removeClass(conf.mbClass);
                $searchForm.addClass('hidden-xs');
            };
            themecore.isMbScreen() ? mbSearch() : dtSearch();
            $win.on(deskEvent, dtSearch).on(mobiEvent, mbSearch);
        });
    }
    /* Common functions */
    window.themecore = function() { return this; };
    var thc = themecore;
    thc.stickyMenu = () => {
        if ($('.js-sticky-menu').length) require(['themewidgets'], function() {$.codazon.stickyMenu({}, $('.js-sticky-menu'));});
    };
    thc.backToTop = () => {
        if ($('#back-top').length == 0) $('<div id="back-top" class="back-top" data-role="back_top"><a title="Top" href="#top">Top</a></div>').appendTo('body');
        $('[data-role="back_top"]').each(function() {
            var $bt = $(this);
            $bt.on('click', function(e) {
                e.preventDefault();
                $('html, body').animate({'scrollTop':0},800);
            });
            function toggleButton(hide) {
                hide ? $bt.fadeOut(300) : $bt.fadeIn(300);
            }
            var hide = ($win.scrollTop() < 100);
            toggleButton(hide);
            $win.on('scroll', () => {
                var newState = ($win.scrollTop() < 100);
                if(newState != hide){
                    hide = newState;
                    toggleButton(hide);
                }
            });
        });
    }
    thc.b64DecodeUnicode = (str) => {
        return decodeURIComponent(Array.prototype.map.call(atob(str), function (c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
    };
    thc.isMbScreen = (breakP) => {
        if (typeof breakP === 'undefined') breakP = mBreakpoint;
        return (window.innerWidth < breakP);
    };
    thc.isDtScreen = (breakP) => {
        if (typeof breakP === 'undefined') breakP = mBreakpoint;
        return (window.innerWidth >= breakP);
    };
    thc.uniqid = (prefix) => {
        return (prefix ? prefix : '') + Math.random().toString().substring(2,8);
    };
    thc.triggerAdaptScreen = (breakP) => {
        if (typeof breakP === 'undefined') breakP = mBreakpoint;
        var eventSuffix =  (breakP == mBreakpoint)? '' : '_' + breakP, winwidth = window.innerWidth,
        triggerMedia = () => {
            thc.isMbScreen(breakP) ? $win.trigger(mobiEvent + eventSuffix) : $win.trigger(deskEvent + eventSuffix);
        }, checkAdpatChange = () => {
            var curwidth = window.innerWidth;
            if ( ((winwidth < breakP) && (curwidth >= breakP) ) || 
               ( (winwidth >= breakP) && (curwidth < breakP)) ) {
                $win.trigger('adaptchange' + eventSuffix);
                triggerMedia();
            }
            winwidth = curwidth;
        }, t = false;
        $win.on('resize', () => {
            if(t) clearTimeout(t);
            t = setTimeout(checkAdpatChange, 50);
        });
        triggerMedia();
    };
    thc.autoTrigger = () => {
        $body.on('click', '[data-autotrigger]', function(e) {
            e.preventDefault();
            $($(this).data('autotrigger')).first().trigger('click');
        });
    }
    thc.moveElToNewContainer = (fromPrefix, toPrefix) => {
        $(`[id^="${fromPrefix}"]`).each(function() {
           $(this).children().appendTo('#' + toPrefix + this.id.substr(fromPrefix.length));
        });
    };
    thc.moveFromSourceElement = () => {
        $('[data-movefrom]').each(function() {
            var $dest = $(this), $source = $($dest.data('movefrom')).first();
            $dest.replaceWith($source);
        });
    };
    thc.setupMobile = () => {
        thc.moveElToNewContainer(deskPrefix, mobiPrefix);
    };
    thc.setupDesktop = () => {
        thc.moveElToNewContainer(mobiPrefix, deskPrefix);
    };
    thc.winWidthChangedEvent = () => {
        var curwidth = window.innerWidth;
        $win.on('resize', () => {
            if (window.innerWidth != curwidth) {
                curwidth = window.innerWidth;
                $win.trigger(winWidthChangedEvent, [curwidth]);
            }
        });
    };
    thc.scrollTo = () => {
        $body.on('click', '[data-scollto]', function(e) {
            e.preventDefault();
            var $button = $(this), $dest = $($button.data('scollto'));
            if ($dest.is(':visible')) {
                $('html, body').animate({scrollTop: $dest.offset().top - 100}, 300);
            } else {
                if ($dest.parents('[role=tabpanel]').length) {
                    $('a.switch[href="#' + $dest.parents('[role=tabpanel]').first().attr('id') + '"]').click();
                    setTimeout(() => {
                        $('html, body').animate({scrollTop: $dest.offset().top - 100}, 300);
                    }, 300);
                }
            }
        });
    };
    thc.updateFormKey = () => {
        $('.product-item form [name=form_key]').val($('input[name=form_key]').first().val());
    }
    thc.cdzLazyImage = () => {
        $('[data-lazysrc]').each(function() {
            var $img = $(this).attr('src', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=');
            if ($img.attr('width') && $img.attr('height') && (!$img.parent().hasClass('abs-img'))) {
                $img.wrap($('<span class="abs-img">').css({paddingBottom: (100*$img.attr('height'))/$img.attr('width') + '%'}));
            }
            $img.addClass('cdz-lazy owl-lazy').attr('data-src', $img.data('lazysrc')).removeAttr('data-lazysrc').cdzLazy(100, function() {
                $img.removeClass('cdz-lazy owl-lazy').css('display', '');
            });
        });
    }
    thc.init = function() {
        this.triggerAdaptScreen();
        this.triggerAdaptScreen(1200);
        this.winWidthChangedEvent();
        var sht, lt = false;
        $win.on(deskEvent, this.setupDesktop)
            .on(mobiEvent, this.setupMobile)
            .on(winWidthChangedEvent, () => {
                if (sht) clearTimeout(sht);
                sht = setTimeout(this.makeSameHeight, 300);
            });
        this.cdzLazyImage();
        $body.on('contentUpdated cdzResize', () => {
            if (sht) clearTimeout(sht);
            sht = setTimeout(this.makeSameHeight, 100);
        }).on('cdzTabsOpened', (e, $tab) => {
            if (sht) clearTimeout(sht);
            sht = setTimeout(() => {
                this.makeSameHeight($tab);
            }, 100);
        }).on('swatch.initialized', (e) => {
            this.makeSameHeight($(e.target).parents('[data-sameheight]').first().parent());
        });
        $(document).ajaxComplete(() => {
            if (lt) clearTimeout(lt);
            lt = setTimeout(this.cdzLazyImage, 500);
        });
        this.isMbScreen() ? this.setupMobile() : this.setupDesktop();        
        this.toggleMobileMenu();
        this.autoTrigger();
        this.qtyControl();
        this.remoteSliderNav();
        this.scrollTo();
        this.mobiProductViewTabs();
        this.addNumControl();
        $(document).ready(() => {
            this.mbtoolbar();
            this.toggleContent();
            this.moveFromSourceElement();
            this.attachCustomerInfo();
            this.verticalMenu();
            this.backToTop();
            this.isMbScreen() ? this.setupMobile() : this.setupDesktop();
        });
        $body.on('contentUpdated', () => {
            this.toggleContent();
            this.cdzLazyImage();
            this.addNumControl();
        });
        this.makeSameHeight();
        var onLoaded = () => {
            this.ajaxHandler();
            this.stickyMenu();
            this.makeSameHeight();
            this.updateFormKey();
            this.qtyControl();
            this.addNumControl();
            this.sectionMenu();
        }
        window.windowLoaded ? onLoaded() : $win.on('load', onLoaded);
    };
    thc.ajaxHandler = () => {
        $(document).ajaxStart(() => {
            $body.addClass('cdz-ajax-loading');
        });
        $(document).ajaxStop(() => {
            $body.removeClass('cdz-ajax-loading');
        });
    };
    thc.remoteSliderNav = () => {
        $body.on('click', '[data-targetslider]', function() {
            var $btn = $(this), sliderId = $btn.data('targetslider'),
            $slider = $('#' + sliderId).find('.owl-carousel');
            if ($slider.length) $btn.hasClass('owl-prev') ? $slider.trigger('prev.owl.carousel') : $slider.trigger('next.owl.carousel');
        });
    };
    thc.attachCustomerInfo = function() {
        function loadCustomerInfo() {
            if ($('[data-customerinfo]').length) {
                $.ajax({
                    url: codazon.customerDataUrl,
                    type: "get",
                    cache: false,
                    success: (data) => {
                        var customer = data.customer
                        if (customer) {
                            $('[data-customerinfo]').each(function() {
                                var $info = $(this), info = $info.data('customerinfo');
                                $info.removeAttr('data-customerinfo');
                                if (customer[info]) {
                                    $info.replaceWith(customer[info].replace(/(<([^>]+)>)/ig,''));
                                }
                            });
                        }
                    }
                });
            }
        }
        loadCustomerInfo();
        $body.on('contentUpdated', loadCustomerInfo);
    };
    thc.mbtoolbar = function() {
        var $toolbar = $('#mb-bottom-toolbar'), $btnSlider = $('[data-role=group-slider]', $toolbar),
        $switcher = $('[data-role=switch-group]'), clicked = false;
        $btnSlider.owlCarousel({
            items: 1,
            dots: false,
            nav: false,
            animateIn: 'changing',
            animateOut: false,
            touchDrag: false,
            mouseDrag: false,
            rtl: $body.hasClass('rtl-layout'),
            onChanged: function(property) {
                if (clicked) {
                    var dotsCount = $switcher.find('.dot').length;
                    $switcher.toggleClass('return');
                    $switcher.find('.dot').each((i, el) => {
                        var $dot = $(el);
                        setTimeout(() => {
                            $dot.removeClass('wave-line').addClass('wave-line');
                            setTimeout(() => {
                                $dot.removeClass('wave-line');
                            }, 1000);
                        }, i*100);
                    });
                    setTimeout(() => {
                        $btnSlider.find('.owl-item').removeClass('changing animated');
                    },300);
                    clicked = false;
                }
            }
        });
        var owl = $btnSlider.data('owl.carousel'), slideTo = 0;
        $switcher.on('click', (e) => {
            clicked = true;
            e.preventDefault();
            slideTo = !slideTo;
            owl.to(slideTo, 1, true);
        });
        var $currentDisplay = false, $currentPlaceholder = $('<div class="mb-toolbar-placeholder">').hide().appendTo('body'),
        $toolbarContent = $toolbar.find('[data-role=mb-toolbar-content]').first(), eventType = (typeof window.orientation == 'undefined') ? 'click' : 'touchend';
        $toolbar.find('[data-action]').on(eventType, function(e) {
            e.preventDefault();
            var $btn = $(this);
            var action = $btn.data('action');
            if (action.display) {
                if (!$toolbar.hasClass('content-opened')) {
                    $toolbar.addClass('content-opened');
                    if (action.display.element) {
                        if ($(action.display.element).length) {
                            $currentDisplay = $(action.display.element).first();
                            $currentPlaceholder.insertBefore($currentDisplay);
                            $currentDisplay.appendTo($toolbarContent);
                        }
                    }
                } else {
                    $toolbar.removeClass('content-opened')
                    if ($currentDisplay) {
                        $currentDisplay.insertAfter($currentPlaceholder);
                        $currentDisplay = false;
                    }
                }
            }
            if (action.trigger) {
                $(action.trigger.target).trigger(action.trigger.event);
            }
        });
        $toolbar.on('click', '[data-role=close-content]', () => {
            $toolbar.removeClass('content-opened');
            if ($currentDisplay) {
                $currentDisplay.insertAfter($currentPlaceholder);
                $currentDisplay = false;
            }
        });
    };
    thc.makeSameHeight = function($context) {
        if (typeof $context == 'undefined') $context = $body;
        $('[data-sameheight]', $context).each(function() {
            var $el = $(this);
            if ($el.is(':visible')) {
                var sameHeightArray = $el.data('sameheight').split(',');
                $.each(sameHeightArray, function(i, sameHeight) {
                    var maxHeight = 0;
                    $el.find(sameHeight).css({minHeight: ''}).each(function() {
                        var $sItem = $(this), height = $sItem.outerHeight();
                        if (height > maxHeight) {
                            maxHeight = height;
                        }
                    }).css({minHeight: maxHeight});
                });
            }
        });
    };
    thc.sectionMenu = function() {
        if ($('[data-secmenuitem]').length) {
            var processing = false, topSpace = 100, $wrap = $('<div class="section-menu-wrap hidden-xs">').appendTo('body'), $menu = $('<div class="section-menu">').appendTo($wrap), sections = [];
            $('[data-secmenuitem]').each(function() {
                var $section = $(this), $menuItem = $('<div class="menu-item">'), data = $section.data('secmenuitem'), icon = data.icon, title = data.title;
                $menuItem.html('<i class="' + icon + '"></i>');
                if (title) $menuItem.append('<div class="item-label"><span>' + title + '</span></div>');
                $menuItem.appendTo($menu).on('click', function() {
                    if (!processing) {
                        var secTop = $section.offset().top - topSpace;
                        $menuItem.addClass('active').siblings().removeClass('active');
                        processing = true;
                        $('html, body').animate({scrollTop: secTop}, 300, 'linear', function() {
                            setTimeout(function() {
                                processing = false;
                            },100);
                        });
                    }
                });
                $section.removeAttr('data-secmenuitem');
                sections.push({
                    menuItem: $menuItem,
                    section: $section
                });
            });
            $('<div class="menu-item go-top"><i class="sec-icon fa fa-arrow-circle-up"></i></div>')
                .append('<div class="item-label"><span>Back to Top</span></div>')
                .prependTo($menu).on('click', function() {
                    $('html, body').animate({scrollTop: 0});
                });
            if ($win.scrollTop() > window.innerHeight - topSpace) {
                $wrap.addClass('open');
            } else {
                $wrap.removeClass('open');
            }
            $win.on('scroll', function() {
                if (thc.isDtScreen() && !processing) {
                    $.each(sections, function(id, item) {                        
                        var elTop = item.section.offset().top - topSpace,
                        elBot = elTop + item.section.outerHeight(),
                        winTop = $win.scrollTop(),
                        winBot = winTop + window.innerHeight;
                        if (winTop > window.innerHeight - topSpace) {
                            $wrap.addClass('open');
                        } else {
                            $wrap.removeClass('open');
                        }
                        var cond1 = (elTop <= winTop) && (elBot >= winTop),
                        cond2 = (elTop >= winTop) && (elTop <= winBot),
                        cond3 = (elTop >= winTop) && (elBot <= winBot),
                        cond4 = (elTop <= winTop) && (elBot >= winBot);
                        if (cond1 || cond2 || cond3 || cond4) {
                            item.menuItem.addClass('active').siblings().removeClass('active');
                            return false;
                        }
                    });
                }
            });
        }
    }
    thc.checkVisible = function($element){
        var cond1 = ($element.get(0).offsetWidth > 0) && ($element.get(0).offsetHeight > 0), cond2 = ($element.is(':visible')), winTop = $win.scrollTop(),
        winBot = winTop + window.innerHeight,
        elTop = $element.offset().top,
        elHeight = $element.outerHeight(true),
        elBot = elTop + elHeight,
        cond3 = (elTop <= winTop) && (elBot >= winTop),
        cond4 = (elTop >= winTop) && (elTop <= winBot),
        cond5 = (elTop >= winTop) && (elBot <= winBot),
        cond6 = (elTop <= winTop) && (elBot >= winBot),
        cond7 = true;
        if ($element.parents('md-tab-content').length > 0) {
            cond7 = $element.parents('md-tab-content').first().hasClass('md-active');
        }
        return cond1 && cond2 && (cond3 || cond4 || cond5 || cond6) && cond7;
    };
    thc.toggleContent = function() {
        var self = this;
        $('[data-cdz-toggle]').each(function() {            
            var $link = $(this).addClass('link-toggle'),
            $content = $($link.data('cdz-toggle'));
            if ($content.length) {
                $content.attr('data-role', 'cdz-toggle-content');
                $link.removeAttr('data-cdz-toggle').on('click', function() {
                    if (self.isMbScreen()) {
                        $content.toggleClass('active');
                        if ($content.hasClass('active')) {
                            $link.addClass('active');
                        } else {
                            $link.removeClass('active');
                        }
                        $content.slideToggle(300);
                    }
                });
                $win.on(deskEvent, function() {
                    $link.removeClass('active');
                });
            }
        });
        $('[data-role=cdz-toggle-content]').each(function() {
            var $content = $(this);
            if (self.isMbScreen()) $content.hide();
            $win.on(deskEvent, function() {
                $content.css({display: ''}).removeClass('active');
            }).on(mobiEvent, function() {
                $content.css({display: 'none'}).removeClass('active');
            });
            $content.removeAttr('data-role');
        });
    };
    
    thc.mobiProductViewTabs = function() {
        if ($body.hasClass('catalog-product-view')) {
            $body.on('click', '.product.info.detailed a.data.switch', function() {
                if (window.innerWidth < mBreakpoint) {
                    var $tab = $(this);
                    setTimeout(function() {
                        if ($tab.offset().top < window.scrollY) $('html, body').animate({'scrollTop': ($tab.offset().top - 100)}, 300);
                    }, 150);
                }
            });
        }
    };
    thc.verticalMenu = function () {
        if (window.codazon.alignVerMenuHeight) {
            function alignMenu($menu) {
                var $menuwrap = $menu.parents('[data-role=menu-content]').first();
                if ($menuwrap.length && $menuwrap.parents('.column.main').length) {
                    var $slideshow = $('[data-role="cdz-slideshow"]').first(), height, t = false, eventName = winWidthChangedEvent + '.vmenu lazyLoadInitialized.vmenu', cont = $menuwrap.data('align_container'), $cont = $(cont);
                    $menu.addClass('aligned');
                    if (!$cont.length) $cont = $slideshow.parent();
                    if ($cont.length) {
                        var calcHeight = () => {
                            $menuwrap.removeClass('fixed-height-menu');
                            $menu.css('position', 'absolute');
                            height = $cont.outerHeight(false);
                            $menu.css('position', '');
                            var menuHeight = $menu.height();
                            if (height < menuHeight) $menuwrap.addClass('fixed-height-menu').css({height: height});
                        }
                        calcHeight();
                        $win.off(eventName).on(eventName, function(e) {
                            if (t) clearTimeout(t);
                            $menuwrap.removeClass('fixed-height-menu').css({height: ''});
                            t = setTimeout(calcHeight, 500);
                        });
                    }
                }
            }
            $('.column.main [data-role=menu-content] .cdz-menu').each(function() {
                alignMenu($(this));
            });
            $body.on('cdzmenu.initialized', (e, $menu) => { alignMenu($menu); });
        }
    };
    thc.qtyControl = function() {
        $body.off('click.cdzQtyControl').on('click.cdzQtyControl','[data-role=change_cart_qty]', function (e) {
            var $btn = $(this);
            if ($btn.data('role') != 'change_cart_qty') $btn = $btn.parents('[data-role=change_cart_qty]').first();
            var qty = $btn.data('qty'), $pr = $btn.parents('.cart-qty').first(),
            $qtyInput = $('input.qty',$pr), min = $qtyInput.attr('min'),
            curQty = $qtyInput.val()?parseInt($qtyInput.val()):0;
            curQty += qty; min = (min == undefined) ? 1 : parseFloat(min);
            (curQty < min) ? (curQty = min) : null;
            $qtyInput.val(curQty).attr('value', curQty).trigger('change');
        });
    }
    thc.addNumControl = function() {
        $(codazon.numCtrlSeletor).each(function() {
            var $ip = $(this);
            if (!$ip.parents('[role="numctrl"]').first().length) {
                var step = parseFloat($ip.attr('step')), $pr = $('<div class="cdz-qty-box cart-qty" role="numctrl">').insertBefore($ip);
                step = isNaN(step) ? 1 : step;
                $ip.addClass('qty').appendTo($pr);
                $('<div class="m-btn m-minus" data-role="change_cart_qty" data-qty="-'+step+'">').prependTo($pr);
                $('<div class="m-btn m-plus" data-role="change_cart_qty" data-qty="'+step+'">').appendTo($pr);
            }
        });
    };
    thc.toggleMobileMenu = function() {
        $('[data-role=menu-title]').each(function() {
            var $title = $(this),
            $menu = $title.parent().find('[data-role=menu-content]').removeClass('hidden-xs'),
            onMobile = function() {
                $menu.hide();
            }, onDesktop = function() {
                $menu.css({display: ''});
            }, toggle = function() {
                (window.innerWidth < mBreakpoint) ? onMobile() : onDesktop();
            }
            $title.on('click', function() {
                if (window.innerWidth < mBreakpoint) $menu.slideToggle(200);
            });
            $win.on(mobiEvent, onMobile).on(deskEvent, onDesktop);
            toggle();
        });
    }
    thc.init();
});