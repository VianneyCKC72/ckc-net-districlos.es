/**
 * AbClos — goal « revenue » sur la page confirmation de commande.
 * 1) window.abclosOrder (injecté PHP, fiable)
 * 2) fallback data-order (thème Classic)
 * Dédup localStorage ; skip preview.
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

  function parseAmount(raw) {
    if (typeof raw === 'number') {
      return raw;
    }
    if (raw == null || raw === '') {
      return NaN;
    }
    // "1 234,56 €" / "1234.56" → nombre
    var cleaned = String(raw)
      .replace(/\s/g, '')
      .replace(/[^\d,.-]/g, '')
      .replace(',', '.');
    return parseFloat(cleaned);
  }

  function readFromPhp() {
    var payload = window.abclosOrder;
    if (!payload || !payload.reference) {
      return null;
    }
    var value = parseAmount(payload.value);
    if (!isFinite(value) || value < 0) {
      return null;
    }
    return {
      reference: String(payload.reference),
      value: value,
      source: 'php',
    };
  }

  function readFromDom() {
    var orderContent = document.querySelector('section#content.page-order-confirmation');
    if (!orderContent || !orderContent.dataset.order) {
      return null;
    }

    var order;
    try {
      order = JSON.parse(orderContent.dataset.order);
    } catch (e) {
      return null;
    }

    var reference = order && order.details && order.details.reference
      ? String(order.details.reference)
      : '';
    if (!reference) {
      return null;
    }

    var amount =
      order.amounts &&
      order.amounts.totals &&
      order.amounts.totals.total_including_tax &&
      order.amounts.totals.total_including_tax.amount;

    // Fallback total / total_paid si structure thème custom
    if (amount == null && order.amounts && order.amounts.totals) {
      var totals = order.amounts.totals;
      if (totals.total && totals.total.amount != null) {
        amount = totals.total.amount;
      } else if (totals.total_paid && totals.total_paid.amount != null) {
        amount = totals.total_paid.amount;
      }
    }

    var value = parseAmount(amount);
    if (!isFinite(value) || value < 0) {
      return null;
    }

    return {
      reference: reference,
      value: value,
      source: 'dom',
    };
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
      if (send() || attempts > 40) {
        clearInterval(timer);
      }
    }, 250);
  }

  ready(function () {
    if (isPreview()) {
      return;
    }

    var hit = readFromPhp() || readFromDom();
    if (!hit) {
      return;
    }

    try {
      if (localStorage.getItem(storageKey(hit.reference))) {
        return;
      }
    } catch (e) {
      /* continue without dédup if storage unavailable */
    }

    trackRevenue(hit.value, hit.reference);
  });
})();
