import initCheckoutNav from './checkout/nav';
import initCheckoutPayment from './checkout/payment';
import initCheckoutStickyCart from './checkout/sticky-cart';

$(document).ready(function () {
    initCheckoutNav();
    initCheckoutPayment();
    initCheckoutStickyCart();
});
