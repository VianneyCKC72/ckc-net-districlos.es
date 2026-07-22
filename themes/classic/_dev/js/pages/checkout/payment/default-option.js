import { syncPaymentCardSelection } from './sync-selection';

export function selectDefaultPaymentOption() {
    var $checked = $('input[name="payment-option"]:checked');
    if ($checked.length) {
        return;
    }

    var $options = $('input[name="payment-option"]').filter(function () {
        var $input = $(this);
        if (!$input.is(':visible') && !$input.closest('.payment-option-card').is(':visible')) {
            // Sources regroupées (PayPal/Oney) restent sélectionnables
            if (
                !$input.closest('.--paypal-source, .--oney-source, .--oney-nx-source, .--oney-long-source').length
            ) {
                return false;
            }
        }
        var module = ($input.data('module-name') || '').toString().toLowerCase();
        var title = ($input.closest('.payment-option-card').find('.payment-option__title').text() || '').toLowerCase();
        // Ne pas précocher devis / téléphone
        if (
            module === 'opartdevis' ||
            title.indexOf('devis') !== -1 ||
            module === 'ckc_paywithphone' ||
            title.indexOf('éléphone') !== -1
        ) {
            return false;
        }
        return true;
    });

    if (!$options.length) {
        return;
    }

    // Priorité : carte bancaire PayPlug / CB
    var $preferred = $options.filter(function () {
        var module = ($(this).data('module-name') || '').toString().toLowerCase();
        var title = ($(this).closest('.payment-option-card').find('.payment-option__title').text() || '').toLowerCase();
        return (
            (module === 'payplug' && (title.indexOf('carte') !== -1 || title.indexOf('card') !== -1)) ||
            module === 'credit_card' ||
            title.indexOf('carte bancaire') !== -1 ||
            title.indexOf('carte de crédit') !== -1
        );
    }).first();

    var $target = $preferred.length ? $preferred : $options.first();
    $target.prop('checked', true).trigger('change');
    syncPaymentCardSelection();
}
