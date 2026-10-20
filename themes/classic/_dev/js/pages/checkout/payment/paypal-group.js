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
                            '<img src="/themes/classic/assets/img/payment/paypal-logos.png" alt="PayPal" width="110" height="28">' +
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

    // Comme Oney : sortir les infos additionnelles du ps-hidden.
    // Sinon le SDK Pay Later (PAYLATER) initialise dans un conteneur display:none,
    // isEligible() échoue, le bouton 4x n'est jamais rendu → overlay vide.
    $group.find('.--paypal-source .js-additional-information').removeClass('ps-hidden').show();

    var $sdkHost = $('<div class="paypal-group__sdk-host" aria-hidden="true"></div>');
    $group.append($sdkHost);
    var $cashBtnHost = $cashCard.find('[paypal-button-container]').first();
    var $bnplBtnHost = $bnplCard.find('[paypal-bnpl-button-container]').first();
    if ($cashBtnHost.length) {
        $sdkHost.append($cashBtnHost);
    }
    if ($bnplBtnHost.length) {
        $sdkHost.append($bnplBtnHost);
    }

    function bnplButtonIsReady() {
        var $btn = $('[paypal-bnpl-button-container]').first();
        return $btn.length > 0 && $btn.find('iframe, .paypal-buttons, .paypal-button-row').length > 0;
    }

    function ensureBnplButtonRendered() {
        if (bnplButtonIsReady()) {
            return true;
        }
        if (typeof window.BNPL === 'undefined' || typeof window.totPaypalBnplSdkButtons === 'undefined') {
            return false;
        }
        var $btn = $('[paypal-bnpl-button-container]').first();
        if (!$btn.length) {
            return false;
        }
        try {
            $btn.empty();
            // Réaffiche l'option si le 1er init l'avait masquée (not eligible)
            $bnplInput.closest('.payment-option').css('display', '');
            if (typeof BNPL.init === 'function') {
                BNPL.init();
            }
            if (typeof BNPL.initButton === 'function') {
                BNPL.initButton();
            }
        } catch (e) {
            // eslint-disable-next-line no-console
            console.warn('[paypal-group] BNPL re-init failed', e);
            return false;
        }
        return bnplButtonIsReady();
    }

    function syncBnplAvailability() {
        var ready = bnplButtonIsReady();
        var $bnplSub = $group.find('.paypal-group__sub--bnpl');
        if (ready) {
            $bnplSub.removeClass('is-unavailable').show();
            $group.find('.paypal-group__parent-label .payment-option__desc')
                .text('Au comptant ou quatre fois sans frais');
            return;
        }
        // Après tentatives : masquer le 4x plutôt que laisser un clic mort
        if ($group.data('bnpl-retry-done')) {
            $bnplSub.addClass('is-unavailable').hide();
            if ($bnplInput.is(':checked')) {
                selectPayPalSub('cash');
            }
            $group.find('.paypal-group__parent-label .payment-option__desc')
                .text('Paiement sécurisé via PayPal');
        }
    }

    /**
     * UX Districlos : le bouton SDK PayPal est superposé invisiblement
     * sur « PASSER COMMANDE » (clic réel = PayPal, pas de clic programmatique iframe).
     * Un seul bouton SDK → on le repositionne sur le CTA survolé (bas OU récap).
     */
    var overlayTargetEl = null;

    function getBottomPassOrderSubmit() {
        return $('#payment-confirmation .payment-confirmation__submit').first();
    }

    function getClonePassOrderSubmit() {
        var $clone = $('.js-payment-checkout-actions-clone .payment-confirmation__submit');
        if ($clone.length && $clone.closest('.js-cart-summary-payment-actions').hasClass('is-visible')) {
            return $clone.first();
        }
        return $();
    }

    function getPassOrderSubmit() {
        if (overlayTargetEl && document.body.contains(overlayTargetEl)) {
            var $preferred = $(overlayTargetEl);
            if ($preferred.is(':visible')) {
                return $preferred;
            }
        }
        // Par défaut : CTA bas (zone paiement), sinon clone récap
        var $bottom = getBottomPassOrderSubmit();
        if ($bottom.length && $bottom.is(':visible')) {
            return $bottom;
        }
        return getClonePassOrderSubmit();
    }

    function parkSdkButton($el) {
        if (!$el || !$el.length) {
            return;
        }
        $el.css({
            position: 'absolute',
            top: '0',
            left: '-9999px',
            width: '200px',
            height: '40px',
            opacity: '0',
            zIndex: '0',
            display: 'block',
            overflow: 'hidden',
            margin: '0',
            padding: '0',
            pointerEvents: 'none'
        });
    }

    function clearPayPalOverlayStyles($el) {
        $el.css({
            position: '',
            top: '',
            left: '',
            width: '',
            height: '',
            opacity: '',
            zIndex: '',
            display: '',
            overflow: '',
            margin: '',
            padding: '',
            pointerEvents: ''
        });
    }

    /**
     * Le module PayPal met #payment-confirmation en visibility:hidden
     * dès qu'une option paypal/paypal_bnpl est cochée. On le force visible
     * pour garder « PASSER COMMANDE » (l'overlay SDK se place dessus).
     */
    function forcePassOrderVisible(paypalActive) {
        var $conf = $('#payment-confirmation');
        if (paypalActive) {
            $conf.addClass('paypal-pass-order-visible').css('visibility', 'visible');
            $conf.find('.payment-confirmation__submit').css('visibility', 'visible').show();
            $('.js-payment-checkout-actions-clone .payment-confirmation__submit')
                .css('visibility', 'visible')
                .show();
        } else {
            $conf.removeClass('paypal-pass-order-visible').css('visibility', '');
            $conf.find('.payment-confirmation__submit').css('visibility', '');
        }
    }

    function placePayPalOverlay() {
        var paypalActive = $cashInput.is(':checked') || $bnplInput.is(':checked');
        var $cashBtn = $('[paypal-button-container]');
        var $bnplBtn = $('[paypal-bnpl-button-container]');

        $('[paypal-wrong-button-message], [paypal-ec-wrong-button-message]').hide();
        forcePassOrderVisible(paypalActive);

        if (!paypalActive) {
            parkSdkButton($cashBtn);
            parkSdkButton($bnplBtn);
            return;
        }

        // Ne pas appeler showElementsIfChecked ici : ça affiche le bouton
        // Pay Later en clair dans le groupe avant le repositionnement.

        var $submit = getPassOrderSubmit();
        if (!$submit.length) {
            parkSdkButton($cashBtn);
            parkSdkButton($bnplBtn);
            return;
        }

        var rect = $submit[0].getBoundingClientRect();
        var disabled = $submit.hasClass('disabled') || $submit.prop('disabled');
        var overlayCss = {
            position: 'fixed',
            top: rect.top + 'px',
            left: rect.left + 'px',
            width: Math.max(rect.width, 150) + 'px',
            height: Math.max(rect.height, 35) + 'px',
            opacity: 0.01,
            zIndex: 10050,
            overflow: 'hidden',
            margin: 0,
            padding: 0,
            pointerEvents: disabled ? 'none' : 'auto',
            display: 'block'
        };

        if ($bnplInput.is(':checked')) {
            parkSdkButton($cashBtn);
            $bnplBtn.css(overlayCss);
            $bnplBtn.find('iframe, .paypal-buttons, .paypal-button-row').css({
                width: '100%',
                height: '100%',
                maxWidth: 'none'
            });
        } else {
            parkSdkButton($bnplBtn);
            $cashBtn.css(overlayCss);
            $cashBtn.find('iframe, .paypal-buttons, .paypal-button-row').css({
                width: '100%',
                height: '100%',
                maxWidth: 'none'
            });
        }
    }

    function syncPayPalModuleUi() {
        placePayPalOverlay();
    }

    function selectPayPalSub(sub) {
        if (sub === 'bnpl') {
            ensureBnplButtonRendered();
            if (!bnplButtonIsReady() && $group.data('bnpl-retry-done')) {
                sub = 'cash';
            }
        }
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
        // Laisser le core / PaypalTools appliquer display, puis overlay
        setTimeout(syncPayPalModuleUi, 30);
        setTimeout(syncPayPalModuleUi, 200);
    }

    $group.on('click', '.paypal-group__parent-label, .paypal-group__parent-input', function (e) {
        e.preventDefault();
        var currentSub = $group.find('input[name="paypal-group-sub"]:checked').val() || 'cash';
        selectPayPalSub(currentSub);
    });

    $group.on('change', 'input[name="paypal-group-sub"]', function () {
        selectPayPalSub($(this).val());
    });

    $(document).on('change.paypalGroup', '#' + cashId + ', #' + bnplId, function () {
        if (!$(this).is(':checked')) {
            syncPayPalModuleUi();
            return;
        }
        var sub = $(this).data('module-name') === 'paypal_bnpl' ? 'bnpl' : 'cash';
        $group.find('.paypal-group__sub').removeClass('is-active');
        $group.find('.paypal-group__sub[data-paypal-sub="' + sub + '"]').addClass('is-active');
        $group.find('input[name="paypal-group-sub"][value="' + sub + '"]').prop('checked', true);
        setTimeout(syncPayPalModuleUi, 30);
        setTimeout(syncPayPalModuleUi, 200);
    });

    // Empêche uniquement les alertes module ; le vrai clic passe par l'overlay SDK
    document.addEventListener('click', function (event) {
        var btn = event.target.closest('#payment-confirmation button, .payment-confirmation__submit');
        if (!btn) {
            return;
        }
        var moduleName = ($('input[name="payment-option"]:checked').data('module-name') || '').toString();
        if (moduleName !== 'paypal' && moduleName !== 'paypal_bnpl') {
            return;
        }
        // Si le clic vient du bouton PS (pas de l'overlay), on ignore —
        // l'utilisateur doit cliquer la zone (overlay PayPal par-dessus).
        // On bloque juste l'alerte module si elle se déclenche.
        setTimeout(function () {
            $('[paypal-wrong-button-message], [paypal-ec-wrong-button-message]').hide();
        }, 0);
    }, true);

    $(document).on('paypal-after-init-shortcut-button paypal-after-init-bnpl-button', function () {
        syncBnplAvailability();
        setTimeout(syncPayPalModuleUi, 50);
        setTimeout(syncPayPalModuleUi, 300);
    });

    // Repositionne l'overlay sur le CTA survolé (bas OU récap à droite)
    $(document).on(
        'mouseenter.paypalOverlay focusin.paypalOverlay',
        '#payment-confirmation .payment-confirmation__submit, .js-payment-checkout-actions-clone .payment-confirmation__submit',
        function () {
            if (!$cashInput.is(':checked') && !$bnplInput.is(':checked')) {
                return;
            }
            overlayTargetEl = this;
            placePayPalOverlay();
        }
    );

    $(window).on('scroll.paypalOverlay resize.paypalOverlay', function () {
        if ($cashInput.is(':checked') || $bnplInput.is(':checked')) {
            placePayPalOverlay();
        }
    });

    $(document).on(
        'change.paypalOverlay',
        '#conditions-to-approve input[type="checkbox"], .js-conditions-to-approve-summary input[type="checkbox"], input[name="payment-option"]',
        function () {
            setTimeout(syncPayPalModuleUi, 50);
        }
    );

    // Le module écoute « input » sur .payment-options et remet visibility:hidden :
    // on re-force visible juste après (même tick + frame suivante).
    $container.on('input.paypalOverlayKeepVisible', function () {
        if (!$cashInput.is(':checked') && !$bnplInput.is(':checked')) {
            return;
        }
        forcePassOrderVisible(true);
        requestAnimationFrame(function () {
            forcePassOrderVisible(true);
            placePayPalOverlay();
        });
    });

    setTimeout(syncPayPalModuleUi, 100);
    setTimeout(function () {
        ensureBnplButtonRendered();
        syncBnplAvailability();
        syncPayPalModuleUi();
    }, 500);
    setTimeout(function () {
        ensureBnplButtonRendered();
        $group.data('bnpl-retry-done', true);
        syncBnplAvailability();
        syncPayPalModuleUi();
    }, 1500);
}
