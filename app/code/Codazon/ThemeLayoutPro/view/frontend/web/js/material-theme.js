window.angularCompileElement = () => {}
require(['jquery'], function($) {
    "use strict";
    if (typeof window.cdzUtilities == 'undefined') {
        window.cdzUtilities = {};
    }
    var deskPrefix = 'desk_', mobiPrefix = 'mobi_', deskEvent = 'cdz_desktop', mobiEvent = 'cdz_mobile',
	$window = $(window), rtl = $('body').hasClass('rtl-layout'), checkedSide = rtl ? 'left' : 'right',
	mBreakpoint = 768,
	winWidthChangedEvent = 'cdz_window_width_changed',
	$body = $('body');
    cdzUtilities.writeCssVars = function() {
        let $root = $('#css-cdz-vars');
        if ($root.length == 0) {
            $root = $('<style id="css-cdz-vars">').prependTo($body);
        }
        let $cont = $('<div class="container">').css('opacity', 0).appendTo('body'),
        css = '--scrollbar-width: ' + this.scrollBarWidth + "px;\n";
        css += '--cur-cont-pd:' + $cont.css('padding-left') + ';--cur-cont-width: ' + $cont.width() + "px;\n";
        css += '--cur-cont-outer-width: ' + $cont.outerWidth() + "px;\n";
        css = 'body {' + css + '}';
        $cont.remove();
        $root.html(css);
    }
    cdzUtilities.uniqId = function () {
        return Math.random().toString().substr(2,6);
    }
    cdzUtilities.getScrollBarWidth = function() {
        var $outer = $('<div>').css({visibility: 'hidden', width: 100, overflow: 'scroll'}).appendTo('body'),
            widthWithScroll = $('<div>').css({width: '100%'}).appendTo($outer).outerWidth();
        $outer.remove();
        return 100 - widthWithScroll;
    };   
    cdzUtilities.sidebar = function() {
        var $backface = $('#cdz-sidebar-backface');
        if ($backface.length == 0) {
            $backface = $('<div data-role="cdz-close-sidebar" id="cdz-sidebar-backface" class="cdz-sidebar-backface" >');
            $backface.appendTo('body');
        }
        var side, $sidebar, section, openedEvent, interval;
        function closeSidebar() {
            $('html').removeClass('cdz-panel-open-left cdz-panel-open-right');
            $('html').addClass('cdz-panel-close-' + side);
            openedEvent = false;
            if (interval) clearInterval(interval);
            setTimeout(() => {
                $sidebar.css('top', '');
                $('html').removeClass('cdz-panel-close-' + side);
                $('#' + section).hide();
                $body.css({paddingLeft: '', paddingRight: ''});
            }, 200);
        }
        function openSidebar() {
            $sidebar.css('top', $(window).scrollTop());
            if (interval) clearInterval(interval);
            interval = setInterval(() => {
                $sidebar.css('top', $(window).scrollTop());
            }, 100);
            $('html').removeClass('cdz-panel-open-left cdz-panel-open-right')
                    .addClass('cdz-panel-open-' + side);
            $('#' + section).show().siblings().hide();
            (side == checkedSide)?$body.css({paddingLeft: cdzUtilities.scrollBarWidth}):$body.css({paddingRight: cdzUtilities.scrollBarWidth});
            setTimeout(() => {
                if (openedEvent) $('#' + section).trigger(openedEvent);
            },300);
        }
        
        $('body').on('click', '[data-sidebartrigger]', function(e) {
            e.preventDefault();
            var $trigger = $(this);
            var data = $trigger.data('sidebartrigger');
            section = data.section ? data.section : 'utilities-main';
            side = data.side ? data.side : 'right';
            $sidebar = $('[data-sidebarid=' + side + ']').first();
            openedEvent = data.event;
            if ($('html').hasClass('cdz-panel-open-' + side)) {
                closeSidebar();
            } else {
                openSidebar();
            }
            $sidebar.find('[data-action=close]').off('click').on('click', closeSidebar);
        });
        $('body').on('click touchend', '[data-role=cdz-close-sidebar]', function(e) {
            setTimeout(closeSidebar, 50);
        });
        var _buildSidebar = function() {
            $('[data-sidebarsection]').each(function() {
                var $content = $(this), data = $content.data('sidebarsection'), side = data.side ? data.side : 'right',
                id = data.id ? data.id : 'utilities-' + cdzUtilities.uniqId(),
                $sectionsWrap = $('[data-sidebarid=' + side + ']').first().find('.utilies-sections').first(),
                $section = $('<div class="utilies-section nice-scroll">').addClass(id).attr('id', id).appendTo($sectionsWrap).hide();
                $content.appendTo($section).removeAttr('data-sidebarsection');
            });
        }
        _buildSidebar();
        $('body').on('cdzBuildSidebar', _buildSidebar);
    };
    
    cdzUtilities.dropdown = function() {
        var $container = false, $trigger, $dropdown, activeClass = 'cdz-dd-active', active = '.' + activeClass;
        $('body').on('click', '[data-role=cdz-dd-trigger]', function(e) {
            e.preventDefault();
            $trigger = $(this);
            $container = $trigger.parents('[data-role=cdz-dropdown]').first();
            $dropdown = $container.find('[data-role=cdz-dd-content]');
            
            if ($container.hasClass(activeClass)) {
                $container.removeClass(activeClass);
                $container = false;
            } else {
                $(active).removeClass(activeClass);
                $container.addClass(activeClass);
                var ddRight = $container.offset().left + $dropdown.outerWidth() + 20;
                var delta = 0;
                if (ddRight > window.innerWidth) {
                    delta = ddRight - window.innerWidth;
                }
                $dropdown.css({left: -delta});
                setTimeout(() => {
                    $dropdown.trigger('dropdowndialogopen');
                }, 300);
                
            }
        });
        $('body').on('click', function(e) {
            if ($container) {
                var $target = $(e.target), cond1 = $target.is($container), cond2 = ($container.has($target).length > 0);
                if (!(cond1 || cond2)) {
                    $container.removeClass(activeClass);
                    $dropdown.css({left: ''});
                }
            }
        });
    };
    
    cdzUtilities.popup = function() {
        var $popupContainer, $ppContainerInner, $openedPopup, $backface;
        function _prepare() {
            $popupContainer = $('#cdz-popup-area');
            if ($popupContainer.length == 0) {
                $popupContainer = $('<div class="cdz-popup-area" id="cdz-popup-area">').appendTo('body');
                $ppContainerInner = $('<div class="cdz-popup-area-inner" >').appendTo($popupContainer);
                $backface = $('<div class="cdz-backface" data-role="close-cdzpopup">').appendTo($ppContainerInner);
            }
        }
        function _buildPopup() {
            $('[data-cdzpopup]').each(function() {
                var $popup = $(this),
                $wrap = $('<div class="cdz-popup">').appendTo($ppContainerInner).addClass('popup-' + $popup.attr('id')),
                $inner = $('<div class="cdz-popup-inner">').appendTo($wrap),
                $content = $('<div class="cdz-popup-content">').appendTo($inner),
                $closeBtn = $('<button type="button" class="close-cdzpopup" data-role="close-cdzpopup"><span></span></button>').appendTo($wrap);
                $popup.removeAttr('data-cdzpopup');
                $popup.appendTo($content);
                if (!$popup.hasClass('no-nice-scroll'))
                    $content.addClass('nice-scroll');
                if ($popup.hasClass('hidden-overflow'))
                    $content.css({overflow: 'hidden'});
                if ($popup.data('parentclass'))
                    $wrap.addClass($popup.data('parentclass'));
                $popup.on('triggerPopup', () => {
                    cdzUtilities.triggerPopup($popup.attr('id'));
                });
            });
        }
        this.triggerPopup = function(popupId, $trigger) {
            var $popup = $('#' + popupId);
            if ($popup.length) {
                if ($popup.parents('.cdz-popup').length) {
                    $popup.parents('.cdz-popup').first().addClass('opened').siblings().removeClass('opened');
                    $('body').css({overflow: 'hidden'});
                    $('.js-sticky-menu.active').css({
                        right: 'auto',
                        width: 'calc(100% - ' + cdzUtilities.scrollBarWidth +'px)'
                    });
                    $('body').addClass('cdz-popup-opened');
                    setTimeout(() => {
                        $popup.trigger('cdz_popup_opened');
                        if ($trigger) {
                            if (typeof $trigger.data('event') === 'string') {
                                $popup.trigger($trigger.data('event'));
                            }
                        }
                    }, 300);
                }
            }
        }
        function _bindEvents() {
            $('body').on('click', '[data-cdzpopuptrigger]', function(e) {
                e.preventDefault();
                var $trigger = $(this);
                var popupId = $trigger.data('cdzpopuptrigger');
                cdzUtilities.triggerPopup(popupId, $trigger);
            });
            function closePopup() {
                $('.cdz-popup.opened').removeClass('opened');
                $('body').removeClass('cdz-popup-opened');
                $('body').css({overflow: ''});
                $('.js-sticky-menu').css({right: '', width: ''});
            }
            function modifyButton($button, it) {
                $button.attr('id', 'btn-minicart-close-popup');
                if (!$button.data('popup_bind_event')) {
                    $button.data('popup_bind_event', true);
                    $button.on('click', closePopup);
                    $popupContainer.find('#top-cart-btn-checkout').on('click', closePopup);
                    if (it) clearInterval(it);
                }
            }
            if ($popupContainer.find('div.block.block-minicart').length) {
                var it = setInterval(function() {
                    var $button = $popupContainer.find('#btn-minicart-close');
                    if ($button.length) {
                        modifyButton($button, it);
                    }
                }, 2000);
                require(['Magento_Customer/js/customer-data'], function(customerData) {
                    var cartData = customerData.get('cart');
                    cartData.subscribe(function (updatedCart) {
                        var $button = $popupContainer.find('#btn-minicart-close');
                        if ($button.length) {
                            setTimeout(() => {
                                modifyButton($button, false);
                            }, 1000);
                        }
                    });
                });
            }
            $popupContainer.on('click', '[data-role=close-cdzpopup]', closePopup);
        }
        
        _prepare();
        _buildPopup();
        _bindEvents();
        $('body').on('cdzBuildPopup', _buildPopup);
        
    };
    function replaceTag($obj, newTag) {
        var $newTag = $('<' + newTag + '>').insertAfter($obj);
        if($obj.children().length) {
            $obj.children().appendTo($newTag);
        } else if($obj.html()) {
            $newTag.html($obj.html());
        }
        $.each($obj.get(0).attributes, function(id, el) {
            $newTag.attr(el.name, el.value);
        });
        $obj.remove();
        return $newTag;
    }
    cdzUtilities.builDynamicTabs = function() {
        var deskEvent = 'cdz_desktop', mobiEvent = 'cdz_mobile';
        $window = $(window);
        $('[data-role=tabs-dynamic-control]').each(function(){
            var $tabs = $(this), $container = $tabs.parents('.js-tab-dc-container').first();
            if ($container.length == 0)
                $container = $tabs.parents('[data-role=js-tab-dc-container]');
            var $linkPlaceholder = $container.find('.tab-links-placeholder');
            if ($linkPlaceholder.length == 0)
                $linkPlaceholder = $container.find('[data-role=tab-links-placeholder]');
            var $ul = $('<ul class="box-cate-link hidden-xs abs-dropdown">').appendTo($linkPlaceholder.empty()).unwrap(),
            $mbTitle = $('<a href="javascript:void(0)" class="mobile-toggle visible-xs">').insertBefore($ul);
            $('.tab-item', $tabs).each(function(id, el) {
                var $tabItem = $(this), external = false, href = 'javascript:void(0)';
                if ($tabItem.find('.tab-title').data('externalurl')) {
                    href = $tabItem.find('.tab-title').data('externalurl');
                    external = true;
                }
                var $tabTitle = replaceTag($tabItem.find('.tab-title'), 'a').attr('href', href).removeAttr('data-externalurl'),
                $li = $('<li class="item">').addClass($tabTitle.data('class')).append($tabTitle.removeAttr('data-class')).appendTo($ul);
                if (id == 0) {
                    $li.addClass('active');
                    $tabItem.addClass('active');
                    $mbTitle.text($tabTitle.text());
                }
                $li.on('click', () => {
                    if (!external) {
                        $tabItem.addClass('active').siblings().removeClass('active');
                        $li.addClass('active').siblings().removeClass('active');
                        $mbTitle.text($tabTitle.text());
                        if (window.innerWidth < 768) {
                            $ul.slideUp(300);
                            $mbTitle.removeClass('open');
                        }
                        $('body').trigger('cdzTabsOpened', [$tabItem, $li]);
                    }
                });
            });
            $ul.removeClass('hidden-xs');
            function toggleUL() {
                if (window.innerWidth < 768) {
                    $ul.hide();
                } else {
                    $ul.css('display', '');
                }
            }
            toggleUL();
            $window.on(deskEvent, toggleUL).on(mobiEvent, toggleUL);
            $('body').on('click', function(e) {
                if ($mbTitle.hasClass('open')) {
                var $target = $(e.target);
                    var cond1 = $target.is($mbTitle),
                    cond2 = $mbTitle.find($target).length,
                    cond3 = $target.is($ul),
                    cond4 = $ul.find($target).length;
                    if (!(cond1 || cond2 || cond3 || cond4)) {
                        $ul.slideUp(300);
                        $mbTitle.removeClass('open');
                    }
                }
            });
            $mbTitle.on('click', function() {
                $ul.slideToggle(300);
                $mbTitle.toggleClass('open');
            });
            $tabs.removeClass('hidden');
        });
    };
    cdzUtilities.customSelect = function() {
        $('.js-cdz-select').each(function() {
            var $sl = $(this);
            if (!$sl.attr('multiple')) {
                var $wrap = $('<span class="cdz-select-wrap">').insertBefore($sl), $label = $('<span class="mk">').appendTo($wrap);
                $sl.prependTo($wrap).removeClass('js-cdz-select');
                var addText = () => {
                    $label.text($sl.find('option:selected').text());
                };
                addText();
                $sl.on('change', addText);
            }
        });
    };
    cdzUtilities.init = function() {
		this.scrollBarWidth = cdzUtilities.getScrollBarWidth();
		this.writeCssVars();
        var winwidth = window.innerWidth;
        $(window).on('resize', () => {
            if (winwidth != window.innerWidth) {
                this.scrollBarWidth = this.getScrollBarWidth();
				this.writeCssVars();
                winwidth = window.innerWidth;
            }
        });
        this.sidebar();
        this.dropdown();
        this.popup();
        this.builDynamicTabs();
        this.customSelect();
    };
    if (document.readyState == 'complete') {
        cdzUtilities.init();
    } else {
        $(document).ready(() => { cdzUtilities.init(); });
    }
});