/**
 * AbClos — goal « revenue » sur la page confirmation de commande.
 * Lit data-order (thème Classic) ; dédup localStorage ; skip preview.
 */
(function () {
  'use strict';

  var GOAL = 'revenue';

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

  function storageKey(reference) {
    return 'abclos_revenue_' + reference;
  }

  function trackRevenue(value, reference) {
    function send() {
      if (!window.abclos || typeof window.abclos.track !== 'function') {
        return false;
      }
      try {
        window.abclos.track(GOAL, { value: value });
        try {
          localStorage.setItem(storageKey(reference), '1');
        } catch (storageErr) {
          /* ignore quota / private mode */
        }
        if (window.console && console.debug) {
          console.debug('[AbClos] track', GOAL, { value: value, reference: reference });
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

    var orderContent = document.querySelector('section#content.page-order-confirmation');
    if (!orderContent || !orderContent.dataset.order) {
      return;
    }

    var order;
    try {
      order = JSON.parse(orderContent.dataset.order);
    } catch (e) {
      return;
    }

    var reference = order && order.details && order.details.reference
      ? String(order.details.reference)
      : '';
    if (!reference) {
      return;
    }

    try {
      if (localStorage.getItem(storageKey(reference))) {
        return;
      }
    } catch (e) {
      /* continue without dédup if storage unavailable */
    }

    var amount = order.amounts
      && order.amounts.totals
      && order.amounts.totals.total_including_tax
      && order.amounts.totals.total_including_tax.amount;

    var value = parseFloat(amount);
    if (!isFinite(value) || value < 0) {
      return;
    }

    trackRevenue(value, reference);
  });
})();
