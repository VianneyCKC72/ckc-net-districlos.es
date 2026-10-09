/**
 * AbClos — goal « add_to_cart ».
 * updateCart (linkAction strict) + fallback clic ; debounce ; skip preview.
 */
(function () {
  'use strict';

  var GOAL = 'add_to_cart';
  var lastSent = 0;
  var DEBOUNCE_MS = 800;

  function isPreview() {
    try {
      return /(?:^|[?&])abclos_preview=1(?:&|$)/.test(window.location.search);
    } catch (e) {
      return false;
    }
  }

  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  function trackAddToCart(meta) {
    if (isPreview()) {
      return;
    }

    var now = Date.now();
    if (now - lastSent < DEBOUNCE_MS) {
      return;
    }
    lastSent = now;

    function send() {
      if (!window.abclos || typeof window.abclos.track !== 'function') {
        return false;
      }
      try {
        window.abclos.track(GOAL, meta || {});
        if (window.console && console.debug) {
          console.debug('[AbClos] track', GOAL, meta || {});
        }
      } catch (e) {
        if (window.console && console.warn) {
          console.warn('[AbClos] track failed', e);
        }
      }
      return true;
    }

    if (send()) {
      return;
    }

    var attempts = 0;
    var timer = setInterval(function () {
      attempts += 1;
      if (send() || attempts > 20) {
        clearInterval(timer);
      }
    }, 250);
  }

  ready(function () {
    if (isPreview()) {
      return;
    }

    if (typeof prestashop !== 'undefined' && prestashop.on) {
      prestashop.on('updateCart', function (event) {
        var reason = event && event.reason ? event.reason : null;
        if (!reason || reason.linkAction !== 'add-to-cart') {
          return;
        }
        trackAddToCart({
          source: 'prestashop.updateCart',
          id_product: reason.idProduct || undefined,
        });
      });
    }

    document.addEventListener(
      'click',
      function (e) {
        var btn = e.target && e.target.closest
          ? e.target.closest('[data-button-action="add-to-cart"], button.add-to-cart')
          : null;
        if (!btn) {
          return;
        }
        setTimeout(function () {
          trackAddToCart({ source: 'click.add-to-cart' });
        }, 600);
      },
      true
    );
  });
})();
