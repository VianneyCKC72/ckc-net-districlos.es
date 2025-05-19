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
});
