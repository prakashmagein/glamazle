require(['jquery', 'domReady!'], function ($) {
  'use strict';

  /* ===== Route guards & special selectors ===== */
  var RAW_PATH = (window.location && window.location.pathname || '/');
  var PATH = RAW_PATH.toLowerCase().replace(/\/+$/,'/'); // normalize trailing slash
  var IS_BRANDS = /^\/brands(\/|$)/i.test(PATH);

  function bodyHas(cls){ var b=document.body; if(!b) return false; return new RegExp('(^|\\s)'+cls+'(\\s|$)').test(b.className||''); }

  // Only true for actual one-page checkout, *not* cart
  var IS_ONEPAGE_CHECKOUT =
      (PATH === '/checkout/' || PATH === '/checkout')
      || bodyHas('checkout-index-index')
      // optional: common OSC modules
      || /^\/onestepcheckout(\/|$)/i.test(PATH)
      || bodyHas('onestepcheckout-index-index');

  var BRANDS_AJAX_SAFE  = '.char-list[data-role="char-list"]';

  // Common Magento logo patterns (cover Luma + checkout header variants)
  var CHECKOUT_LOGO_SEL_ARR = [
    'a.logo',
    '.logo > a',
    'header .logo a',
    '.page-header .logo a',
    '.opc-wrapper .logo a',
    '.checkout-header .logo a'
  ];
  var CHECKOUT_LOGO_SEL = CHECKOUT_LOGO_SEL_ARR.join(',');

  function isCheckoutLogoAnchor($el){
    if (!$el || !$el.length) return false;
    return $el.is(CHECKOUT_LOGO_SEL) || $el.closest(CHECKOUT_LOGO_SEL).length > 0;
  }
  function isCheckoutLogoNode(node){
    if (!node || node.nodeType !== 1) return false;
    try {
      if (node.matches && node.matches(CHECKOUT_LOGO_SEL)) return true;
      var a = node.closest && node.closest(CHECKOUT_LOGO_SEL);
      return !!a;
    } catch(e){ return false; }
  }

  if (window.__SMART_LOADER_INIT__) return;
  window.__SMART_LOADER_INIT__ = true;

  /* ============== UA detection (iOS/Desktop Safari lane) ============== */
  var UA = navigator.userAgent || '';
  var PLAT = navigator.platform || '';
  var IOS_WEBKIT = (/iP(hone|ad|od)/.test(PLAT) || (/\bMac\b/.test(PLAT) && (navigator.maxTouchPoints||0)>1))
                   && /AppleWebKit/i.test(UA);
  var isSafariLike = /^((?!chrome|android|crios|fxios|edgios|opr).)*safari/i.test(UA);
  var WEBKIT_SAFARI = IOS_WEBKIT || isSafariLike;

  /* ============== Storage shim ============== */
  var STORE_KEY_TTFB = 'lastTTFB';
  var STORE_KEY_LOAD = 'lastLoadTime';
  var NAME_BLOB_KEY  = '__SMART_LOADER__';

  function readWindowNameBlob() {
    try {
      var nm = window.name || '';
      if (nm && nm.indexOf(NAME_BLOB_KEY) === 0) {
        return JSON.parse(nm.slice(NAME_BLOB_KEY.length)) || {};
      }
    } catch (e) {}
    return {};
  }
  function writeWindowNameBlob(obj) {
    try { window.name = NAME_BLOB_KEY + JSON.stringify(obj || {}); } catch (e) {}
  }
  var nameBlob = readWindowNameBlob();

  var storage = {
    get: function (k) {
      try { var v = sessionStorage.getItem(k); if (v != null) return v; } catch (e) {}
      try { var v2 = localStorage.getItem(k); if (v2 != null) return v2; } catch (e2) {}
      if (Object.prototype.hasOwnProperty.call(nameBlob, k)) return String(nameBlob[k]);
      return null;
    },
    set: function (k, v) {
      var ok = false;
      try { sessionStorage.setItem(k, String(v)); ok = true; } catch (e) {}
      if (!ok) { try { localStorage.setItem(k, String(v)); ok = true; } catch (e2) {} }
      if (!ok) { nameBlob[k] = String(v); writeWindowNameBlob(nameBlob); }
    },
    remove: function (k) {
      try { sessionStorage.removeItem(k); } catch (e) {}
      try { localStorage.removeItem(k); } catch (e2) {}
      if (Object.prototype.hasOwnProperty.call(nameBlob, k)) { delete nameBlob[k]; writeWindowNameBlob(nameBlob); }
    }
  };

  function getNumber(key){ var raw=storage.get(key), n=raw==null?NaN:parseFloat(raw); return isFinite(n)?n:NaN; }
  function setNumber(key,n){ if(isFinite(n)) storage.set(key,String(n)); }
  function nowTs(){ try{ return performance.now(); }catch(e){ return +new Date(); } }

  /* ============== CSS ============== */
  (function injectCSS(){
    if (document.getElementById('smart-loader-css')) return;
    var css = ''
      + '#top-loader{position:fixed;top:0;left:0;right:0;height:6px;z-index:2147483647;pointer-events:none;'
      + 'background:rgba(224,224,224,.4);opacity:0;transition:opacity .25s ease;-webkit-transform:translateZ(0);transform:translateZ(0)}'
      + '#top-loader.show{opacity:1}'
      + '#top-loader .loader-shell{position:relative;width:100%;height:100%;overflow:hidden}'
      + '#top-loader .loader-bar{position:absolute;left:0;top:0;height:100%;width:100%;background:#3b82f6;background-size:200% 100%;box-shadow:0 0 10px rgba(59,130,246,.35);-webkit-backface-visibility:hidden;backface-visibility:hidden}'
      /* Non-Safari: JS drives width */
      + '#top-loader.width-mode .loader-bar{width:0%;transition:width .22s linear;will-change:width}'
      /* Safari lane: transform keyframe */
      + '#top-loader.webkit{transition:none !important}'
      + '#top-loader.webkit .loader-bar{-webkit-transform-origin:0 50%;transform-origin:0 50%;'
      + ' -webkit-transform:translateZ(0) scaleX(0.001);transform:translateZ(0) scaleX(0.001);will-change:transform}'
      /* Shimmer when holding 100% */
      + '#top-loader.hold-100 .loader-bar::after{content:"";position:absolute;inset:0;pointer-events:none;'
      + ' background:linear-gradient(90deg,rgba(255,255,255,0) 0%,rgba(255,255,255,.6) 50%,rgba(255,255,255,0) 100%);'
      + ' animation:smart-shimmer 1.1s linear infinite;mix-blend:screen}'
      + '@keyframes smart-shimmer{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}'
      + '@-webkit-keyframes smart-grow{from{-webkit-transform:translateZ(0) scaleX(0.001);transform:translateZ(0) scaleX(0.001)}'
      + ' to{-webkit-transform:translateZ(0) scaleX(1);transform:translateZ(0) scaleX(1)}}'
      + '@keyframes smart-grow{from{-webkit-transform:translateZ(0) scaleX(0.001);transform:translateZ(0) scaleX(0.001)}'
      + ' to{-webkit-transform:translateZ(0) scaleX(1);transform:translateZ(0) scaleX(1)}}';
    var style = document.createElement('style'); style.id='smart-loader-css';
    style.appendChild(document.createTextNode(css));
    (document.head || document.getElementsByTagName('head')[0]).appendChild(style);
  })();

  /* ============== DOM ============== */
  var $loader = $('#top-loader');
  if ($loader.length === 0) {
    $loader = $('<div id="top-loader" class="hidden"></div>');
    $loader.append('<div class="loader-shell"><div class="loader-bar"></div></div>');
    $loader.append('<div class="loader-percent" aria-hidden="true"></div>');
    $('body').prepend($loader);
  }
  var $bar = $loader.find('.loader-bar');
  var $percent = $loader.find('.loader-percent');

  // Pick rendering mode
  if (WEBKIT_SAFARI) { $loader.addClass('webkit'); } else { $loader.addClass('width-mode'); }

  /* ============== Timing (+Product Page Markup) ============== */
  var lastTTFB = getNumber(STORE_KEY_TTFB);  // ms
  var lastLoad = getNumber(STORE_KEY_LOAD);  // ms

  var MIN_TIME = 1200, MAX_TIME = 8000;
  var baseDur = (isFinite(lastTTFB)&&lastTTFB>0)?lastTTFB:(isFinite(lastLoad)&&lastLoad>0)?lastLoad:1800;
  var isProductPage = bodyHas('catalog-product-view');
  if (isProductPage && isFinite(lastTTFB) && lastTTFB > 0) {
    baseDur = (lastTTFB < 5000) ? (lastTTFB + 2000) : lastTTFB; // e.g., 2.0s -> 4.0s
  }
  var animDuration = Math.min(Math.max(baseDur, MIN_TIME), MAX_TIME);

  var isActive=false, pendingUrl=null, pendingForm=null, navigationLocked=false;

  /* ============== Intent gate ============== */
  var recentNavIntentAt = 0;
  var INTENT_WINDOW_MS = 1500;
  function markNavIntent(){ recentNavIntentAt = nowTs(); }
  function hasRecentIntent(){ return (nowTs() - recentNavIntentAt) <= INTENT_WINDOW_MS; }
  function hasDestination(){ return !!(pendingUrl || pendingForm); }

  /* ============== iOS/Safari paint helper ============== */
  function paintThen(fn){
    try {
      requestAnimationFrame(function(){
        requestAnimationFrame(function(){
          setTimeout(function(){ try{ fn(); }catch(e){} }, 60);
        });
      });
    } catch(e){
      setTimeout(function(){ try{ fn(); }catch(e){} }, 100);
    }
  }

  /* ============== UI helpers ============== */
  function setProgress(p){
    p=Math.min(100,Math.max(0,p));
    if($percent.length) { try{ $percent.text(Math.round(p)+'%'); }catch(e){} }
  }
  function showLoader(){
    // used by keyframe lane
    $loader[0].style.setProperty('--dur', animDuration + 'ms');
    $loader.addClass('show').removeClass('hidden').css('opacity',1);
    setProgress(0);
    try{ $loader[0].getBoundingClientRect(); }catch(e){}
  }
  function holdAt100(){ $loader.addClass('hold-100'); setProgress(100); }
  function hideLoader(){
    // reset inline animation for Safari/WebKit lane
    try { $bar[0].style.webkitAnimation = $bar[0].style.animation = 'none'; } catch(e){}
    $loader.removeClass('hold-100 run').css('opacity',0);
    setTimeout(function(){ $loader.addClass('hidden').removeClass('show'); isActive=false; },250);
  }
  function rampProgress(duration, done){
    var start = nowTs();
    function step(ts){
      if (!ts){ ts = nowTs(); }
      var t=Math.min(1,(ts-start)/duration);
      var eased = t; // linear for exactness
      var pct = eased*100;
      if ($loader.hasClass('width-mode')) { $bar.css('width', pct+'%'); }
      setProgress(pct);
      if (t<1){ requestAnimationFrame(step); } else { if(done) done(); }
    }
    requestAnimationFrame(step);
  }

  /* ============== Start loader (Safari exact-duration) ============== */
  function startLoader(){
    if (isActive) return;
    isActive = true;
    showLoader(); // sets --dur

    if (WEBKIT_SAFARI) {
      // Force reflow + inline animation with a *linear* timing function for exact perceived duration
      try {
        $bar[0].style.webkitAnimation = $bar[0].style.animation = 'none';
        void $bar[0].offsetWidth; // reflow
        var animStr = 'smart-grow ' + animDuration + 'ms linear forwards';
        $bar[0].style.webkitAnimation = animStr;
        $bar[0].style.animation = animStr;

        // Hard stop guard: if Safari throttles frames, force the "hold" at exactly animDuration
        setTimeout(function(){ if (isActive) holdAt100(); }, animDuration);
      } catch(e){}
    } else {
      $bar.css('width','0%');
      rampProgress(animDuration, function(){ holdAt100(); });
    }
  }

  function triggerLoader(){
    if(navigationLocked) return;
    navigationLocked = true;
    markNavIntent();
    try { storage.set('navigationStart', String(nowTs())); } catch(e){}
    startLoader();
    paintThen(function(){
      if(pendingForm){ try{ pendingForm.submit(); }catch(e){} }
      else if(pendingUrl){ window.location.href = pendingUrl; }
    });
  }

  /* ===================== CART PAGE CTAs (capture-phase) =====================
     Safari: preventDefault, start CSS animation, then navigate after paint.
     Others: show loader early but do NOT preventDefault.
  =========================================================================== */
  if (!IS_ONEPAGE_CHECKOUT) {
    var CTA_SEL = [
      '[data-role="proceed-to-checkout"]',
      'button.action.primary.checkout',
      '#top-cart-btn-checkout',
      '#ckoApplePayButton',
      '.apple-pay-button',
      'a.action.multicheckout',
      'a[href*="/multishipping/checkout"]'
    ].join(',');

    function matches(el, sel){
      var p=Element.prototype;
      var fn=p.matches||p.msMatchesSelector||p.webkitMatchesSelector||p.mozMatchesSelector||p.oMatchesSelector;
      try{ return fn ? fn.call(el, sel) : false; }catch(e){ return false; }
    }
    function isCta(el){ return el && matches(el, CTA_SEL); }

    function captureCTA(evt){
      var t = evt.target;
      while (t && t !== document) {
        if (isCta(t)) {
          // Respect checkout logo exemption even if CTA is inside header
          if (isCheckoutLogoNode(t)) return;
          markNavIntent();
          if (WEBKIT_SAFARI) {
            evt.preventDefault();
            startLoader();
            var href = (t.tagName==='A' && t.href) ? t.href : null;
            if (!href) {
              var a = t.closest ? t.closest('a[href]') : null;
              if (a && a.href) href = a.href;
            }
            paintThen(function(){
              if (href) { window.location.href = href; }
              else { try { t.click && t.click(); } catch(e){} }
            });
          } else {
            startLoader(); // let default proceed
          }
          break;
        }
        t = t.parentNode;
      }
    }

    document.addEventListener('mousedown', captureCTA, true);

    // bind direct for late-inserted nodes
    function bindDirect(el){
      if (!el || el.__smartLoaderBound) return;
      el.__smartLoaderBound = true;
      el.addEventListener('click', function(e){
        if (isCheckoutLogoNode(el)) return;
        markNavIntent();
        if (WEBKIT_SAFARI) {
          e.preventDefault();
          startLoader();
          var href = (el.tagName==='A' && el.href) ? el.href : null;
          paintThen(function(){ if(href){ window.location.href = href; } else { try{ el.click && el.click(); }catch(_e){} }});
        } else {
          startLoader();
        }
      }, true);
    }
    function tryBindAll(root){
      try {
        var nodes = root.querySelectorAll ? root.querySelectorAll(CTA_SEL) : [];
        for (var i=0;i<nodes.length;i++) bindDirect(nodes[i]);
      } catch(e){}
    }
    tryBindAll(document);

    try{
      var obs = new MutationObserver(function(muts){
        for (var i=0;i<muts.length;i++){
          var m = muts[i];
          if (!m.addedNodes) continue;
          for (var j=0;j<m.addedNodes.length;j++){
            var n = m.addedNodes[j];
            if (!n || n.nodeType !== 1) continue;
            if (isCta(n)) bindDirect(n);
            tryBindAll(n);
          }
        }
      });
      obs.observe(document.documentElement, {childList:true, subtree:true});
    }catch(e){}
  }

  /* ============== Normal links (same-tab), with brand alphabet exemption ============== */
  $(document).on('click','a',function(e){
    var $a = $(this), href = $a.attr('href') || '';

    // Exemption: brand alphabet widget (AJAX only)
    if (IS_BRANDS) {
      var $t = $(e.target);
      if ($t.closest(BRANDS_AJAX_SAFE).length || $a.closest(BRANDS_AJAX_SAFE).length) { return; }
    }

    // On ONE-PAGE checkout: ONLY the logo should use the loader; others untouched
    if (IS_ONEPAGE_CHECKOUT && !isCheckoutLogoAnchor($a)) { return; }

    // Ignore non-navigations
    if(!href || href.indexOf('#')===0 || href.indexOf('mailto:')===0 || href.indexOf('tel:')===0 ||
       href==='javascript:;' || href==='javascript:void(0)') return;
    if($a.attr('target')==='_blank' || e.ctrlKey || e.metaKey || e.shiftKey || e.which===2) return;

    e.preventDefault();
    markNavIntent();
    pendingUrl = href;

    if (WEBKIT_SAFARI) {
      startLoader();
      // Ensure a paint before leaving so animation is visible
      paintThen(function(){ window.location.href = pendingUrl; });
    } else {
      triggerLoader();
    }
  });

  /* ============== Non-AJAX forms (skip only on one-page checkout) ============== */
  $(document).on('submit','form',function(e){
    if (IS_ONEPAGE_CHECKOUT) return;
    var $f=$(this);
    if($f.attr('target') || $f.data('role')==='tocart-form' || $f.hasClass('ajax-form')) return;
    e.preventDefault();
    markNavIntent();
    pendingForm=this;
    triggerLoader();
  });

  /* ============== Programmatic redirects (skip only on one-page checkout) ============== */
  (function hookLocation(){
    if (IS_ONEPAGE_CHECKOUT) return;
    try{
      if(window.location && window.location.assign){
        var _assign=window.location.assign.bind(window.location);
        window.location.assign=function(url){
          pendingUrl=url; markNavIntent();
          if (WEBKIT_SAFARI){ startLoader(); paintThen(function(){ _assign(url); }); }
          else { triggerLoader(); }
        };
      }
      if(window.location && window.location.replace){
        var _replace=window.location.replace.bind(window.location);
        window.location.replace=function(url){
          pendingUrl=url; markNavIntent();
          if (WEBKIT_SAFARI){ startLoader(); paintThen(function(){ _replace(url); }); }
          else { triggerLoader(); }
        };
      }
    }catch(e){}
  })();

  /* ============== Safety nets (intent-gated) — defined AFTER checkout flag ============== */
  if (!IS_ONEPAGE_CHECKOUT) {
    window.addEventListener('beforeunload', function(){
      if (!isActive && (navigationLocked || hasRecentIntent() || hasDestination())) startLoader();
    }, true);

    document.addEventListener('visibilitychange', function(){
      if (document.visibilityState === 'hidden' && !isActive &&
          (navigationLocked || hasRecentIntent() || hasDestination())) {
        startLoader();
      }
    }, true);

    window.addEventListener('pagehide', function(ev){
      var persisted = !!(ev && ev.persisted);
      if (!isActive && !persisted && (navigationLocked || hasRecentIntent() || hasDestination())) startLoader();
    }, true);

    // BFCache restore: reset UI if we come back cached
    window.addEventListener('pageshow', function (ev) {
      if (ev && ev.persisted) {
        try { $bar[0].style.webkitAnimation = $bar[0].style.animation = 'none'; } catch(e){}
        $loader.addClass('hidden').removeClass('show hold-100 run').css('opacity',0);
        isActive=false; navigationLocked=false; pendingUrl=null; pendingForm=null;
      }
    });
  }

  /* ============== Persist timings for NEXT page ============== */
  window.addEventListener('load', function () {
    try {
      var navEntries = (performance.getEntriesByType && performance.getEntriesByType('navigation')) || null;
      var nav = navEntries && navEntries[0];
      if (nav) {
        var ttfb = Math.max(0, nav.responseStart - nav.startTime);
        var full = Math.max(0, (nav.loadEventEnd || nowTs()) - nav.startTime);
        var safeTTFB = Math.min(Math.max(ttfb || 0, 200), 12000);
        var safeLoad = Math.min(Math.max(full || 0, 600), 20000);
        setNumber(STORE_KEY_TTFB, safeTTFB);
        setNumber(STORE_KEY_LOAD, safeLoad);
        storage.remove('navigationStart');
      } else {
        var navStartRaw = storage.get('navigationStart');
        var navStart = navStartRaw ? parseFloat(navStartRaw) : NaN;
        if (isFinite(navStart) && navStart > 0) {
          var approxLoad = nowTs() - navStart;
          approxLoad = Math.min(Math.max(approxLoad, 600), 20000);
          setNumber(STORE_KEY_LOAD, approxLoad);
          storage.remove('navigationStart');
        }
      }
    } catch (e) {}
    holdAt100();
    setTimeout(hideLoader, 600);
  });

  // Initial state
  $loader.addClass('hidden').removeClass('show hold-100 run').css('opacity',0);
});
