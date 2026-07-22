export function relocateMhOneyNxWidget($source, mode, $slot) {
    // Flag OK seulement si le widget est réellement présent dans le slot
    if ($slot.data('oney-widget-moved') && $slot.find('.oney-checkoutSection').length) {
        return true;
    }
    $slot.removeData('oney-widget-moved');

    var $placeholder = $source.find('#paymentOption' + mode);
    if (!$placeholder.length) {
        $placeholder = $source.find('.js-oney-checkout-placeholder[data-oney-mode="' + mode + '"]').first();
    }
    // Déjà déplacé dans le slot mais vidé par le SDK
    if (!$placeholder.length) {
        $placeholder = $slot.find('#paymentOption' + mode + ', .js-oney-checkout-placeholder[data-oney-mode="' + mode + '"]').first();
    }
    if (!$placeholder.length) {
        return false;
    }

    $source.find('.js-additional-information').removeClass('ps-hidden');
    if (!$slot.find($placeholder).length) {
        $placeholder.appendTo($slot);
    }

    if (typeof window.attachEventsAndUpdateOptions === 'function') {
        window.attachEventsAndUpdateOptions();
    }

    if ($placeholder.find('.oney-checkoutSection').length) {
        $slot.data('oney-widget-moved', true);
        return true;
    }

    return false;
}
