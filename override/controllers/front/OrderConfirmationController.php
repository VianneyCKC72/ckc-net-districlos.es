<?php

class OrderConfirmationController extends OrderConfirmationControllerCore
{
    public function initContent()
    {
        parent::initContent();

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
}
