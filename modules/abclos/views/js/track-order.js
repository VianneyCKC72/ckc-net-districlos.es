/**
 * AbClos — goal « revenue » sur la page confirmation de commande.
 * 1) window.abclosOrder (injecté PHP, fiable)
 * 2) fallback data-order (thème Classic)
 * Dédup localStorage par référence commande ; skip preview.
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
        // orderRef = 1 conversion + montant par commande (pas 1 seule fois / visiteur)
        window.abclos.track(GOAL, { value: value, orderRef: reference });
        try {
          localStorage.setItem(storageKey(reference), '1');
        } catch (storageErr) {
          /* ignore */
        }
        if (window.console && console.debug) {
          console.debug('[AbClos] track', GOAL, {
            value: value,
            orderRef: reference,
          });
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
      if (window.console && console.debug) {
        console.debug('[AbClos] revenue: pas de données commande sur cette page');
      }
      return;
    }

    try {
      if (localStorage.getItem(storageKey(hit.reference))) {
        if (window.console && console.debug) {
          console.debug('[AbClos] revenue déjà envoyé pour', hit.reference);
        }
        return;
      }
    } catch (e) {
      /* continue */
    }

    trackRevenue(hit.value, hit.reference);
  });
})();
