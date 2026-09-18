(function () {
  'use strict';

  var RESET_FLAG_KEY = 'dtc_cart_reservation_reset';

  function setResetFlag() {
    try {
      sessionStorage.setItem(RESET_FLAG_KEY, '1');
    } catch (e) {
      // sessionStorage indisponible
    }
  }

  function isAddToCartEvent(event) {
    if (!event || !event.reason) {
      return false;
    }

    return event.reason.linkAction === 'add-to-cart';
  }

  document.addEventListener('click', function (event) {
    if (
      event.target.closest('a[href*="add=1"]')
      || event.target.closest('[data-button-action="add-to-cart"]')
    ) {
      setResetFlag();
    }
  }, true);

  if (typeof prestashop !== 'undefined' && typeof prestashop.on === 'function') {
    prestashop.on('updateCart', function (event) {
      if (isAddToCartEvent(event)) {
        setResetFlag();
      }
    });

    prestashop.on('updatedCart', function (event) {
      if (isAddToCartEvent(event)) {
        setResetFlag();
      }
    });
  }
})();
