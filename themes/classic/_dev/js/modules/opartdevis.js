$(document).ready(function () {
    // Met à jour le bouton "Commander" sur la page de paiement
    var paymentButton = $("#payment-confirmation button").html();

    $("#checkout-payment-step input[type='radio']").on("click", function () {
        var moduleName = this.dataset.moduleName;
        var orderButtonContent = "Créer un devis";

        setTimeout(function () {
            if (moduleName === "opartdevis") {
                $("#payment-confirmation button").html(orderButtonContent);
            } else {
                $("#payment-confirmation button").html(paymentButton);
            }
        }, 100);
    });

    // Surcharge du modal ps_shopping_cart
    prestashop.blockcart = prestashop.blockcart || {};
    var blockCartModal = prestashop.blockcart.showModal;

    prestashop.blockcart.showModal = function (modalHTML) {
        var $body = $('body');
        var $modal = $('#opartdevis-modal');
        var $backDrop = $('.modal-backdrop');

        $body.append(modalHTML);
        $backDrop.fadeTo(200, 0.4);
        $modal.fadeIn();

        $body.one('click', '#opartdevis-modal', function () {
            $modal.remove();
            $backDrop.remove();
        });
    };

    prestashop.on('updateCart', function (event) {
        if (event.resp && event.resp.hasError && event.resp.modal) {
            prestashop.blockcart.showModal(event.resp.modal);
            return;
        }

        var refreshURL = $('.blockcart').data('refresh-url');
        var requestData = {};

        if (event.reason) {
            requestData = {
                id_product_attribute: event.reason.idProductAttribute,
                id_product: event.reason.idProduct,
                action: event.reason.linkAction,
                id_customization: event.reason.idCustomization
            };
        }

        $.post(refreshURL, requestData)
            .then(function (resp) {
                $('.blockcart').replaceWith($(resp.preview).find('.blockcart'));
                if (resp.modal) {
                    blockCartModal(resp.modal);
                }
            })
            .fail(function (resp) {
                prestashop.emit('handleError', {eventType: 'updateShoppingCart', resp: resp});
            });
    });
});
