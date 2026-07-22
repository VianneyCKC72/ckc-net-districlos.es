export function formatEuro(amount) {
    return amount.toLocaleString('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' €';
}

export function getCheckoutTotalAmount() {
    var total = null;
    if (typeof prestashop !== 'undefined' && prestashop.cart && prestashop.cart.totals && prestashop.cart.totals.total) {
        var raw = prestashop.cart.totals.total.amount;
        if (typeof raw === 'number') {
            total = raw;
        } else if (typeof raw === 'string') {
            total = parseFloat(raw.replace(',', '.'));
        }
    }
    if (total === null || isNaN(total)) {
        var $total = $('.cart-summary-line.cart-total .value, .cart-total .value').first();
        if ($total.length) {
            var text = $total.text().replace(/[^\d,.-]/g, '').replace(',', '.');
            total = parseFloat(text);
        }
    }
    return (!total || isNaN(total)) ? null : total;
}
