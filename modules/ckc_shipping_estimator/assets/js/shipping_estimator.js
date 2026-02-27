$(document).ready(function () {
    // Affichage du modal de livraison
    $("#showDelivery").click(function (e) {
        e.preventDefault();
        $("#modalShowDelivery").modal("show");
    });

    // Initialisation du modal d'estimation des frais de livraison du panier
    initModalCartDelivery();

    // Soumission du code postal pour l'estimation des frais de livraison
    $(document).on('click', '#submitZipCode', function () {
        search();
    });

    $(document).on('click', '#submitZipCodeProduct', function () {
        search(true, $(this).data('idProduct'), $(this).data('idProductAttribute'));
    });

    // Écoute des requêtes AJAX pour rafraîchir l'affichage
    $(document).ajaxComplete(function (event, xhr, settings) {
        if (shouldRefreshModal(settings.url, settings.data)) {
            setTimeout(initModalCartDelivery, 1000);
        }
    });
});

/**
 * Initialise le modal des frais de livraison dans le panier.
 */
function initModalCartDelivery() {
    let $modalShipping = $("#modalShippingCost");

    if ($modalShipping.length) {
        $("#openModalShippingCost").on("click", function () {
            $modalShipping.css("display", "flex");
        });

        $("#closeModalShippingBtn").on("click", function () {
            $modalShipping.css("display", "none");
        });

        $(window).on("click", function (event) {
            if ($(event.target).is($modalShipping)) {
                $modalShipping.css("display", "none");
            }
        });
    }
}

/**
 * Effectue une recherche des options de livraison en fonction du code postal.
 * @param {boolean} product - Indique si l'estimation est pour un produit ou le panier.
 * @param {int|null} id_product - ID du produit si applicable.
 * @param {int|null} id_product_attribute - ID de l'attribut produit si applicable.
 */
function search(product = false, id_product = null, id_product_attribute = null) {
    let url = product ? '/module/ckc_shipping_estimator/productshippingestimator' : '/module/ckc_shipping_estimator/cartshippingestimator';

    let data = {
        ajax: "1",
        postal_code: $('#postalCodeInput').val()
    };

    if (product) {
        data.id_product = id_product;
        data.id_product_attribute = id_product_attribute;
        data.quantity = $('#quantity_wanted').val();
    }

    searchProduct(url, data);
}

/**
 * Envoie une requête AJAX pour récupérer les options de livraison et met à jour l'affichage.
 * @param {string} url - URL de l'API Prestashop.
 * @param {object} data - Données à envoyer.
 */
function searchProduct(url, data) {
    $.post(url, data, null, "json")
        .done(function (resp) {
            if (resp.success === true) {
                let $tableBody = $('.deliveriesTable tbody');
                $tableBody.empty();

                let options = resp.deliveries_options || [];
                let retrait = [];
                let others = [];

                options.forEach(function (deliveryOption) {
                    let nameLower = (deliveryOption.name || '').toLowerCase();
                    if (nameLower.indexOf('retrait chez districlos') !== -1) {
                        retrait.push(deliveryOption);
                    } else {
                        others.push(deliveryOption);
                    }
                });

                options = others.concat(retrait);

                $.each(options, function (_, deliveryOption) {
                    let price = deliveryOption.price === 'gratuit' ? 'Gratuit' : deliveryOption.price;
                    let reduc = deliveryOption.price !== 'gratuit' ? `<span class="text-line-through color-red mr-10">${deliveryOption.reduc}</span>` : '';

                    let name = formatDeliveryName(deliveryOption.name);

                    let row = `
                        <tr>
                            <td style="width: 35%" class="text-center">${name}</td>
                            <td style="width: 20%">${reduc} ${price}</td>
                            <td style="width: 45%">${deliveryOption.delay}</td>
                        </tr>
                    `;

                    $tableBody.append(row);
                });

                $('.deliveriesTable').show();
                $('.deliveryCostError').hide();
            } else {
                $('.deliveriesTable').hide();
                $('.deliveryCostError').show();
            }
        });
}

/**
 * Formate le nom du transporteur en mettant en gras la partie après '£' s'il est présent.
 * @param {string} name - Nom du transporteur.
 * @returns {string} - Nom formaté.
 */
function formatDeliveryName(name) {
    let position = name.indexOf('£');
    if (position !== -1) {
        let avant = name.substring(0, position);
        let apres = name.substring(position + 1);
        return `${avant} <strong>${apres}</strong>`;
    }
    return name;
}

/**
 * Détermine si le modal doit être rafraîchi après une requête AJAX.
 * @param {string} url - URL de la requête.
 * @param {string} data - Données envoyées en requête.
 * @returns {boolean} - Retourne true si le modal doit être mis à jour.
 */
function shouldRefreshModal(url, data) {
    let decodedData = decodeURIComponent(data || "");
    let dataObj = Object.fromEntries(new URLSearchParams(decodedData));

    return url.includes('controller=product') && dataObj.action === "refresh"
        || url.includes('panier?ajax=1&action=refresh')
        || url.includes('hsmultiaccessoriespro')
        || url.includes('-pack.html?quantity_wanted=')
        || (url.includes('-pack.html') && dataObj.action === "refresh")
        || url.includes('pack/update')
        || url.includes('module/pm_advancedpack/ajax_cart');
}
