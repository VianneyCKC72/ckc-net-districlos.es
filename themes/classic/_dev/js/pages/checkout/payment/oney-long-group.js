import { syncPaymentCardSelection } from './sync-selection';
import { relocateMhOneyNxWidget } from './oney-mh-widget';

function findMhOneyLongCard(mode) {
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
        if (mode === '24x') {
            return title.indexOf('24') !== -1 || logo.indexOf('24x') !== -1 || logo.indexOf('25x') !== -1;
        }
        if (mode === '36x') {
            return title.indexOf('36') !== -1 || logo.indexOf('36x') !== -1 || logo.indexOf('37x') !== -1;
        }
        if (mode === '48x') {
            return title.indexOf('48') !== -1 || logo.indexOf('48x') !== -1 || logo.indexOf('49x') !== -1;
        }
        return false;
    }).first();
}

function watchMhOneyLongWidgets($group, $card24x, $card36x, $card48x) {
    var $slot24x = $group.find('.oney-group__widget-slot[data-oney-widget="24x"]');
    var $slot36x = $group.find('.oney-group__widget-slot[data-oney-widget="36x"]');
    var $slot48x = $group.find('.oney-group__widget-slot[data-oney-widget="48x"]');
    var attempts = 0;

    var timer = setInterval(function () {
        attempts += 1;
        var done24 = relocateMhOneyNxWidget($card24x, '24x', $slot24x);
        var done36 = relocateMhOneyNxWidget($card36x, '36x', $slot36x);
        var done48 = relocateMhOneyNxWidget($card48x, '48x', $slot48x);

        if ((done24 && done36 && done48) || attempts >= 40) {
            clearInterval(timer);
        }
    }, 250);
}

export function initMhOneyLongPaymentGroup() {
    var $container = $('.payment-options');
    if (!$container.length || $container.find('.payment-option-card--oney-long-group').length) {
        return;
    }

    var $card24x = findMhOneyLongCard('24x');
    var $card36x = findMhOneyLongCard('36x');
    var $card48x = findMhOneyLongCard('48x');

    if (!$card24x.length || !$card36x.length || !$card48x.length) {
        return;
    }

    var $input24x = $card24x.find('input[name="payment-option"]').first();
    var $input36x = $card36x.find('input[name="payment-option"]').first();
    var $input48x = $card48x.find('input[name="payment-option"]').first();
    var id24x = $input24x.attr('id');
    var id36x = $input36x.attr('id');
    var id48x = $input48x.attr('id');
    var groupSelected = $input24x.is(':checked') || $input36x.is(':checked') || $input48x.is(':checked');
    var activeSub = '24x';
    if ($input48x.is(':checked')) {
        activeSub = '48x';
    } else if ($input36x.is(':checked')) {
        activeSub = '36x';
    }

    var $group = $(
        '<div class="payment-option-card payment-option-card--oney-long-group' + (groupSelected ? ' -selected' : '') + '">' +
            '<div class="payment-option oney-group__header">' +
                '<span class="custom-radio">' +
                    '<input class="ps-shown-by-js oney-long-group__parent-input" type="radio" name="oney-long-group-parent" value="1"' + (groupSelected ? ' checked' : '') + '>' +
                    '<span></span>' +
                '</span>' +
                '<label class="oney-group__parent-label">' +
                    '<span class="payment-option__row">' +
                        '<span class="payment-option__text">' +
                            '<span class="payment-option__title">Payer en <strong>24x</strong>, <strong>36x</strong> ou <strong>48x</strong></span>' +
                            '<span class="payment-option__desc">Crédit long avec Oney</span>' +
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
                '<div class="oney-group__subs-grid oney-group__subs-grid--3">' +
                    '<div class="oney-group__sub' + (activeSub === '24x' && groupSelected ? ' is-active' : '') + '" data-oney-long-sub="24x">' +
                        '<label class="oney-group__sub-header">' +
                            '<span class="custom-radio">' +
                                '<input type="radio" name="oney-long-group-sub" value="24x"' + (activeSub === '24x' && groupSelected ? ' checked' : '') + '>' +
                                '<span></span>' +
                            '</span>' +
                            '<span class="oney-group__sub-title">Payer par <strong>24x</strong></span>' +                                '</label>' +
                        '<div class="oney-group__widget-slot" data-oney-widget="24x"></div>' +
                    '</div>' +
                    '<div class="oney-group__sub' + (activeSub === '36x' && groupSelected ? ' is-active' : '') + '" data-oney-long-sub="36x">' +
                        '<label class="oney-group__sub-header">' +
                            '<span class="custom-radio">' +
                                '<input type="radio" name="oney-long-group-sub" value="36x"' + (activeSub === '36x' && groupSelected ? ' checked' : '') + '>' +
                                '<span></span>' +
                            '</span>' +
                            '<span class="oney-group__sub-title">Payer par <strong>36x</strong></span>' +
                        '</label>' +
                        '<div class="oney-group__widget-slot" data-oney-widget="36x"></div>' +
                    '</div>' +
                    '<div class="oney-group__sub' + (activeSub === '48x' && groupSelected ? ' is-active' : '') + '" data-oney-long-sub="48x">' +
                        '<label class="oney-group__sub-header">' +
                            '<span class="custom-radio">' +
                                '<input type="radio" name="oney-long-group-sub" value="48x"' + (activeSub === '48x' && groupSelected ? ' checked' : '') + '>' +
                                '<span></span>' +
                            '</span>' +
                            '<span class="oney-group__sub-title">Payer par <strong>48x</strong></span>' +
                        '</label>' +
                        '<div class="oney-group__widget-slot" data-oney-widget="48x"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="oney-long-group__sources"></div>' +
            '</div>' +
        '</div>'
    );

    $card24x.addClass('--oney-long-source').attr('data-oney-long-role', '24x');
    $card36x.addClass('--oney-long-source').attr('data-oney-long-role', '36x');
    $card48x.addClass('--oney-long-source').attr('data-oney-long-role', '48x');

    $group.insertBefore($card24x);
    $group.find('.oney-long-group__sources').append($card24x).append($card36x).append($card48x);

    $group.find('.--oney-long-source .js-additional-information').removeClass('ps-hidden');
    watchMhOneyLongWidgets($group, $card24x, $card36x, $card48x);

    function selectOneyLongSub(sub) {
        var $target = $input24x;
        if (sub === '36x') {
            $target = $input36x;
        } else if (sub === '48x') {
            $target = $input48x;
        }
        if (!$target.is(':checked')) {
            $target.prop('checked', true).trigger('change');
        }
        $group.find('.oney-group__sub').removeClass('is-active');
        $group.find('.oney-group__sub[data-oney-long-sub="' + sub + '"]').addClass('is-active');
        $group.find('input[name="oney-long-group-sub"][value="' + sub + '"]').prop('checked', true);
        syncPaymentCardSelection();
    }

    $group.on('click', '.oney-group__parent-label, .oney-long-group__parent-input', function (e) {
        e.preventDefault();
        var currentSub = $group.find('input[name="oney-long-group-sub"]:checked').val() || '24x';
        selectOneyLongSub(currentSub);
    });

    $group.on('click', '.oney-group__sub-header', function (e) {
        if ($(e.target).closest('a').length) {
            return;
        }
        e.preventDefault();
        selectOneyLongSub($(this).closest('.oney-group__sub').data('oney-long-sub'));
    });

    $group.on('change', 'input[name="oney-long-group-sub"]', function () {
        selectOneyLongSub($(this).val());
    });

    $(document).on('change.oneyLongGroup', '#' + id24x + ', #' + id36x + ', #' + id48x, function () {
        if (!$(this).is(':checked')) {
            return;
        }
        var clickedId = $(this).attr('id');
        var sub = '24x';
        if (clickedId === id36x) {
            sub = '36x';
        } else if (clickedId === id48x) {
            sub = '48x';
        }
        $group.find('.oney-group__sub').removeClass('is-active');
        $group.find('.oney-group__sub[data-oney-long-sub="' + sub + '"]').addClass('is-active');
        $group.find('input[name="oney-long-group-sub"][value="' + sub + '"]').prop('checked', true);
    });
}
