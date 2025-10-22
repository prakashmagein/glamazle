/**
 * Copyright © 2021 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */ 
define(['jquery', 'jquery-ui-modules/widget', 'owlslider', 'themecore'], function($) {
    var deskPrefix = 'desk_', mobiPrefix = 'mobi_', deskEvent = 'cdz_desktop', mobiEvent = 'cdz_mobile', win = window, $win = $(win), $body = $('body'),
    rtl = $body.hasClass('rtl-layout'), mBreakpoint = 768, winWidthChangedEvent = 'cdz_win_width_changed', nowTimeLoading = false, nowTimeLoaded = false,
    onNowTimeLoaded = (func) => {
        if (!nowTimeLoading) {
            nowTimeLoading = true;
            $.ajax({
                url: codazon.dateTimeUrl,
                type: 'get',
                success: (rs) => {
                    if (typeof rs.now != 'undefined') codazon.now = rs.now;
                    codazon.localNow = (new Date()).getTime();
                    nowTimeLoaded = true;
                    updateTimestamp();
                    $win.trigger('nowTimeLoaded');
                }
            });
        }
        if (nowTimeLoaded) {
            updateTimestamp(); func();
        } else {
            $win.on('nowTimeLoaded', func);
        }
    }, itemEffect = ($parent, delayUnit) => {
        $('.cdz-transparent', $parent).each((i, el) => {
            var $item = $(el);
            setTimeout(() => {
                $item.removeClass('cdz-transparent').addClass('cdz-translator');
                setTimeout(() => {
                    $item.removeClass('cdz-translator');
                }, 1000);
            }, delayUnit*i);
        });
    }, formatDate = (str) => {
        return str.replaceAll('-', '/');
    }, updateTimestamp = () => {
        codazon.curTimestamp = (new Date(formatDate(codazon.now))).getTime() + ((new Date()).getTime() - codazon.localNow);
    }, getCustomStyleElement = () => {
        var $css = $('#cdz-widget-css-script');
        if (!$css.length) $css = $('<style id="cdz-widget-css-script">').appendTo('body');
        return $css;
    }
    $.widget('codazon.buyNow', {
        _create: function() {
            var self = this, $form = self.element.parents('form').first();
            this.element.on('click', (e) => {
                $form.one('addToCartBegin', () => {
                    $form.attr('buy_now', 1);
                }).one('addToCartCompleted', () => {
                    $form.removeAttr('buy_now');
                    win.location = codazon.checkoutUrl;
                });
            });
        }
    });
    $.widget('codazon.autowidth', {
        options: {
            item: '[data-role=item]',
            itemsPerRow: [],
            margin: 0,
            marginBottom: false,
            sameHeight: [],
        },
        _sameHeight: function() {
            var conf = this.options, maxHeight = 0;
            this.element.attr('data-sameheight', conf.sameHeight.join(','));
            $.each(conf.sameHeight, (i, sameHeight) => {
                this.element.find(sameHeight).css({minHeight: ''}).each(function() {
                    var height = $(this).outerHeight();
                    if (height > maxHeight) maxHeight = height;
                }).css({minHeight: maxHeight});
            });
        },
        _create: function() {
            var conf = this.options, i = 0;
            if (!conf.itemsPerRow) return true;
            this.itemsPerRow = [];
            for (var point in conf.itemsPerRow) {
                this.itemsPerRow[i] = {};
                this.itemsPerRow[i]['breakPoint'] = point;
                this.itemsPerRow[i]['items'] = conf.itemsPerRow[point];
                i++;
            };
            this.gridId = Math.random().toString().substr(2, 6);
            this._addGridCSS();
            this._itemEffect();
            $body.on('contentUpdated', () => {
                var itemClass = 'cdz-grid-item-' + this.gridId;
                this.element.find(conf.item).addClass(itemClass)
                this._itemEffect();
            });
            this._sameHeight();
            this.element.parents('.no-loaded').first().removeClass('no-loaded');
        },
        _itemEffect: function() {
            itemEffect(this.element, 200);
        },
        _addGridCSS: function() {
            var id = this.gridId, parentClass = 'cdz-grid-' + id, itemClass = 'cdz-grid-item-' + id;
            this.element.find(this.options.item).addClass(itemClass).first().parent().addClass(parentClass);
            var css = this._getCSSCode(parentClass, itemClass, this.itemsPerRow);
            css = `<style type="text/css">${css}</style>`;
            $(css).insertAfter(this.element);
        },
        _getCSSCode: function(parentClass, itemClass, itemsPerRow) {
            var self = this, conf = this.options, css = '', width;
            bpLength = itemsPerRow.length;
            var marginSide = rtl ? 'margin-left' : 'margin-right';
            for(var i = bpLength - 1; i >=0; i--) {
                if (itemsPerRow[i].breakPoint < mBreakpoint) {
                    var margin = 10, subtrahend = 11;
                } else {
                    var margin = conf.margin, subtrahend = conf.margin;
                }
                var marginBottom = conf.marginBottom ? conf.marginBottom : margin;
                width = 100/itemsPerRow[i].items;
                css += '@media (min-width: ' + itemsPerRow[i].breakPoint + 'px)';
                if (typeof itemsPerRow[i + 1] != 'undefined') {
                     css += ' and (max-width: ' + (itemsPerRow[i + 1].breakPoint - 1) + 'px)';
                }
                css += '{.' + parentClass + '{' + marginSide +': -' + margin + 'px}';
                css += '.' + parentClass + ' .' + itemClass + '{width:calc(' + width + '% - ' + subtrahend + 'px);' + marginSide +':' + margin + 'px;margin-bottom:' + marginBottom + 'px}}\n';
            };
            return css;
        }
    });
    $.widget('codazon.socialSharing', {
        _create: function() {
            var self = this, conf = this.options;
            this.element.on('click', '[data-type]', function(e) {
                e.preventDefault();
                self._openPopup($(this).data('type'));
            });
        },
        _openPopup: function(type) {
            var conf = this.options, url;
            switch (type) {
                case 'facebook' : url = 'https://www.facebook.com/sharer/sharer.php?u=' + conf.url; break;
                case 'twitter' : url = 'https://twitter.com/intent/tweet?url='+ conf.url + '&text=' + conf.description; break;
                case 'linkedin' : url = 'https://www.linkedin.com/shareArticle?mini=true&url='+ conf.url + '&title=' + conf.title + '&ro=false&summary=' + conf.description; break;
                case 'pinterest' : url = 'https://www.pinterest.com/pin/create/button/?url='+ conf.url + '&media=' + conf.image + '&description=' + conf.description; break;
                case 'reddit' : url = 'https://www.reddit.com/submit?url=' + conf.url +'&title=' + conf.title; break;
                case 'whatsapp' : url = 'https://api.whatsapp.com/send/?text=' + conf.url + '&type=custom_url&app_absent=0'; break;
                case 'snapchat': url = 'https://www.snapchat.com/scan?attachmentUrl='+ conf.url;break;
            }
            if (url) win.open(url,"", 'menubar=1,resizable=1,width=700,height=600');
        }
    });
    $.widget('codazon.flexibleSlider', {
        options: {
            mbMargin: 10,
            sameHeight: ['.product-details', '.product-item-details'],
            pageNumber: false,
            divider: '/',
            pullDrag: true,
            noLoadedClass: false
        },
        _create: function() {
            var conf = this.options, slideConf = conf.sliderConfig, $el = this.element;
            this.$css = getCustomStyleElement(); this.id = 'cdz-slider-' + themecore.uniqid(); $el.addClass(this.id);
            conf.noLoop = conf.forceNoLoop ? conf.forceNoLoop : $el.hasClass('product-items') || $el.parents('.product-items').length;
            this.totalItem = $el.children().length;
            if (conf.noLoadedClass) $el.parents('.' + conf.noLoadedClass).removeClass(conf.noLoadedClass);
            slideConf.rtl = rtl;
            slideConf.lazyLoad = true;
            slideConf.pullDrag = conf.pullDrag;
            slideConf.navElement = 'div';
            slideConf.autoplayHoverPause = true;
            if (slideConf.responsive) {
                var forceNext = false, side = rtl ? 'left' : 'right', overflow = 'visible';
                $.each(slideConf.responsive, (i, rsp) => {
                    if ((slideConf.margin > conf.mbMargin) && (i < mBreakpoint)) {
                        slideConf.responsive[i] = $.extend({}, {margin: conf.mbMargin}, slideConf.responsive[i]);
                    }
                    if (conf.noLoop) {
                        var items = parseFloat(rsp.items), intItems = parseInt(items), pdr = 0;
                        if (intItems != items) {
                            slideConf.responsive[i].nav = false;
                            if (conf.noLoop) {
                                slideConf.responsive[i].items = intItems;
                                slideConf.responsive[i].loop = false;
                                pdr = ((items - intItems)*100/items) + '%';
                                forceNext = true;
                                this.$css.append('@media (min-width: '+i+'px) {.'+this.id+'{padding-'+side+': '+pdr+';overflow:hidden}.'+this.id+'>.owl-stage-outer{overflow:visible}}');
                            } else {
                                slideConf.responsive[i].loop = true;
                            }
                        } else if (forceNext && conf.noLoop) {
                            forceNext = false;
                            this.$css.append('@media (min-width: '+i+'px) {.'+this.id+'{padding-'+side+': '+pdr+';overflow:'+overflow+'}.'+this.id+'>.owl-stage-outer{overflow:hidden}}');
                        } else if (conf.noLoop) {
                            forceNext = false;
                        }
                    } else {
                        if ((slideConf.responsive[i].items%1) > 0) {
                            slideConf.responsive[i].loop = true;
                        } else {
                            slideConf.responsive[i].loop = slideConf.loop || false;
                        }
                    }
                });
            }
            slideConf.onLoadedLazy = function(e) {$(e.element).css('opacity','').removeClass('owl-lazy cdz-lazy');};
            $el.addClass('owl-carousel').owlCarousel(slideConf);
            this._sameHeight();
            this._itemEffect();
            if (conf.pageNumber) this._addPageNumber();
            if (win.innerWidth <= 1024) {
                setTimeout(() => {
                    $el.trigger('refresh.owl.carousel');
                    this._sameHeight();
                }, 100);
            }
            if (slideConf.autoplay && (!slideConf.loop)) {
                $el.on('translated.owl.carousel', (e) => {
                    var timeout = slideConf.autoplayTimeout ? slideConf.autoplayTimeout : 5000;                    
                    if ($el.find('.owl-item').last().hasClass('active')) {
                        setTimeout(() => {
                            $el.trigger('to.owl.carousel', [0, 0]);
                        }, timeout);
                    }
                });
            }
            if (!slideConf.autoplay)
                $el.on('changed.owl.carousel', (e) => {
                    $el.trigger('stop.owl.autoplay');
                });
        },
        _addPageNumber: function() {
            var conf = this.options, owlData = this.element.data('owl.carousel');
            this.$pageNumber = $('<div class="owl-page">').html('<span class="current-page"></span>'+conf.divider+'<span class="total-page"></span>').insertBefore(this.element.find('.owl-nav').first());
            var $current = this.$pageNumber.find('.current-page').text(owlData._current + 1);
            this.$pageNumber.find('.total-page').text(this.totalItem);
            this.element.on('changed.owl.carousel', () => { $current.text(owlData._current + 1); });
        },
        _sameHeight: function() {
            var conf = this.options;
            this.element.attr('data-sameheight', conf.sameHeight.join(','));
            $.each(conf.sameHeight, (i, sameHeight) => {
                var maxHeight = 0;
                this.element.find(sameHeight).css({minHeight: ''}).each(function() {
                    var $sItem = $(this), height = $sItem.outerHeight();
                    if (height > maxHeight) maxHeight = height;
                }).css({minHeight: maxHeight});
            });
        },
        _itemEffect: function() {
            itemEffect(this.element, 200);
        }
    });
    $.widget('codazon.slideshow', {
        _create: function() {
            var self = this, conf = this.options, vt = false;
            this.$items = this.element.find('[role="items"]');
            this._buildHtml();
            this.$items.addClass('owl-carousel');
            conf.sliderConfig.rtl = rtl;
            conf.sliderConfig.lazyLoad = true;
            conf.sliderConfig.navElement = 'div';
            var playVideo = (e) => {
                if (vt) clearTimeout(vt);
                vt = setTimeout(() => {
                    var $active = this.$items.find('.owl-item.active .item');
                    this.$items.find('.video-wrap').empty().off('click.playYT');
                    if ($active.find('.video-wrap:visible').length) {
                        var $video = $active.find('.video-wrap'), video = $video.data('video');
                        if (video.type === 'youtube') {
                            var frameId = themecore.uniqid('video');
                            $video.html('<div class="abs-frame-inner overlay"></div><div class="abs-frame-inner" noloaded data-videoid="' + video.id + '" id="' + frameId + '"></div><a class="abs-frame-inner front-overlay" href="'+video.link+'"></a>').addClass('hideall');
                            if (typeof win.onYouTubeIframeAPIReady == 'undefined') {
                                win.onYouTubeIframeAPIReady = function() {
                                    function loadVideo() {
                                        $('.video-wrap [noloaded]').each(function() {
                                            var $frame =  $(this), id = $frame.removeAttr('noloaded').attr('id'), videoId = $frame.data('videoid'), $wrap = $frame.parent();
                                            win[id] = new win.YT.Player(id, {
                                                videoId: videoId,
                                                playerVars: {'autoplay': 1, 'playsinline': 1, 'mute':1, 'loop':1, 'controls': 0, 'playlist': videoId, 'iv_load_policy': 3, 'showinfo' : 0, 'modestbranding' : 1, 'autohide': 1, 'enablejsapi': 1, 'origin': document.URL},
                                                events: {
                                                    'onReady': () => {
                                                        setTimeout(() => { $wrap.removeClass('hideall'); }, 1500)
                                                        win[id].playVideo();
                                                    },
                                                    'onStateChange': () => {
                                                        if (win[id].getPlayerState() != YT.PlayerState.PLAYING) {
                                                            win[id].playVideo();
                                                        }
                                                    }
                                                }
                                            });
                                        });
                                    }
                                    loadVideo();
                                    $win.on('cdzLoadYoutubeVideo', loadVideo);
                                }
                            }
                            require(['https://www.youtube.com/iframe_api'], function() { $win.trigger('cdzLoadYoutubeVideo'); });
                        } else {
                            $video.html(this._getFrameHtml(video.url, video.link));
                        }
                    }
                }, 50);
            }
            this.$items.on('initialized.owl.carousel translated.owl.carousel', playVideo).owlCarousel(conf.sliderConfig).parents('.abs-frame').first().css('background', '');
            if (conf.showThumbDots) {
                this.$items.addClass('preview-dots');
                $.each(conf.items, (i, el) => {
                    this.$items.find('.owl-dots .owl-dot:eq(' + i + ')').addClass('thumb-dot').css('background-image', 'url(' + el.smallImg + ')').append($('<div class="dot-img-tt"><div class="abs-img" style="padding-bottom: ' + conf.paddingBottom + '%"><img src="' + el.smallImg + '"></div>'+(el.title?'<div class="tt-title">' + el.title + '</div>':'')+'</div>'));
                });
            }
            if (conf.showThumbNav) {
                this.$items.addClass('preview-nav');
                var $prev = $('<div class="thumb-arrow thumb-prev">').appendTo(this.$items.find('.owl-prev')).append('<div class="thumb-tt"><div class="cdz-banner shine-effect"><img /></div><div class="tt-title"></div></div>'),
                $next = $('<div class="thumb-arrow thumb-next">').appendTo(this.$items.find('.owl-next')).append('<div class="thumb-tt"><div class="cdz-banner shine-effect"><img /></div><div class="tt-title"></div></div>'), t = false,
                attachImg = () => {
                    var $active = this.$items.find('.owl-item.active .item');
                    $prev.find('img').attr('src', $active.attr('data-thumbprev'));
                    $prev.find('.tt-title').text($active.attr('data-titleprev'));
                    $next.find('img').attr('src', $active.attr('data-thumbnext'));
                    $next.find('.tt-title').text($active.attr('data-titlenext'));
                }
                attachImg();
                this.$items.on('change.owl.carousel', () => {
                    if (t) clearTimeout(t);
                    t = setTimeout(attachImg, 0);
                });
            }
        },
        _buildHtml: function() {
            var conf = this.options, n = conf.items.length;
            $.each(conf.items, (i, el) => {
                let img = (win.matchMedia("(max-device-width: 767px)").matches) ? el.mobiImage : el.img;
                let srcAttr = ((i==0) || (!conf.lazyLoad)) ? 'src="' : 'class="owl-lazy" data-src="', $desc,
                prev = (conf.items[i-1]) ? conf.items[i-1] : conf.items[n-1], next = (conf.items[i+1]) ? conf.items[i+1] : conf.items[0],
                video = el.video ? this._getVideo(el.video) : {},
                $item = $(`<div class="item" data-titleprev="${prev.title}" data-titlenext="${next.title}" data-thumbprev="${prev.smallImg}" data-thumbnext="${next.smallImg}"><a class="item-image abs-img" style="padding-bottom: ${conf.paddingBottom}%" href="${el.link}"><img ${srcAttr}${img}" alt="${el.title}" /></a> </div>`).appendTo(this.$items);
                if ($desc = this.element.find('.item-desc-' + i)) $desc.appendTo($item);
                if (video.type) {
                    video.link = el.link;
                    let paddingBottom = el.video_ratio ? 100*parseFloat(el.video_ratio) : conf.paddingBottom;
                    $('<div class="video-wrap abs-frame">').css({paddingBottom: paddingBottom + '%'}).attr('data-video', JSON.stringify(video)).appendTo($item);
                }
            });
        },
         _getFrameHtml: function(videoUrl, link) {
            return `<div class="abs-frame-inner overlay"></div><iframe allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" frameborder="0" allowfullscreen class="abs-frame-inner" src="${videoUrl}"></iframe><a href="${link}" class="abs-frame-inner"></a>`;
        },
        _getVideo: function (url) {
            var type, id, url;
            if (url) {
                id = url.match(/(http:|https:|)\/\/(player.|www.)?(vimeo\.com|youtu(be\.com|\.be|be\.googleapis\.com))\/(video\/|embed\/|watch\?v=|v\/)?([A-Za-z0-9._%-]*)(\&\S+)?/);
                type = (id[3].indexOf('youtu') > -1) ? 'youtube' : ((id[3].indexOf('vimeo') > -1) ? 'vimeo' : ''); id = id[6];
                url = (type === 'youtube') ?  '//www.youtube.com/embed/'+id+'?autoplay=1&mute=1&enablejsapi=1&controls=0&showinfo=0&modestbranding=1&rel=0&autohide=1&color=white&iv_load_policy=3&loop=1&playlist='+id: '//player.vimeo.com/video/' + id + '?autoplay=1&loop=1&autopause=0&muted=1';
                return {type: type, id: id, url: url};
            }
            return {};
        }
    });
    
    $.widget('codazon.minicountdown', {
        options: {
            nowDate: false,
            startDate: false,
            stopDate: false,
            dayLabel: 'Day(s)',
            hourLabel: 'Hour(s)',
            minLabel: 'Minute(s)',
            secLabel: 'Second(s)',
            hideWhenExpired: true,
            delay: 1000
        },
        _create: function() {
            onNowTimeLoaded(this._initHtml.bind(this));
        },
        _initHtml: function() {
            var conf = this.options;
            if (conf.stopDate) {
                conf.stopDate = formatDate(conf.stopDate);                
                var now = codazon.curTimestamp;
                if (conf.startDate) {
                    conf.startDate = formatDate(conf.startDate);
                    this.startDate = new Date(conf.startDate).getTime();
                    if (this.startDate > now) return true;
                }
                this.delta = (new Date()).getTime() - codazon.curTimestamp;
                this.stopDate = (new Date(conf.stopDate)).getTime();
                if (this.stopDate > now) {
                    this.$wrapper = $('<div class="deal-items">').appendTo(this.element.empty()).hide();
                    this.$days = $('<div class="deal-item days"><span class="value" title="'+conf.dayLabel+'"></span> <span class="label">'+conf.dayLabel+'</span></div>').appendTo(this.$wrapper).find('.value');
                    this.$hours = $('<div class="deal-item hours"><span class="value" title="'+conf.hourLabel+'"></span> <span class="label">'+conf.hourLabel+'</span></div>').appendTo(this.$wrapper).find('.value');
                    this.$mins = $('<div class="deal-item mins"><span class="value" title="'+conf.minLabel+'"></span> <span class="label">'+conf.minLabel+'</span></div>').appendTo(this.$wrapper).find('.value');
                    this.$secs = $('<div class="deal-item secs"><span class="value" title="'+conf.secLabel+'"></span> <span class="label">'+conf.secLabel+'</span></div>').appendTo(this.$wrapper).find('.value');
                    this._countDown();
                    intervalOneSec(() => {this._countDown();});
                    this.$wrapper.fadeIn(300, 'linear', () => { this.$wrapper.css({display: ''}); });
                    $body.trigger('cdzResize');
                } else {
                    this._countDownExpired();
                }
            } else {
                this._countDownExpired();
            }
        },
        _countDown: function() {
            var now = new Date().getTime() - this.delta, distance = this.stopDate - now;
            if (distance < 0) {
                this._countDownExpired();
            } else {
                var days = Math.floor(distance / (1000 * 60 * 60 * 24)), hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
                mins = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)), secs = Math.floor((distance % (1000 * 60)) / 1000);
                this.$days.text(this._formateNum(days)); this.$hours.text(this._formateNum(hours)); this.$mins.text(this._formateNum(mins)); this.$secs.text(this._formateNum(secs));
            }
        },
        _countDownExpired: function() {
            var conf = this.options, hwe = conf.hideWhenExpired, $timerWrap = this.element.parents('[role=timer_wrap]');
            $pr = conf.parentSelector ? this.element.parents(conf.parentSelector).first() : $timerWrap;
            if (hwe && this.$wrapper) this.$wrapper.hide();
            $timerWrap.addClass('cd-expired');
            if ($pr.length) {
                if (hwe) $pr.children().hide();
                $expMsg = $pr.find('[role="expired_msg"]');
                if ($expMsg.length) $expMsg.removeClass('hidden').css('display','').appendTo($pr);
            }
            $pr.find('.deal-item .value').html('00');
        },
        _formateNum: function(num) {
            return num.toString().length < 2 ? '0' + num : num;
        }
    });
    $.widget('codazon.searchtrigger', {
        options: {
            searchContainer: '#header-search-wrap',
            toggleClass: 'search-opened'
        },
        _create: function() {
            var conf = this.options, $searchContainer = $(conf.searchContainer),
            mbSearch = () => {
                $searchContainer.removeClass(conf.toggleClass);
                this.element.removeClass(conf.toggleClass);
            }, dtSearch = () => {};
            this.element.on('click.triggersearch', (e) => {
                e.preventDefault();
                $searchContainer.toggleClass(conf.toggleClass);
                if ($searchContainer.hasClass(conf.toggleClass)) {
                    this.element.addClass(conf.toggleClass);
                } else {
                    this.element.removeClass(conf.toggleClass);
                }
            });
            $body.on('click', (e) => {
                if ($searchContainer.hasClass(conf.toggleClass)) {
                    var $target = $(e.target), cond1 = $searchContainer.is($target),
                    cond2 = ($searchContainer.find($target).length > 0),
                    cond3 = this.element.is($target),
                    cond4 = (this.element.find($target).length > 0);
                    if(!(cond1 || cond2 || cond3 || cond4)) {
                        $searchContainer.removeClass(conf.toggleClass);
                        this.element.removeClass(conf.toggleClass);
                    }
                }
            });
            $win.on(deskEvent, dtSearch).on(mobiEvent, mbSearch);
        }
    });
    $.widget('codazon.searchtoggle', {
        options: {
            toggleBtn: '[data-role=search_toggle]',
            searchForm: '[data-role=search_form]',
            toggleClass: 'input-opened',
            mbClass: 'mb-search',
            onlyMobi: true,
            hoverOnDesktop: false
        },
        _create: function () {
            var $element = this.element, conf = this.options,
            $searchForm = $(conf.searchForm, $element),
            $searchBtn = $(conf.toggleBtn, $element),
            mbSearch = () => {
                $element.addClass(conf.mbClass);
                $searchForm.removeClass('hidden-xs');
            }, dtSearch = () => {
                $element.removeClass(conf.mbClass);
                $searchForm.addClass('hidden-xs');
            };
            if (conf.onlyMobi) {
                themecore.isMbScreen() ? mbSearch() : dtSearch();
                $win.on(deskEvent, dtSearch).on(mobiEvent, mbSearch);
            } else {
                mbSearch();
                if (conf.hoverOnDesktop) {
                     $element.hover(() => {
                            if (!themecore.isMbScreen()) $element.addClass(conf.toggleClass);
                        }, () => {
                            if (!themecore.isMbScreen()) $element.removeClass(conf.toggleClass);
                        }
                    );
                }
            }
            $searchBtn.on('click', function() {
                if (conf.hoverOnDesktop) {
                    if (themecore.isMbScreen()) $element.toggleClass(conf.toggleClass);
                } else {
                    $element.toggleClass(conf.toggleClass);
                }
            });
        }
    });
    $.widget('codazon.isogrid', {
        options: {
            groupStyle: '1,2,2',
            item: '.product-item',
            useDataGrid: true,
            breakPoint: mBreakpoint,
            sameHeight: ['.product-item-details','.product-details'],
            sliderConfig: {},
            colWidth: {1: '40%', 2: '20%', 3: '20%', 4: '20%'}
        },
        _create: function() {
            var conf = this.options, t = false, ww = win.innerWidth;
            this._assignVariables();
            this._groupItems();
            this._itemEffect();
            if ((conf.groupStyle == '1,3,3,3') || (conf.groupStyle == '3,3,3,1')) {
                $win.on('adaptchange_1200', () => {
                    if (win.innerWidth > conf.breakPoint && ww >= conf.breakPoint) this._groupItems();
                    ww = win.innerWidth;
                });
            }
            $win.on('adaptchange', () => {
                ww = win.innerWidth;
                this._groupItems();
            }).on(winWidthChangedEvent, () => {
                setTimeout(() => { this._sameHeight() }, 300);
            });
        },
        _sumArray: function(array) {
            return array.reduce(function(a, b){return parseFloat(a) + parseFloat(b)});
        },
        _assignVariables: function() {
            var conf = this.options, $el = this.element;
            if(conf.useDataGrid && $el.parents('[data-grid]').length) {
                conf.groupStyle = $el.parents('[data-grid]').data('grid');
            }
            this.subGroup = conf.groupStyle.split(',');
            this.iPG = this._sumArray(this.subGroup);
            this.colPG = this.subGroup.length;
            this.totalItems = $el.children().length;
            this.totalGroup = Math.floor(this.totalItems/this.iPG);
            this.$allItems = $el.find('.product-item');
        },
        _groupItems: function() {
            (win.innerWidth < this.options.breakPoint) ? this._groupItemsOnMb() : this._groupItemsOnPC();
        },
        _itemEffect: function() {
            itemEffect(this.element, 100);
        },
        _groupItemsOnMb: function() {
            var conf = this.options, $inner = $('<div class="mb-group-inner">').appendTo(this.element);
            this.$allItems.each(function(i, el) {
                $(el).appendTo($inner);
            });
            this.element.find('[data-smallimage]').each(function() {
                var $img = $(this);
                $img.attr('src', $img.attr('data-smallimage'));
            });
            this.element.removeClass('hidden').children('.group-inner').trigger('destroy.owl.carousel').remove();
            $.codazon.flexibleSlider({sliderConfig: conf.sliderConfig, sameHeight: []}, $inner);
        },
        _groupItemsOnPC: function() {
            var conf = this.options, $el = this.element;
            this.Group = [];
            this.$allItems.each((i, el) => {
                var $item = $(el), groupId = Math.floor(i/this.iPG);
                if (typeof this.Group[groupId] === 'undefined')
                    this.Group[groupId] = [];
                this.Group[groupId].push($item);
            });
            $el.children('.group-inner').addClass('old').trigger('destroy.owl.carousel');
            var $inner = $('<div class="group-inner">').appendTo($el);
            if ((win.innerWidth < 1200) && ((conf.groupStyle == '1,3,3,3') || (conf.groupStyle == '3,3,3,1'))) {
                var subGroup = [1,2,2,2,2,2];
            } else {
                var subGroup = this.subGroup;
            }
            $el.removeClass('hidden');
            $.each(this.Group, function(i, group) {
                var $group = $('<div class="item-group flex-grid">').appendTo($inner), itemIndex = 0;
                $.each(subGroup, function(ii, iPC) {
                    if (iPC == 1) {
                        var width = (typeof conf.colWidth[iPC] != 'undefined')?(conf.colWidth[iPC]):'50%',
                        colClass = 'large-col';
                    } else {
                        var width = (typeof conf.colWidth[iPC] != 'undefined')?(conf.colWidth[iPC]):'25%',
                        colClass = 'small-col';
                    }
                    var $col = $('<div class="group-col">').appendTo($group).css({width: width}).addClass(colClass);
                    for(var j=0; j < iPC; j++) {
                        if (typeof group[itemIndex] != 'undefined') {
                            group[itemIndex].find('[data-smallimage]').each(function() {
                                var $img = $(this), $gallery = group[itemIndex].find('[data-gallery]');
                                if (iPC == 1) {
                                    if ($gallery.length) {
                                        $gallery.hide();
                                        $.codazon.horizontalThumbsSlider($gallery.data('gallery'), $gallery);
                                        $gallery.removeAttr('data-gallery');
                                    }
                                    $img.attr('src', $img.attr('data-largeimg'));
                                } else {
                                    if ($gallery.length) $gallery.remove();
                                    $img.attr('src', $img.attr('data-smallimage'));
                                }
                            });
                            group[itemIndex].appendTo($col);
                            itemIndex++;
                        }
                    }
                });
                if ((conf.groupStyle == '1,3,3,3') || (conf.groupStyle == '3,3,3,1')) {
                    var groupColWidth = 100 - parseFloat(conf.colWidth[1]),
                    $mergedSubGroup = $('<div class="merged-sub-group">').css('width', groupColWidth + '%');
                    if ((conf.groupStyle == '1,3,3,3')) {
                        $mergedSubGroup.appendTo($group);
                    } else {
                        $mergedSubGroup.prependTo($group);
                    }
                    $group.find('.small-col').css('width', '').appendTo($mergedSubGroup);
                    $.codazon.flexibleSlider({sliderConfig: {
                        margin: 0, dots: false, nav: false,
                        responsive: {
                            768: {items: 2, nav: true}, 1200: {items: 3, pullDrag: false}
                        }},
                        sameHeight: []
                    }, $mergedSubGroup);
                }
            });
            $el.children('.group-inner.old').remove();
            if (this.Group.length > 1) {
                $.codazon.flexibleSlider({sliderConfig: conf.sliderConfig, sameHeight: []}, $inner.addClass('owl-carousel cdz-grid-slider'));
            }
            this._sameHeight();
            $el.find('.img-gallery').css({display: ''});
            $el.find('.mb-group-inner').trigger('destroy.owl.carousel').remove();
        },
        _sameHeight: function() {
            var conf = this.options;
            if (win.innerWidth >= conf.breakPoint) {
                this.element.find('.item-group').each(function() {
                    var $group = $(this);
                    $.each(conf.sameHeight, function(i, sameHeight) {
                        var maxHeight = 0;
                        $group.find('.small-col ' + sameHeight).css({height: '', minHeight: ''}).each(function() {
                            var $sItem = $(this), height = $sItem.outerHeight();
                            if (height > maxHeight) maxHeight = height;
                        }).css({minHeight: maxHeight});
                    });
                });
            } else {
                this.element.find(conf.sameHeight).css({height: ''});
            }
        }
    });
    $.widget('codazon.horizontalThumbsSlider', {
        options: {
            parent: '.product-item',
            mainImg: '.product-image-wrapper .product-image-photo:last',
            itemCount: 4,
            activeClass: 'item-active',
            loadingClass: 'swatch-option-loading',
            moreviewSettings: {}
        },
        _create: function(){
            var conf = this.options;
            if((!conf.images) || (conf.images.length == 0)) return false;
            this.$parent = this.element.parents(conf.parent).first();
            this.$mainImg = $(conf.mainImg, this.$parent);
            this.images = conf.images;
            this.initHtml();
            this.bindHoverEvent();
            this.element.css({minHeight:''});
        },
        initHtml: function() {
            this.$slider = $(this.getHtml(this.images));
            this.$slider.appendTo(this.element);
            this.initSlider();
            this.element.css({display: ''});
        },
        initSlider: function() {
            var sliderConfig = $.extend({}, {items: 4, nav: true, dots: false, mouseDrag: false, touchDrag: false}, this.options.moreviewSettings);
            sliderConfig.responsiveRefreshRate = 200;
            $.codazon.flexibleSlider({sliderConfig: sliderConfig}, this.$slider);
        },
        bindHoverEvent: function(){
            var conf = this.options;
            $('.gitem', this.$slider).each((i, el) => {
                var $gitem = $(el), $link = $('.img-link', $gitem), $img = $('img', $link), mainSrc = $link.attr('href');
                $link.on('click', (e) => {
                    e.preventDefault();
                }).hover(() => {
                    if ($gitem.parents('.owl-carousel.media-slider').length) {
                        $gitem.addClass(conf.activeClass).parent().siblings().children().removeClass(conf.activeClass);
                    } else {
                        $gitem.addClass(conf.activeClass).siblings().removeClass(conf.activeClass);
                    }
                    if (typeof $link.data('loaded') === 'undefined') {
                        var mainImg = new Image();
                        this.$mainImg.addClass(conf.loadingClass);
                        $(mainImg).on('load', () => {
                            this.$mainImg.removeClass(conf.loadingClass);
                            this.$mainImg.attr('src', mainSrc);
                            $link.data('loaded', true);
                        });
                        mainImg.src = mainSrc;
                    } else {
                        this.$mainImg.attr('src', mainSrc);
                    }
                });
            });
        },
        getHtml: function(images) {
            var html = '<div class="gitems media-slider">';
            $.each(images, (id,img) => {
                html += `<div class="gitem"><a class="img-link" href="${img.large}"><img class="img-responsive" src="${img.small}" /></a></div>`;
            });
            html += '</div>';
            return html;
        }
    });
    $.widget('codazon.stickyMenu', {
        options: {
            threshold: 300,
            enableSticky: codazon.enableStikyMenu,
            anchor: null,
        },
        _create: function () {
            var conf = this.options, tt = false, t = false, w = $win.prop('innerWidth'), $el = this.element;
            if (!conf.enableSticky) return false;
            var $parent = $el.parent(), $anchor = conf.anchor ? $(conf.anchor) : $parent, parentHeight = $anchor.outerHeight(), isHeader = $el.hasClass('js-sticky-menu'),
            headerStateChanged = function() {
                if (isHeader) $win.trigger('changeHeaderState');
                $el.trigger('changeStickyState');
            };
            if (isHeader) $body.addClass('has-hd-sticky');
            $parent.css({minHeight: parentHeight});
            var threshold = (win.innerWidth < mBreakpoint) ? $anchor.offset().top + parentHeight : conf.threshold, stop = false, stickyNow = currentState = false;
            this.changeThreshold = (ths) => {threshold = ths;};
            $win.on('resize', () => {
                stop = false;
                if (t) clearTimeout(t);
                t = setTimeout(() => {
                    var newWidth = $win.prop('innerWidth');
                    if (w != newWidth) {
                        $el.removeClass('active'); stop = true;
                        $parent.css({minHeight:''});
                        w = newWidth;
                        headerStateChanged();
                        if (tt) clearTimeout(tt);
                        tt = setTimeout(() => {
                            parentHeight = $anchor.outerHeight();
                            $parent.css({minHeight: parentHeight});
                            threshold = (win.innerWidth < mBreakpoint) ? $anchor.offset().top + parentHeight : conf.threshold;
                            stickyNow = currentState = $win.scrollTop() > threshold;
                            if (currentState) {
                                $el.addClass('active');
                                headerStateChanged();
                            }
                        }, 100);
                    }
                }, 50);
            });
            setTimeout(() => {
                $parent.css({minHeight:''});
                $parent.css({minHeight:$parent.height()});
                $win.on('scroll', () => {
                    currentState = $win.scrollTop() > threshold;
                    currentState ? $el.addClass('active') : $el.removeClass('active');
                    if (currentState != stickyNow) {
                        headerStateChanged();
                        stickyNow = currentState;
                    }
                });
            }, 300);
        }
    });
    $.widget('codazon.fullsearchbox', {
        _create: function() {
            var $catSearch = $($('#search-by-category-tmpl').html());
            if ($catSearch.length) {
                $.codazon.categorySearch($catSearch.data('search'), $catSearch.appendTo(this.element.addClass('has-cat-search').find('form')));
            } else {
                this.element.addClass('no-cat-search');
            }
        }
    });
    $.widget('codazon.categorySearch', {
        options: {
            trigger: '[data-role="trigger"]',
            dropdown: '[data-role="dropdown"]',
            catList: '[data-role="category-list"]',
            activeClass: 'open',
            currentCat: false,
            allCatText: 'All Categories',
            ajaxUrl: false
        },
        _create: function() {
            this._assignVariables();
            this._assignEvents();
        },
        _assignVariables: function() {
            var conf = this.options, $el = this.element;
            this.$trigger = $el.find(conf.trigger);
            this.$triggerLabel = this.$trigger.children('span');
            this.$dropdown = $el.find(conf.dropdown);
            this.$catList = $el.find(conf.catList);
            this.$searchForm = $el.parents('form').first().addClass('has-cat');
            this.$catInput = this.$searchForm.find('[name=cat]');
            this.$qInput = this.$searchForm.find('[name=q]');
            if (this.$catInput.length == 0) {
                this.$catInput = $('<input type="hidden" id="search-cat-input" name="cat">').appendTo(this.$searchForm);
            }
            if (conf.currentCat) {
                this.$catInput.val(conf.currentCat);
                var catText = this.$catList.find('[data-id="' + conf.currentCat + '"]').text();
                this.$triggerLabel.text(catText);
            } else {
                this.$catInput.attr('disabled', 'disabled');
            }
            $el.insertBefore(this.$searchForm);
        },
        _assignEvents: function() {
            var self = this, conf = this.options, $el = this.element;
            $body.on('click', '#suggest > li:first > a, .searchsuite-autocomplete .see-all', (e) => {
                e.preventDefault();
                this.$searchForm.submit();
            });
            this.$trigger.on('click', function() {
                $el.toggleClass(conf.activeClass);
            });
            this.$catList.find('a').on('click', function(e) {
                e.preventDefault();
                var $cat = $(this), id = $cat.data('id'), label = $cat.text();
                if (id) {
                    self.$catInput.removeAttr('disabled').val(id).trigger('change');
                    self.$triggerLabel.text(label);
                } else {
                    self.$catInput.attr('disabled', 'disabled').val('').trigger('change');
                    self.$triggerLabel.text(conf.allCatText);
                }
                self.$qInput.trigger('input');
                $el.removeClass(conf.activeClass);
            });
            $body.on('click', (e) => {
                if (!$el.has($(e.target)).length) $el.removeClass(conf.activeClass);
            });
        }
    }); 
    $.widget('codazon.customValidation', {
        _create: function() {
            require(['validation', 'domReady'], () => {
                this.element.validation();
            });
        }
    });
    $.widget('codazon.toggleList', {
        options: {
            item: 'li',
            itemList: 'ul',
            link: 'a'
        },
        _create: function() {
            var conf = this.options;
            this.element.children(conf.item).addClass('level-top');
            $(conf.item, this.element).each(function() {
                var $item = $(this), $a = $item.children(conf.link);
                if ($item.children(conf.itemList).length) {
                    $item.addClass('parent');
                    var $itemList = $item.children(conf.itemList).hide();
                    $('<span class="menu-toggle">').insertAfter($a).on('click', function() {
                        $itemList.slideToggle(300);
                        $item.toggleClass('active');
                    });
                }
            });
        }
    });
    
    $.widget('codazon.ratingSummary', {
        options: {
            tmpl: '#rating-summary-tmpl'
        },
        _create: function() {
            var conf = this.options;
            require(['mage/template', 'underscore'], (mageTemplate) => {
                let tmpl = mageTemplate(conf.tmpl), $parent = $('.product-info-main .product-reviews-summary').first().find('.rating-summary');
                if ($parent.length) {
                    $(tmpl({data: conf.data})).appendTo($parent);
                }
            });
        }
    });
    
    $.widget('codazon.innerZoom', {
        options: {
            stage: '.fotorama__stage',
            width: 250,
            height: 250
        },
        _create: function() {
            this.element.on('gallery:loaded', () => {
                if (!this.element.data('gallery')) return false;
                this._addMagnifier();
            });
        },
        _addMagnifier: function() {
            var conf = this.options;
            this.$stage = this.element.find(conf.stage).first();
            this.$magnifier = $('<div class="cdz-magnifier">').css({
                width: conf.width,
                height: conf.height,
                position: 'absolute',
                left: 0,
                top: 0,
            }).appendTo(this.$stage);
            this._manify();
        },
        _manify: function() {
            var conf = this.options, nativeWidth = 0, nativeHeight = 0, backgroundSize = 0,
            fotorama = this.element.data('gallery').fotorama, t = false;
            this.$stage.on('mousemove.innerZoom', (e) => {
                if (fotorama.activeFrame.type == 'video') {
                    this.$stage.removeClass('cdz-manifier-active');
                    this.$magnifier.hide();
                    return false;
                }
                $mainImg = fotorama.activeFrame.$stageFrame;
                if ($mainImg) {
                    this.$stage.addClass('cdz-manifier-active');
                    if (!nativeWidth && !nativeHeight) {
                        var imgObject = new Image();
                        $(imgObject).on('load', () => {
                            nativeWidth = imgObject.width * conf.zoomRatio;
                            nativeHeight = imgObject.height * conf.zoomRatio;
                            backgroundSize = nativeWidth.toString() + 'px ' + nativeHeight.toString() + 'px';
                        });
                        imgObject.src = fotorama.activeFrame.full;
                    } else {
                        var magnifierOffset = this.$stage.offset(), mx = e.pageX - magnifierOffset.left,
                        my = e.pageY - magnifierOffset.top;
                    }
                    if (mx < this.$stage.width() && my < this.$stage.height() && mx > 0 && my > 0) {
                        this.$magnifier.show();
                        if (t) clearTimeout(t);
                        t = setTimeout(() => {
                            this.$stage.addClass('cdz-manifier-active');
                        }, 100);
                    } else {
                        this.$magnifier.hide();
                        this.$stage.removeClass('cdz-manifier-active');
                    }
                    if (this.$magnifier.is(':visible')) {
                        var dx = $mainImg.offset().left - this.$stage.offset().left, dy = $mainImg.offset().top - this.$stage.offset().top,
                        rx = Math.round(mx / $mainImg.width() * nativeWidth - this.$magnifier.width() / 2) * (-1) + dx,
                        ry = Math.round(my / $mainImg.height() * nativeHeight - this.$magnifier.height() / 2) * (-1) + dy,
                        bgp = rx + "px " + ry + "px", px = mx - this.$magnifier.width() / 2, py = my - this.$magnifier.height() / 2;
                        this.$magnifier.css({
                            left: px,
                            top: py,
                            background: `url("${fotorama.activeFrame.full}") no-repeat ${bgp}/${backgroundSize}`,
                        });
                    }
                }
            });
            this.element.on('fotorama:show', () => {
                nativeWidth = 0;
                nativeHeight = 0;
            });
            this.$stage.on('mouseleave.innerZoom', (e) => {
                if (t) clearTimeout(t);
                this.$magnifier.hide();
                t = setTimeout(() => {
                    this.$stage.removeClass('cdz-manifier-active');
                }, 100);
            });
        }
    });
    
    $.widget('codazon.ajaxcmsblock', {
        _create: function() {
            var conf = this.options;
            if (conf.ajaxUrl && conf.blockIdentifier) {
                $.ajax({
                    url: conf.ajaxUrl,
                    cache: true,
                    data: {block_identifier: conf.blockIdentifier},
                    method: 'get',
                    success: (rs) => {
                        this.element.html(rs);
                        if (typeof conf.afterLoaded == 'function') {
                            conf.afterLoaded();
                        };
                        this.element.trigger('contentLoaded').trigger('contentUpdated');
                    }
                });
            }
        }
    });
    $.widget('codazon.newsletterPopup', {
        _create: function() {
            var conf = this.options, cookieName = conf.cookieName;
            require(['mage/cookies'], () => {
                var checkCookie = $.cookie(cookieName);
                if (!checkCookie) {
                    var date = new Date(), minutes = conf.frequency;
                    date.setTime(date.getTime() + (minutes * 60 * 1000));
                    $.cookie(cookieName, '1', date);
                    setTimeout(() => {
                        var $popup = this.element;
                        $.codazon.ajaxcmsblock({
                            ajaxUrl: conf.ajaxUrl,
                            blockIdentifier: conf.blockIdentifier,
                            afterLoaded: () => {
                               $popup.modal({
                                    autoOpen: true,
                                    buttons: [],
                                    modalClass: 'cdz-newsletter-modal'
                                });
                            }
                        }, $popup);
                    }, conf.delay);
                }
            });
        }
    });
    $.widget('codazon.ajaxcontent', {
        options: {
            cache: true,
            method: 'GET',
            handle: 'replaceWith'
        },
        _create: function(){
            var conf = this.options;
            $.ajax({
                url: conf.ajaxUrl,
                method: conf.method,
                cache: conf.cache,
                success: (rs) => {
                    var $rs = (this.element[conf.handle])(rs);
                    if (typeof conf.afterLoaded == 'function') {
                        conf.afterLoaded();
                    } else if (typeof conf.afterLoaded == 'string') {
                        eval(conf.afterLoaded)
                    }
                    $body.trigger('contentUpdated');
                    require(['ko'], function(ko) {
                        ko.cleanNode($rs.get(0));
                        if($.fn.applyBindings != undefined) { $rs.applyBindings(); }
                    });
                }
            })
        }
    });
    $.widget('codazon.themewidgets', {
        _create: function() {
            $.each(this.options, (fn, options) => {
                var namespace = fn.split(".")[0], name = fn.split(".")[1];
                if (typeof $[namespace] !== 'undefined') {
                    if ((namespace == 'codazon') && (name == 'slider')) name = 'flexibleSlider';
                    if(typeof $[namespace][name] !== 'undefined') $[namespace][name](options, this.element);
                }
            });
        }
    });
    return $.codazon.themewidgets;
});