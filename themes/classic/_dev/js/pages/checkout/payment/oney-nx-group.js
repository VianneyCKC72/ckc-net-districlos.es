import { syncPaymentCardSelection } from './sync-selection';
import { relocateMhOneyNxWidget } from './oney-mh-widget';

function findMhOneyNxCard(mode) {
    var $placeholder = $('#paymentOption' + mode);
    if ($placeholder.length) {
        return $placeholder.closest('.payment-option-card');
    }

    return $('.payment-option-card').filter(function () {
        var $input = $(this).find('input[data-module-name="mh_oney"]');
        if (!$input.length) {
            return false;
        }
        var title = ($(this).find('.payment-option__title').text() || '').toLowerCase();
        var logo = ($(this).find('img').attr('src') || '').toLowerCase();
        if (mode === '10x') {
            return title.indexOf('10') !== -1 || logo.indexOf('10x') !== -1 || logo.indexOf('11x') !== -1;
        }
        if (mode === '12x') {
            return title.indexOf('12') !== -1 || logo.indexOf('12x') !== -1 || logo.indexOf('13x') !== -1;
        }
        return false;
    }).first();
}

function watchMhOneyNxWidgets($group, $card10x, $card12x) {
    var $slot10x = $group.find('.oney-group__widget-slot[data-oney-widget="10x"]');
    var $slot12x = $group.find('.oney-group__widget-slot[data-oney-widget="12x"]');
    var attempts = 0;

    var timer = setInterval(function () {
        attempts += 1;
        var done10 = relocateMhOneyNxWidget($card10x, '10x', $slot10x);
        var done12 = relocateMhOneyNxWidget($card12x, '12x', $slot12x);

        if ((done10 && done12) || attempts >= 40) {
            clearInterval(timer);
        }
    }, 250);
}

export function initMhOneyNxPaymentGroup() {
    var $container = $('.payment-options');
    if (!$container.length || $container.find('.payment-option-card--oney-nx-group').length) {
        return;
    }

    var $card10x = findMhOneyNxCard('10x');
    var $card12x = findMhOneyNxCard('12x');

    if (!$card10x.length || !$card12x.length) {
        return;
    }

    var $input10x = $card10x.find('input[name="payment-option"]').first();
    var $input12x = $card12x.find('input[name="payment-option"]').first();
    var id10x = $input10x.attr('id');
    var id12x = $input12x.attr('id');
    var groupSelected = $input10x.is(':checked') || $input12x.is(':checked');
    var activeSub = $input12x.is(':checked') ? '12x' : '10x';

    var $group = $(
        '<div class="payment-option-card payment-option-card--oney-nx-group' + (groupSelected ? ' -selected' : '') + '">' +
            '<div class="payment-option oney-group__header">' +
                '<span class="custom-radio">' +
                    '<input class="ps-shown-by-js oney-nx-group__parent-input" type="radio" name="oney-nx-group-parent" value="1"' + (groupSelected ? ' checked' : '') + '>' +
                    '<span></span>' +
                '</span>' +
                '<label class="oney-group__parent-label">' +
                    '<span class="payment-option__row">' +
                        '<span class="payment-option__text">' +
                            '<span class="payment-option__title">Payer par <strong>10x</strong> ou <strong>12x</strong> sans frais</span>' +
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
                    '<div class="oney-group__sub' + (activeSub === '10x' && groupSelected ? ' is-active' : '') + '" data-oney-nx-sub="10x">' +
                        '<label class="oney-group__sub-header">' +
                            '<span class="custom-radio">' +
                                '<input type="radio" name="oney-nx-group-sub" value="10x"' + (activeSub === '10x' && groupSelected ? ' checked' : '') + '>' +
                                '<span></span>' +
                            '</span>' +
                            '<span class="oney-group__sub-title">Payer par <strong>10x</strong><br>sans frais</span>' +
                        '</label>' +
                        '<div class="oney-group__widget-slot" data-oney-widget="10x"></div>' +
                    '</div>' +
                    '<div class="oney-group__sub' + (activeSub === '12x' && groupSelected ? ' is-active' : '') + '" data-oney-nx-sub="12x">' +
                        '<label class="oney-group__sub-header">' +
                            '<span class="custom-radio">' +
                                '<input type="radio" name="oney-nx-group-sub" value="12x"' + (activeSub === '12x' && groupSelected ? ' checked' : '') + '>' +
                                '<span></span>' +
                            '</span>' +
                            '<span class="oney-group__sub-title">Payer par <strong>12x</strong><br>sans frais</span>' +
                        '</label>' +
                        '<div class="oney-group__widget-slot" data-oney-widget="12x"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="oney-nx-group__sources"></div>' +
            '</div>' +
        '</div>'
    );

    $card10x.addClass('--oney-nx-source').attr('data-oney-nx-role', '10x');
    $card12x.addClass('--oney-nx-source').attr('data-oney-nx-role', '12x');

    $group.insertBefore($card10x);
    $group.find('.oney-nx-group__sources').append($card10x).append($card12x);

    $group.find('.--oney-nx-source .js-additional-information').removeClass('ps-hidden');
    watchMhOneyNxWidgets($group, $card10x, $card12x);

    function selectOneyNxSub(sub) {
        var $target = sub === '12x' ? $input12x : $input10x;
        if (!$target.is(':checked')) {
            $target.prop('checked', true).trigger('change');
        }
        $group.find('.oney-group__sub').removeClass('is-active');
        $group.find('.oney-group__sub[data-oney-nx-sub="' + sub + '"]').addClass('is-active');
        $group.find('input[name="oney-nx-group-sub"][value="' + sub + '"]').prop('checked', true);
        syncPaymentCardSelection();
    }

    $group.on('click', '.oney-group__parent-label, .oney-nx-group__parent-input', function (e) {
        e.preventDefault();
        var currentSub = $group.find('input[name="oney-nx-group-sub"]:checked').val() || '10x';
        selectOneyNxSub(currentSub);
    });

    $group.on('click', '.oney-group__sub-header', function (e) {
        if ($(e.target).closest('a').length) {
            return;
        }
        e.preventDefault();
        selectOneyNxSub($(this).closest('.oney-group__sub').data('oney-nx-sub'));
    });

    $group.on('change', 'input[name="oney-nx-group-sub"]', function () {
        selectOneyNxSub($(this).val());
    });

    $(document).on('change.oneyNxGroup', '#' + id10x + ', #' + id12x, function () {
        if (!$(this).is(':checked')) {
            return;
        }
        var sub = $(this).attr('id') === id12x ? '12x' : '10x';
        $group.find('.oney-group__sub').removeClass('is-active');
        $group.find('.oney-group__sub[data-oney-nx-sub="' + sub + '"]').addClass('is-active');
        $group.find('input[name="oney-nx-group-sub"][value="' + sub + '"]').prop('checked', true);
    });
}
