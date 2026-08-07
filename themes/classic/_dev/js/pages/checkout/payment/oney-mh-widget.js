/**
 * Déplacement + chargement des widgets mh_oney (10x/12x/24x/36x/48x).
 * Important : ne PAS rappeler attachEventsAndUpdateOptions en boucle —
 * chaque appel relance loadOneyWidget sur tous les placeholders vides
 * et fait planter / ralentir les simulations.
 */

var oneyCheckoutLoadTimer = null;

function requestOneyCheckoutLoad() {
    if (oneyCheckoutLoadTimer) {
        return;
    }
    oneyCheckoutLoadTimer = setTimeout(function () {
        oneyCheckoutLoadTimer = null;
        if (typeof window.attachEventsAndUpdateOptions === 'function') {
            window.attachEventsAndUpdateOptions();
        }
    }, 100);
}

export function relocateMhOneyNxWidget($source, mode, $slot) {
    if ($slot.data('oney-widget-ready') && $slot.find('.oney-checkoutSection').length) {
        return true;
    }

    var $placeholder = $source.find('#paymentOption' + mode);
    if (!$placeholder.length) {
        $placeholder = $source.find('.js-oney-checkout-placeholder[data-oney-mode="' + mode + '"]').first();
    }
    if (!$placeholder.length) {
        $placeholder = $slot.find(
            '#paymentOption' + mode + ', .js-oney-checkout-placeholder[data-oney-mode="' + mode + '"]'
        ).first();
    }
    if (!$placeholder.length) {
        return false;
    }

    $source.find('.js-additional-information').removeClass('ps-hidden').show();

    if (!$slot.find($placeholder).length) {
        $placeholder.appendTo($slot);
    }

    // Une seule demande de load après déplacement (debounce global)
    if (!$slot.data('oney-widget-load-requested')) {
        $slot.data('oney-widget-load-requested', true);
        requestOneyCheckoutLoad();
    }

    if ($placeholder.find('.oney-checkoutSection').length) {
        $slot.data('oney-widget-ready', true);
        return true;
    }

    return false;
}
