export function getOriginalPaymentActions() {
    return $('.js-payment-checkout-actions')
        .not('.js-payment-checkout-actions-clone')
        .filter(function () {
            return !$(this).closest('.js-cart-summary-payment-actions').length;
        })
        .first();
}

function syncPaymentActionsToSummary() {
    var $actions = getOriginalPaymentActions();
    var $slot = $('.js-cart-summary-payment-actions');
    if (!$actions.length || !$slot.length) {
        return;
    }

    var $paymentStep = $('#checkout-payment-step');
    var isPaymentCurrent = $paymentStep.hasClass('-current') || $paymentStep.hasClass('js-current-step');

    if (!isPaymentCurrent) {
        $slot.empty().removeClass('is-visible');
        return;
    }

    var $clone = $actions.clone(false, false);
    $clone
        .removeClass('js-payment-checkout-actions')
        .addClass('js-payment-checkout-actions-clone payment-checkout-actions--summary');

    // Évite les doublons d'id / name qui cassent la vraie checkbox.
    // Important: retirer js-conditions-to-approve du clone — PrestaShop
    // (toggleOrderButton) sélectionne TOUTES les checkbox sous cette classe.
    // Sinon cocher la CGV sous les paiements laisse le clone non coché
    // et le bouton reste disabled + l'alerte visible.
    $clone.find('[id]').removeAttr('id');
    $clone.find('form').each(function () {
        var $form = $(this);
        var classes = ($form.attr('class') || '')
            .split(/\s+/)
            .filter(function (c) {
                return c && c !== 'js-conditions-to-approve';
            });
        classes.push('js-conditions-to-approve-summary');
        $form.replaceWith(
            $('<div class="' + classes.join(' ') + '"></div>').append($form.contents())
        );
    });
    $clone.find('.js-conditions-to-approve')
        .removeClass('js-conditions-to-approve')
        .addClass('js-conditions-to-approve-summary');
    $clone.find('.payment-confirmation__prev').remove();

    $clone.find('input[type="checkbox"]').each(function (index) {
        var $input = $(this);
        var $original = $actions.find('input[type="checkbox"]').eq(index);
        var summaryId = 'conditions-summary-' + index;
        $input.attr({
            id: summaryId,
            name: ''
        }).removeAttr('form').removeAttr('required')
            .removeClass('ps-shown-by-js');
        $input.prop('checked', $original.is(':checked'));

        var $label = $input.closest('li').find('label.condition-label');
        if ($label.length) {
            $label.attr('for', summaryId);
        }
    });

    var $originalBtn = $actions.find('.payment-confirmation__submit');
    var $cloneBtn = $clone.find('.payment-confirmation__submit');
    $cloneBtn
        .removeAttr('id')
        .attr('type', 'button')
        .toggleClass('disabled', $originalBtn.hasClass('disabled') || $originalBtn.prop('disabled'));

    $slot.empty().append($clone).addClass('is-visible');
}

function syncSummaryCheckboxState() {
    var $actions = getOriginalPaymentActions();
    var $clone = $('.js-payment-checkout-actions-clone');
    if (!$actions.length || !$clone.length) {
        return;
    }
    var $realBtn = $actions.find('.payment-confirmation__submit');
    $clone.find('.payment-confirmation__submit')
        .toggleClass('disabled', $realBtn.hasClass('disabled') || $realBtn.prop('disabled'));
    $actions.find('input[type="checkbox"]').each(function (i) {
        $clone.find('input[type="checkbox"]').eq(i).prop('checked', $(this).is(':checked'));
    });
}

export function bindPaymentConditionsEvents(selectDefaultPaymentOption) {
    $(document).on('change', '.js-payment-checkout-actions-clone input[type="checkbox"]', function () {
        var index = $(this).closest('li').index();
        var $original = getOriginalPaymentActions().find('input[type="checkbox"]').eq(index);
        if ($original.length) {
            $original.prop('checked', $(this).is(':checked')).trigger('change');
        }
    });

    $(document).on('click', '.js-payment-checkout-actions-clone .payment-confirmation__submit', function (e) {
        e.preventDefault();
        if ($(this).hasClass('disabled')) {
            return;
        }
        var $realBtn = getOriginalPaymentActions().find('.payment-confirmation__submit');
        if ($realBtn.length) {
            $realBtn.trigger('click');
        }
    });

    $(document).on(
        'change',
        'input[name="payment-option"], #conditions-to-approve input[type="checkbox"]',
        function () {
            setTimeout(syncSummaryCheckboxState, 50);
        }
    );

    syncPaymentActionsToSummary();
    $(document).on('click', '.checkout-step .js-step-title', function () {
        setTimeout(function () {
            selectDefaultPaymentOption();
            syncPaymentActionsToSummary();
        }, 50);
        setTimeout(function () {
            selectDefaultPaymentOption();
            syncPaymentActionsToSummary();
        }, 300);
    });
    if (typeof prestashop !== 'undefined') {
        prestashop.on('updatedCart', function () {
            setTimeout(syncPaymentActionsToSummary, 100);
        });
    }
}
