/**
 * Checkout — paiement (orchestrateur)
 */
import { applyPaymentLogos, boldPaymentInstallments, markEmptyPaymentForms } from './payment/ui';
import { syncPaymentCardSelection } from './payment/sync-selection';
import { initPayPalPaymentGroup } from './payment/paypal-group';
import { initOneyPaymentGroup } from './payment/oney-hipay-group';
import { initMhOneyNxPaymentGroup } from './payment/oney-nx-group';
import { initMhOneyLongPaymentGroup } from './payment/oney-long-group';
import { selectDefaultPaymentOption } from './payment/default-option';
import { bindPaymentConditionsEvents } from './payment/conditions';

export default function initCheckoutPayment() {
    if (!$('body').hasClass('page-order')) {
        return;
    }

    applyPaymentLogos();

    initPayPalPaymentGroup();
    initOneyPaymentGroup();
    initMhOneyNxPaymentGroup();
    initMhOneyLongPaymentGroup();

    boldPaymentInstallments();
    markEmptyPaymentForms();

    $(document).on('change', 'input[name="payment-option"]', function () {
        syncPaymentCardSelection();
        markEmptyPaymentForms();
    });
    syncPaymentCardSelection();

    selectDefaultPaymentOption();
    setTimeout(selectDefaultPaymentOption, 200);

    bindPaymentConditionsEvents(selectDefaultPaymentOption);
}
