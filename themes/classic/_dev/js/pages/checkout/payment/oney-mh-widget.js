export function relocateMhOneyNxWidget($source, mode, $slot) {
    if ($slot.data('oney-widget-moved')) {
        return true;
    }

    var $placeholder = $source.find('#paymentOption' + mode);
    if (!$placeholder.length) {
        $placeholder = $source.find('.js-oney-checkout-placeholder[data-oney-mode="' + mode + '"]').first();
    }
    if (!$placeholder.length) {
        return false;
    }

    $source.find('.js-additional-information').removeClass('ps-hidden');
    $placeholder.appendTo($slot);

    if (typeof window.attachEventsAndUpdateOptions === 'function') {
        window.attachEventsAndUpdateOptions();
    }

    if ($placeholder.find('.oney-checkoutSection').length) {
        $slot.data('oney-widget-moved', true);
        return true;
    }

    return false;
}
