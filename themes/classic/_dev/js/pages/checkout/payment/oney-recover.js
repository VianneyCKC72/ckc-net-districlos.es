/**
 * Correctif modale Oney « En savoir plus » :
 *
 * Cause : la modale est montée dans le widget HiPay (donc dans .checkout-step).
 * core.js écoute les clics sur .checkout-step et appelle makeCurrent(), qui
 * retire -current de toutes les étapes → contenu paiement masqué.
 *
 * Fix : stopPropagation sur la modale / bouton pédagogique, absorption du
 * click-through éventuel, restauration de l'étape paiement à la fermeture.
 */

var oneyPedagogicFromPayment = false;
var absorbNextClick = false;

function isPaymentStepCurrent() {
    var step = document.getElementById('checkout-payment-step');
    return !!(step && (step.classList.contains('-current') || step.classList.contains('js-current-step')));
}

function isOneyUiTarget(target) {
    return !!(
        target &&
        target.closest &&
        target.closest(
            '#showOneyWidgetAsModal, .oney-pedagogicButton, .oney-close, button[aria-label="Close"][title="Close"]'
        )
    );
}

function restorePaymentStep() {
    var step = document.getElementById('checkout-payment-step');
    if (!step) {
        return false;
    }

    document.querySelectorAll('.checkout-step').forEach(function (s) {
        s.classList.remove('-current', 'js-current-step');
    });
    step.classList.add('-current', 'js-current-step');
    step.style.display = '';
    var content = step.querySelector('.content');
    if (content) {
        content.style.display = 'block';
    }

    var progressItem = document.querySelector(
        '.checkout-progress__item[data-step="checkout-payment-step"]'
    );
    if (progressItem) {
        document.querySelectorAll('.checkout-progress__item').forEach(function (item) {
            item.classList.remove('-active');
            var btn = item.querySelector('button.checkout-progress__btn');
            if (btn) {
                btn.disabled = false;
                btn.classList.add('js-checkout-progress-step');
                btn.removeAttribute('aria-current');
            }
        });
        progressItem.classList.add('-active');
        progressItem.classList.remove('-reachable', '-unreachable');
        var payBtn = progressItem.querySelector('button.checkout-progress__btn');
        if (payBtn) {
            payBtn.disabled = true;
            payBtn.classList.remove('js-checkout-progress-step');
            payBtn.setAttribute('aria-current', 'step');
        }
    }

    return isPaymentStepCurrent();
}

function recoverAfterOneyModalClose() {
    if (oneyPedagogicFromPayment || !isPaymentStepCurrent()) {
        restorePaymentStep();
    }
}

function bindModalStopPropagation(modal) {
    if (!modal || modal.dataset.oneyStop === '1') {
        return;
    }
    modal.dataset.oneyStop = '1';
    var stop = function (e) {
        e.stopPropagation();
    };
    modal.addEventListener('click', stop);
    modal.addEventListener('mousedown', stop);
}

function bindPedagogicStopPropagation(btn) {
    if (!btn || btn.dataset.oneyStop === '1') {
        return;
    }
    btn.dataset.oneyStop = '1';
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
    });
}

export function bindOneyWidgetRecovery() {
    document.addEventListener(
        'mousedown',
        function (e) {
            if (!isOneyUiTarget(e.target)) {
                return;
            }
            if (e.target.closest('.oney-pedagogicButton')) {
                oneyPedagogicFromPayment = isPaymentStepCurrent();
            }
            if (
                e.target.closest(
                    '.oney-close, button[aria-label="Close"][title="Close"], #showOneyWidgetAsModal'
                )
            ) {
                // mousedown croix → click éventuel sur l'élément dessous (fil d'Ariane / step)
                absorbNextClick = true;
            }
        },
        true
    );

    document.addEventListener(
        'click',
        function (e) {
            if (absorbNextClick) {
                absorbNextClick = false;
                if (!isOneyUiTarget(e.target)) {
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    recoverAfterOneyModalClose();
                    return;
                }
            }

            if (!isOneyUiTarget(e.target)) {
                return;
            }

            queueMicrotask(function () {
                if (!isPaymentStepCurrent()) {
                    recoverAfterOneyModalClose();
                }
            });
            requestAnimationFrame(function () {
                if (!isPaymentStepCurrent()) {
                    recoverAfterOneyModalClose();
                }
            });
        },
        true
    );

    if (typeof MutationObserver === 'undefined') {
        return;
    }

    var observer = new MutationObserver(function (mutations) {
        var pedagogicGone = false;

        mutations.forEach(function (m) {
            Array.prototype.forEach.call(m.addedNodes || [], function (n) {
                if (!n || n.nodeType !== 1) {
                    return;
                }
                var modal =
                    n.id === 'showOneyWidgetAsModal'
                        ? n
                        : n.querySelector && n.querySelector('#showOneyWidgetAsModal');
                if (modal) {
                    bindModalStopPropagation(modal);
                    oneyPedagogicFromPayment = isPaymentStepCurrent() || oneyPedagogicFromPayment;
                }
                if (n.matches && n.matches('.oney-pedagogicButton')) {
                    bindPedagogicStopPropagation(n);
                }
                var pedBtns = n.querySelectorAll && n.querySelectorAll('.oney-pedagogicButton');
                if (pedBtns) {
                    Array.prototype.forEach.call(pedBtns, bindPedagogicStopPropagation);
                }
            });
            Array.prototype.forEach.call(m.removedNodes || [], function (n) {
                if (
                    n &&
                    n.nodeType === 1 &&
                    (n.id === 'showOneyWidgetAsModal' ||
                        (n.querySelector && n.querySelector('#showOneyWidgetAsModal')))
                ) {
                    pedagogicGone = true;
                }
            });
        });

        if (pedagogicGone && oneyPedagogicFromPayment) {
            recoverAfterOneyModalClose();
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });
}
