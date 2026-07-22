import { formatEuro, getCheckoutTotalAmount } from './utils';
import { syncPaymentCardSelection } from './sync-selection';

export function buildPayPalInstallmentHtml() {
    var total = getCheckoutTotalAmount();
    if (!total) {
        return '';
    }
    var installment = Math.round((total / 4) * 100) / 100;
    return (
        '<div class="paypal-group__installments">' +
            '<div class="paypal-group__installments-row paypal-group__installments-row--total">' +
                '<span>Montant total financé</span>' +
                '<strong>' + formatEuro(total) + '</strong>' +
            '</div>' +
            '<div class="paypal-group__installments-row">' +
                '<span>Apport de</span>' +
                '<strong>' + formatEuro(installment) + '</strong>' +
            '</div>' +
            '<div class="paypal-group__installments-row">' +
                '<span>Suivi de 3 mensualités de</span>' +
                '<strong>' + formatEuro(installment) + '</strong>' +
            '</div>' +
        '</div>'
    );
}

export function initPayPalPaymentGroup() {
    var $container = $('.payment-options');
    if (!$container.length || $container.find('.payment-option-card--paypal-group').length) {
        return;
    }

    var $cashCard = $container.find('.payment-option-card').has('input[data-module-name="paypal"]').first();
    var $bnplCard = $container.find('.payment-option-card').has('input[data-module-name="paypal_bnpl"]').first();

    if (!$cashCard.length && !$bnplCard.length) {
        return;
    }

    // Une seule option PayPal : pas de regroupement, juste le logo / libellés existants
    if (!$cashCard.length || !$bnplCard.length) {
        return;
    }

    var $cashInput = $cashCard.find('input[name="payment-option"]').first();
    var $bnplInput = $bnplCard.find('input[name="payment-option"]').first();
    var cashId = $cashInput.attr('id');
    var bnplId = $bnplInput.attr('id');
    var groupSelected = $cashInput.is(':checked') || $bnplInput.is(':checked');
    var activeSub = $bnplInput.is(':checked') ? 'bnpl' : 'cash';

    var $group = $(
        '<div class="payment-option-card payment-option-card--paypal-group' + (groupSelected ? ' -selected' : '') + '">' +
            '<div class="payment-option paypal-group__header">' +
                '<span class="custom-radio">' +
                    '<input class="ps-shown-by-js paypal-group__parent-input" type="radio" name="paypal-group-parent" value="1"' + (groupSelected ? ' checked' : '') + '>' +
                    '<span></span>' +
                '</span>' +
                '<label class="paypal-group__parent-label">' +
                    '<span class="payment-option__row">' +
                        '<span class="payment-option__text">' +
                            '<span class="payment-option__title">Payer avec PayPal</span>' +
                            '<span class="payment-option__desc">Au comptant ou quatre fois sans frais</span>' +
                        '</span>' +
                        '<span class="payment-option__logos">' +
                            '<img src="/themes/classic/assets/img/payment/paypal-logos.png" alt="PayPal" loading="lazy">' +
                        '</span>' +
                    '</span>' +
                '</label>' +
            '</div>' +
            '<div class="paypal-group__subs' + (groupSelected ? ' is-open' : '') + '">' +
                '<label class="paypal-group__sub' + (activeSub === 'cash' && groupSelected ? ' is-active' : '') + '" data-paypal-sub="cash">' +
                    '<span class="custom-radio">' +
                        '<input type="radio" name="paypal-group-sub" value="cash"' + (activeSub === 'cash' && groupSelected ? ' checked' : '') + '>' +
                        '<span></span>' +
                    '</span>' +
                    '<span class="paypal-group__sub-label">Au comptant</span>' +
                '</label>' +
                '<label class="paypal-group__sub paypal-group__sub--bnpl' + (activeSub === 'bnpl' && groupSelected ? ' is-active' : '') + '" data-paypal-sub="bnpl">' +
                    '<span class="custom-radio">' +
                        '<input type="radio" name="paypal-group-sub" value="bnpl"' + (activeSub === 'bnpl' && groupSelected ? ' checked' : '') + '>' +
                        '<span></span>' +
                    '</span>' +
                    '<span class="paypal-group__sub-content">' +
                        '<span class="paypal-group__badge">4x</span>' +
                        '<span class="paypal-group__bnpl-details">' + buildPayPalInstallmentHtml() + '</span>' +
                    '</span>' +
                '</label>' +
                '<div class="paypal-group__sources"></div>' +
            '</div>' +
        '</div>'
    );

    $cashCard.addClass('--paypal-source').attr('data-paypal-role', 'cash');
    $bnplCard.addClass('--paypal-source').attr('data-paypal-role', 'bnpl');

    $group.insertBefore($cashCard);
    $group.find('.paypal-group__sources').append($cashCard).append($bnplCard);

    /**
     * UX Districlos : CTA unique = PASSER COMMANDE.
     * Les boutons SDK PayPal restent dans le DOM (cachés) et sont déclenchés
     * quand on clique sur PASSER COMMANDE.
     */
    function syncPayPalModuleUi() {
        if (window.PaypalTools) {
            if (typeof PaypalTools.hideElementTillChecked === 'function') {
                PaypalTools.hideElementTillChecked();
            }
            if (typeof PaypalTools.showElementsIfChecked === 'function') {
                PaypalTools.showElementsIfChecked();
            }
        }

        var paypalActive = $cashInput.is(':checked') || $bnplInput.is(':checked');
        var $conf = $('#payment-confirmation');

        // Toujours afficher PASSER COMMANDE (le module PayPal le masque via visibility)
        $conf.css('visibility', 'visible');
        $('.js-payment-checkout-actions-clone .payment-confirmation__submit').show();

        // Garder le bon conteneur SDK affiché pour le rendu PayPal, mais hors écran (CSS)
        if ($cashInput.is(':checked')) {
            $('[paypal-button-container]').css('display', 'block');
            $('[paypal-bnpl-button-container]').css('display', 'none');
        } else if ($bnplInput.is(':checked')) {
            $('[paypal-bnpl-button-container]').css('display', 'block');
            $('[paypal-button-container]').css('display', 'none');
        }

        $('[paypal-wrong-button-message], [paypal-ec-wrong-button-message]').hide();

        if (!paypalActive) {
            $conf.css('visibility', '');
        }
    }

    function getActivePayPalSdkContainer() {
        if ($bnplInput.is(':checked')) {
            return document.querySelector('[paypal-bnpl-button-container]');
        }
        if ($cashInput.is(':checked')) {
            return document.querySelector('[paypal-button-container]');
        }
        return null;
    }

    function clickPayPalSdkButton() {
        var container = getActivePayPalSdkContainer();
        if (!container) {
            return false;
        }

        // S'assurer que le conteneur est rendu (PayPal refuse display:none)
        container.style.display = 'block';

        var target =
            container.querySelector('iframe') ||
            container.querySelector('.paypal-buttons') ||
            container.querySelector('[role="link"]') ||
            container.querySelector('[role="button"]') ||
            container.firstElementChild;

        if (!target) {
            return false;
        }

        target.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, view: window }));
        if (typeof target.click === 'function') {
            target.click();
        }
        return true;
    }

    function onPassOrderClick(event) {
        var moduleName = ($('input[name="payment-option"]:checked').data('module-name') || '').toString();
        if (moduleName !== 'paypal' && moduleName !== 'paypal_bnpl') {
            return;
        }

        // Empêche l'alerte module « Veuillez cliquer sur le bouton PayPal »
        event.preventDefault();
        event.stopImmediatePropagation();

        var $btn = $(event.currentTarget);
        if ($btn.hasClass('disabled') || $btn.prop('disabled')) {
            return;
        }

        $('[paypal-wrong-button-message], [paypal-ec-wrong-button-message]').hide();

        if (!clickPayPalSdkButton()) {
            // Fallback : laisser un message discret si le SDK n'est pas prêt
            window.alert('Le paiement PayPal n\'est pas encore prêt. Réessayez dans une seconde.');
        }
    }

    function selectPayPalSub(sub) {
        var $target = sub === 'bnpl' ? $bnplInput : $cashInput;
        if (!$target.is(':checked')) {
            $target.prop('checked', true).trigger('change');
            if ($target[0]) {
                $target[0].dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
        $group.find('.paypal-group__sub').removeClass('is-active');
        $group.find('.paypal-group__sub[data-paypal-sub="' + sub + '"]').addClass('is-active');
        $group.find('input[name="paypal-group-sub"][value="' + sub + '"]').prop('checked', true);
        syncPaymentCardSelection();
        syncPayPalModuleUi();
    }

    $group.on('click', '.paypal-group__parent-label, .paypal-group__parent-input', function (e) {
        e.preventDefault();
        var currentSub = $group.find('input[name="paypal-group-sub"]:checked').val() || 'cash';
        selectPayPalSub(currentSub);
    });

    $group.on('change', 'input[name="paypal-group-sub"]', function () {
        selectPayPalSub($(this).val());
    });

    // Si une option PayPal source est cochée autrement, synchroniser l'UI groupe
    $(document).on('change.paypalGroup', '#' + cashId + ', #' + bnplId, function () {
        if (!$(this).is(':checked')) {
            syncPayPalModuleUi();
            return;
        }
        var sub = $(this).data('module-name') === 'paypal_bnpl' ? 'bnpl' : 'cash';
        $group.find('.paypal-group__sub').removeClass('is-active');
        $group.find('.paypal-group__sub[data-paypal-sub="' + sub + '"]').addClass('is-active');
        $group.find('input[name="paypal-group-sub"][value="' + sub + '"]').prop('checked', true);
        syncPayPalModuleUi();
    });

    // Capture : avant les handlers du module PayPal
    document.addEventListener('click', function (event) {
        var btn = event.target.closest(
            '#payment-confirmation button, .payment-confirmation__submit, .js-payment-checkout-actions-clone .payment-confirmation__submit'
        );
        if (!btn) {
            return;
        }
        onPassOrderClick({
            preventDefault: function () { event.preventDefault(); },
            stopImmediatePropagation: function () {
                event.stopPropagation();
                event.stopImmediatePropagation();
            },
            currentTarget: btn
        });
    }, true);

    $(document).on('paypal-after-init-shortcut-button paypal-after-init-bnpl-button', function () {
        setTimeout(syncPayPalModuleUi, 50);
    });

    if ($cashInput.is(':checked') || $bnplInput.is(':checked')) {
        setTimeout(syncPayPalModuleUi, 100);
    }
    setTimeout(syncPayPalModuleUi, 400);
}
