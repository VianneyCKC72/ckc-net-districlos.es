(function () {
  'use strict';

  var interval = null;
  var config = null;

  function now() {
    return Math.floor(Date.now() / 1000);
  }

  function getBanner() {
    return document.getElementById('dtc-reserved-banner');
  }

  function resolveConfig() {
    var banner = getBanner();

    if (!banner || !banner.getAttribute('data-duration')) {
      return null;
    }

    return {
      duration: parseInt(banner.getAttribute('data-duration'), 10) || 900,
      deadlineKey: banner.getAttribute('data-deadline-key') || 'dtc_cart_reservation_deadline',
      resetFlagKey: banner.getAttribute('data-reset-flag-key') || 'dtc_cart_reservation_reset',
      labelMin: banner.getAttribute('data-label-min') || 'min',
      labelSec: banner.getAttribute('data-label-sec') || 'sec',
    };
  }

  function getTimerEl() {
    var banner = getBanner();
    return banner ? banner.querySelector('.dtc-rb-timer') : null;
  }

  function isCartEmpty() {
    if (document.querySelector('.cart-overview .no-items')) {
      return true;
    }

    return document.querySelectorAll('.cart-overview .cart-items .cart-item').length === 0;
  }

  function hideBanner() {
    if (interval) {
      clearInterval(interval);
      interval = null;
    }

    var banner = getBanner();
    if (banner) {
      banner.style.display = 'none';
    }
  }

  function hideBannerForEmptyCart() {
    hideBanner();
    if (!config) {
      return;
    }
    sessionStorage.removeItem(config.deadlineKey);
    sessionStorage.removeItem(config.resetFlagKey);
  }

  function showBanner() {
    var banner = getBanner();
    if (banner) {
      banner.style.display = 'flex';
    }
  }

  function startCountdown() {
    var timerEl = getTimerEl();
    if (!timerEl || !config) {
      return;
    }

    function render() {
      if (isCartEmpty()) {
        hideBannerForEmptyCart();
        return;
      }

      var remaining = parseInt(sessionStorage.getItem(config.deadlineKey), 10) - now();

      if (isNaN(remaining) || remaining <= 0) {
        hideBanner();
        return;
      }

      var minutes = Math.floor(remaining / 60);
      var seconds = remaining % 60;
      timerEl.textContent = minutes + config.labelMin + ' : ' + (seconds < 10 ? '0' + seconds : seconds) + config.labelSec;
      showBanner();
    }

    render();

    if (interval) {
      clearInterval(interval);
    }

    interval = setInterval(render, 1000);
  }

  function startNewCountdown() {
    if (!config) {
      return;
    }

    if (isCartEmpty()) {
      hideBannerForEmptyCart();
      return;
    }

    sessionStorage.setItem(config.deadlineKey, now() + config.duration);
    startCountdown();
  }

  function showBannerIfActive() {
    if (!config) {
      return;
    }

    if (isCartEmpty()) {
      hideBannerForEmptyCart();
      return;
    }

    var deadline = parseInt(sessionStorage.getItem(config.deadlineKey), 10);

    if (isNaN(deadline) || deadline <= now()) {
      hideBanner();
      return;
    }

    startCountdown();
  }

  function shouldResetFromUrl() {
    try {
      return new URLSearchParams(window.location.search).get('add') === '1';
    } catch (e) {
      return window.location.search.indexOf('add=1') !== -1;
    }
  }

  function syncBannerWithCart(event) {
    config = resolveConfig();
    if (!config || !getBanner()) {
      return;
    }

    if (isCartEmpty()) {
      hideBannerForEmptyCart();
      return;
    }

    var action = event && event.reason ? event.reason.linkAction : null;

    if (action === 'add-to-cart') {
      startNewCountdown();
      return;
    }

    showBannerIfActive();
  }

  function init() {
    config = resolveConfig();
    if (!config || !getBanner()) {
      return;
    }

    if (isCartEmpty()) {
      hideBannerForEmptyCart();
      return;
    }

    if (sessionStorage.getItem(config.resetFlagKey) === '1') {
      sessionStorage.removeItem(config.resetFlagKey);
      startNewCountdown();
      return;
    }

    if (shouldResetFromUrl()) {
      startNewCountdown();
      return;
    }

    if (isNaN(parseInt(sessionStorage.getItem(config.deadlineKey), 10))) {
      startNewCountdown();
      return;
    }

    showBannerIfActive();
  }

  function bindEvents() {
    if (typeof prestashop !== 'undefined' && typeof prestashop.on === 'function') {
      prestashop.on('updateCart', syncBannerWithCart);
      prestashop.on('updatedCart', syncBannerWithCart);
    }

    if (typeof jQuery !== 'undefined') {
      jQuery(document).ajaxComplete(function (event, xhr, settings) {
        if (!getBanner() || !isCartAjaxRequest(settings)) {
          return;
        }

        syncBannerWithCart();
      });
    }
  }

  function isCartAjaxRequest(settings) {
    var url = settings && settings.url ? settings.url : '';
    var data = settings && settings.data ? String(settings.data) : '';

    return /(?:^|[?&])(?:controller=cart|action=refresh)|\/panier|\/cart(?:\?|$|\/)/i.test(url)
      || /(?:^|[?&])controller=cart|action=refresh/i.test(data);
  }

  function watchCartChanges() {
    var cartEl = document.querySelector('.cart-overview.js-cart');

    if (!cartEl || typeof MutationObserver === 'undefined') {
      return;
    }

    var observer = new MutationObserver(function () {
      syncBannerWithCart();
    });

    observer.observe(cartEl, {
      childList: true,
      subtree: true,
    });
  }

  function boot() {
    init();
    bindEvents();
    watchCartChanges();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
