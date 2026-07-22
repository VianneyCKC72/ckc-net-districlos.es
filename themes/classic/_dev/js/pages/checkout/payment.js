/**
 * Checkout — paiement (orchestrateur)
 */
import { applyPaymentLogos, boldPaymentInstallments, markEmptyPaymentForms } from './payment/ui';
import { syncPaymentCardSelection } from './payment/sync-selection';
import { initPayPalPaymentGroup } from './payment/paypal-group';
import { initOneyPaymentGroup } from './payment/oney-hipay-group';
import { initMhOneyNxPaymentGroup } from './payment/oney-nx-group';
import { initMhOneyLongPaymentGroup } from './payment/oney-long-group';
import { bindOneyWidgetRecovery } from './payment/oney-recover';
import { selectDefaultPaymentOption } from './payment/default-option';
import { bindPaymentConditionsEvents } from './payment/conditions';
import { bindApplePayCgvGuard, syncApplePayWithCgv } from './payment/apple-pay-cgv';

export default function initCheckoutPayment() {
    if (!$('body').hasClass('page-order')) {
        return;
    }

    applyPaymentLogos();

    initPayPalPaymentGroup();
    initOneyPaymentGroup();
    initMhOneyNxPaymentGroup();
    initMhOneyLongPaymentGroup();
    bindOneyWidgetRecovery();
    bindApplePayCgvGuard();

    boldPaymentInstallments();
    markEmptyPaymentForms();

    $(document).on('change', 'input[name="payment-option"]', function () {
        syncPaymentCardSelection();
        markEmptyPaymentForms();
        syncApplePayWithCgv();
    });
    syncPaymentCardSelection();

    selectDefaultPaymentOption();
    setTimeout(selectDefaultPaymentOption, 200);

    bindPaymentConditionsEvents(selectDefaultPaymentOption);
}
