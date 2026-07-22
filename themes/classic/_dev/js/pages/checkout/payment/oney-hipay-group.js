import { syncPaymentCardSelection } from './sync-selection';

function findHiPayOneyCard(installments) {
    var productCode = installments === 3 ? '3xcb-no-fees' : '4xcb-no-fees';
    var $form = $('#' + productCode + '-hipay');
    if ($form.length) {
        return $form.closest('.payment-option-card');
    }

    var needle = installments + 'x';
    return $('.payment-option-card').filter(function () {
        var $input = $(this).find('input[data-module-name="local_payment_hipay"]');
        if (!$input.length) {
            return false;
        }
        var title = $(this).find('.payment-option__title').text().toLowerCase();
        return title.indexOf(needle) !== -1;
    }).first();
}

function relocateOneyWidgetFromSource($source, role, $slot) {
    if ($slot.data('oney-widget-moved')) {
        return true;
    }

    var productCode = role === '3x' ? '3xcb-no-fees' : '4xcb-no-fees';
    var $container = $source.find('#hipay-oney-' + productCode + '-widget-container');
    if (!$container.length) {
        $container = $source.find('[id*="hipay-oney"][id*="' + productCode + '"]').first();
    }
    if (!$container.length || (!$container.find('.oney-checkoutSection').length && $container.is('[hidden]'))) {
        return false;
    }

    $container.removeAttr('hidden').appendTo($slot);
    $slot.find('.oney-checkoutSection__legal-notice, .oney-checkout-legal-notice').remove();
    $slot.data('oney-widget-moved', true);
    return true;
}

function watchOneyWidgets($group, $card3x, $card4x) {
    var $slot3x = $group.find('.oney-group__widget-slot[data-oney-widget="3x"]');
    var $slot4x = $group.find('.oney-group__widget-slot[data-oney-widget="4x"]');
    var attempts = 0;

    var timer = setInterval(function () {
        attempts += 1;
        var done3 = relocateOneyWidgetFromSource($card3x, '3x', $slot3x);
        var done4 = relocateOneyWidgetFromSource($card4x, '4x', $slot4x);

        if ((done3 && done4) || attempts >= 40) {
            clearInterval(timer);
        }
    }, 250);
}

export function initOneyPaymentGroup() {
    var $container = $('.payment-options');
    if (!$container.length || $container.find('.payment-option-card--oney-group').length) {
        return;
    }

    var $card3x = findHiPayOneyCard(3);
    var $card4x = findHiPayOneyCard(4);

    if (!$card3x.length && !$card4x.length) {
        return;
    }

    if (!$card3x.length || !$card4x.length) {
        return;
    }

    var $input3x = $card3x.find('input[name="payment-option"]').first();
    var $input4x = $card4x.find('input[name="payment-option"]').first();
    var id3x = $input3x.attr('id');
    var id4x = $input4x.attr('id');
    var groupSelected = $input3x.is(':checked') || $input4x.is(':checked');
    var activeSub = $input4x.is(':checked') ? '4x' : '3x';

    var $group = $(
        '<div class="payment-option-card payment-option-card--oney-group' + (groupSelected ? ' -selected' : '') + '">' +
            '<div class="payment-option oney-group__header">' +
                '<span class="custom-radio">' +
                    '<input class="ps-shown-by-js oney-group__parent-input" type="radio" name="oney-group-parent" value="1"' + (groupSelected ? ' checked' : '') + '>' +
                    '<span></span>' +
                '</span>' +
                '<label class="oney-group__parent-label">' +
                    '<span class="payment-option__row">' +
                        '<span class="payment-option__text">' +
                            '<span class="payment-option__title">Payer par <strong>3x</strong> ou <strong>4x</strong> sans frais</span>' +
                            '<span class="payment-option__desc">Avec votre carte bancaire</span>' +
                        '</span>' +
                        '<span class="payment-option__logos">' +
                            '<img src="/themes/classic/assets/img/payment/oney-logos.png" alt="Oney" loading="lazy">' +
                        '</span>' +
                    '</span>' +
                '</label>' +
            '</div>' +
            '<div class="oney-group__subs' + (groupSelected ? ' is-open' : '') + '">' +
                '<p class="oney-group__legal">UN CRÉDIT VOUS ENGAGE ET DOIT ÊTRE REMBOURSÉ. VÉRIFIEZ VOS CAPACITÉS DE REMBOURSEMENT AVANT DE VOUS ENGAGER.</p>' +
                '<p class="oney-group__redirect">Vous allez être redirigé vers une page de paiement externe afin de finaliser la transaction.</p>' +
                '<div class="oney-group__subs-grid">' +
                    '<div class="oney-group__sub' + (activeSub === '3x' && groupSelected ? ' is-active' : '') + '" data-oney-sub="3x">' +
                        '<label class="oney-group__sub-header">' +
                            '<span class="custom-radio">' +
                                '<input type="radio" name="oney-group-sub" value="3x"' + (activeSub === '3x' && groupSelected ? ' checked' : '') + '>' +
                                '<span></span>' +
                            '</span>' +
                            '<span class="oney-group__sub-title">Payer par <strong>3x</strong><br>sans frais</span>' +
                        '</label>' +
                        '<div class="oney-group__widget-slot" data-oney-widget="3x"></div>' +
                    '</div>' +
                    '<div class="oney-group__sub' + (activeSub === '4x' && groupSelected ? ' is-active' : '') + '" data-oney-sub="4x">' +
                        '<label class="oney-group__sub-header">' +
                            '<span class="custom-radio">' +
                                '<input type="radio" name="oney-group-sub" value="4x"' + (activeSub === '4x' && groupSelected ? ' checked' : '') + '>' +
                                '<span></span>' +
                            '</span>' +
                            '<span class="oney-group__sub-title">Payer par <strong>4x</strong><br>sans frais</span>' +
                        '</label>' +
                        '<div class="oney-group__widget-slot" data-oney-widget="4x"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="oney-group__sources"></div>' +
            '</div>' +
        '</div>'
    );

    $card3x.addClass('--oney-source').attr('data-oney-role', '3x');
    $card4x.addClass('--oney-source').attr('data-oney-role', '4x');

    $group.insertBefore($card3x);
    $group.find('.oney-group__sources').append($card3x).append($card4x);

    $group.find('.--oney-source .js-payment-option-form').removeClass('ps-hidden');
    watchOneyWidgets($group, $card3x, $card4x);

    function selectOneySub(sub) {
        var $target = sub === '4x' ? $input4x : $input3x;
        if (!$target.is(':checked')) {
            $target.prop('checked', true).trigger('change');
        }
        $group.find('.oney-group__sub').removeClass('is-active');
        $group.find('.oney-group__sub[data-oney-sub="' + sub + '"]').addClass('is-active');
        $group.find('input[name="oney-group-sub"][value="' + sub + '"]').prop('checked', true);
        syncPaymentCardSelection();
    }

    $group.on('click', '.oney-group__parent-label, .oney-group__parent-input', function (e) {
        e.preventDefault();
        var currentSub = $group.find('input[name="oney-group-sub"]:checked').val() || '3x';
        selectOneySub(currentSub);
    });

    $group.on('click', '.oney-group__sub-header', function (e) {
        if ($(e.target).closest('a').length) {
            return;
        }
        e.preventDefault();
        selectOneySub($(this).closest('.oney-group__sub').data('oney-sub'));
    });

    $group.on('change', 'input[name="oney-group-sub"]', function () {
        selectOneySub($(this).val());
    });

    $(document).on('change.oneyGroup', '#' + id3x + ', #' + id4x, function () {
        if (!$(this).is(':checked')) {
            return;
        }
        var sub = $(this).attr('id') === id4x ? '4x' : '3x';
        $group.find('.oney-group__sub').removeClass('is-active');
        $group.find('.oney-group__sub[data-oney-sub="' + sub + '"]').addClass('is-active');
        $group.find('input[name="oney-group-sub"][value="' + sub + '"]').prop('checked', true);
    });
}
