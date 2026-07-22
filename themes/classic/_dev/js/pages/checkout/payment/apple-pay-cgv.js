/**
 * Apple Pay (HiPay) : le bouton ne doit pas être cliquable
 * tant que les CGV ne sont pas acceptées.
 *
 * HiPay gère déjà hide/show via checkTermeOfService(), mais notre
 * clone CGV dans le récap + le rendu SDK rendent le bouton encore
 * cliquable. On force pointer-events + resync HiPay.
 */

function areCheckoutCgvAccepted() {
    var $required = $('#conditions-to-approve input[type="checkbox"][required]');
    if (!$required.length) {
        return true;
    }
    return $required.toArray().every(function (el) {
        return el.checked;
    });
}

function getApplePayButton() {
    return $('#apple-pay-button');
}

function isApplePayPaymentSelected() {
    var $checked = $('input[name="payment-option"]:checked');
    if (!$checked.length) {
        return false;
    }
    var formId = $('#pay-with-' + $checked.attr('id') + '-form form').attr('id') || '';
    var title = ($checked.closest('.payment-option-card').find('.payment-option__title').text() || '').toLowerCase();
    return formId === 'applepay-hipay' || title.indexOf('apple') !== -1;
}

/**
 * Resynchronise la référence globale HiPay + leur checkTermeOfService.
 */
function syncHipayApplePayTerms() {
    try {
        if (typeof window.checkbox !== 'undefined' || typeof checkbox !== 'undefined') {
            var el = document.querySelector('#conditions-to-approve input[id^="conditions_to_approve"]');
            if (el) {
                if (typeof window.checkbox !== 'undefined') {
                    window.checkbox = el;
                }
                // Variable non-window dans le scope du script inline HiPay
                try {
                    checkbox = el;
                } catch (e) {
                    /* ignore */
                }
            }
        }
        if (typeof checkTermeOfService === 'function') {
            checkTermeOfService();
        }
    } catch (e) {
        /* ignore */
    }
}

export function syncApplePayWithCgv() {
    var $btn = getApplePayButton();
    if (!$btn.length) {
        return;
    }

    var accepted = areCheckoutCgvAccepted();
    var appleSelected = isApplePayPaymentSelected();

    syncHipayApplePayTerms();

    $btn.toggleClass('apple-pay-cgv-disabled', !accepted);
    $btn.attr('aria-disabled', accepted ? 'false' : 'true');

    if (!accepted) {
        $btn.css('pointer-events', 'none');
        $btn.find('apple-pay-button, button, iframe, div').css('pointer-events', 'none');
        // Garde le message HiPay si Apple Pay est sélectionné
        if (appleSelected) {
            var $err = $('#apple-pay-error-message');
            var $tos = $('#apple-pay-termes-of-service-error-message');
            if ($err.length && $tos.length) {
                $err.css('display', 'inline').text($tos.text());
            }
        }
    } else {
        $btn.css('pointer-events', '');
        $btn.find('apple-pay-button, button, iframe, div').css('pointer-events', '');
        if (appleSelected) {
            $btn.show();
            $('#apple-pay-error-message').hide();
        }
    }
}

export function bindApplePayCgvGuard() {
    var schedule = function () {
        setTimeout(syncApplePayWithCgv, 0);
        setTimeout(syncApplePayWithCgv, 100);
        setTimeout(syncApplePayWithCgv, 400);
    };

    $(document).on(
        'change',
        '#conditions-to-approve input[type="checkbox"], .js-payment-checkout-actions-clone input[type="checkbox"], input[name="payment-option"]',
        schedule
    );

    // Bloque tout clic / activation tant que CGV non cochées
    $(document).on(
        'click mousedown pointerdown touchstart',
        '#apple-pay-button, #apple-pay-button *',
        function (e) {
            if (areCheckoutCgvAccepted()) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            syncApplePayWithCgv();
            return false;
        }
    );

    // Le SDK HiPay injecte le bouton de façon asynchrone
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function (mutations) {
            var relevant = mutations.some(function (m) {
                if (m.target && m.target.id === 'apple-pay-button') {
                    return true;
                }
                return Array.prototype.some.call(m.addedNodes || [], function (n) {
                    if (!n || n.nodeType !== 1) {
                        return false;
                    }
                    return (
                        n.id === 'apple-pay-button' ||
                        (n.querySelector && n.querySelector('#apple-pay-button'))
                    );
                });
            });
            if (relevant) {
                schedule();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    schedule();
}
