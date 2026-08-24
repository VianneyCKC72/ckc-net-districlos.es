<?php

class OrderConfirmationController extends OrderConfirmationControllerCore
{
    public function initContent()
    {
        parent::initContent();

        $this->replaceDeliveryDelayPlaceholder();

        if ($this->id_order) {
            $order = new Order((int) $this->id_order);

            if (Validate::isLoadedObject($order)) {
                $paymentAcceptedState = Configuration::get('PS_OS_PAYMENT');

                if ((int)$order->current_state === (int)$paymentAcceptedState  || (int)$order->current_state > (int)$paymentAcceptedState) {
                    $orderDate = new DateTime($order->date_add);
                    $now = new DateTime();
                    $diffHours = ($now->getTimestamp() - $orderDate->getTimestamp()) / 3600;

                    if ($diffHours > 3) {
                        Tools::redirect(
                            $this->context->link->getPageLink(
                                'order-detail',
                                true,
                                null,
                                ['id_order' => (int) $order->id]
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * Remplace [delaiLivraison+N] dans le délai transporteur affiché sur la confirmation.
     */
    private function replaceDeliveryDelayPlaceholder()
    {
        $presentedOrder = $this->context->smarty->getTemplateVars('order');
        if (!$presentedOrder || !isset($presentedOrder['carrier'])) {
            return;
        }

        $carrier = $presentedOrder['carrier'];
        if (empty($carrier['delay']) || strpos((string) $carrier['delay'], '[delaiLivraison+') === false) {
            return;
        }

        if (!class_exists('DeliveryDelayHelper', false)) {
            require_once _PS_ROOT_DIR_ . '/override/classes/checkout/DeliveryDelayHelper.php';
        }

        $carrier['delay'] = DeliveryDelayHelper::replace($carrier['delay']);

        if (is_object($presentedOrder) && method_exists($presentedOrder, 'offsetSet')) {
            $presentedOrder->offsetSet('carrier', $carrier, true);
        }

        $this->context->smarty->assign('order', $presentedOrder);
    }
}
