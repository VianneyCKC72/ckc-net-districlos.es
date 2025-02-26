$(document).ready(function () {
    var $element = $(".wrapper__swiper__product-img");

    if ($element.length) {
        function updateWidth() {
            var width = $element.width(); // Largeur réelle de l'élément
            $element.css("--product-img-width", width + "px");
        }

        // Observer les changements de taille avec ResizeObserver
        var resizeObserver = new ResizeObserver(updateWidth);
        resizeObserver.observe($element[0]);

        // Initialiser la valeur au chargement
        updateWidth();
    }

    var $postalCodeInput = $("#postalCodeInput");
    if ($postalCodeInput.length) {
        $postalCodeInput.on("keyup", function (event) {
            if (event.key === "Enter") {
                search();
            }
        });
    }

    $("#showDelivery").on("click", function (e) {
        e.preventDefault();
        $("#modalShowDelivery").modal("show");
    });

    $("#submitZipCode").on("click", function () {
        search();
    });

    $("#submitZipCodeProduct").on("click", function () {
        search(true, $(this).data("idProduct"), $(this).data("idProductAttribute"));
    });


    // Délégation d'événements pour les boutons de quantité
    $('body').on('click', '.increase', function () {
        let $quantityInput = $("#quantity_wanted");
        $quantityInput[0].stepUp();
        $quantityInput.trigger('input');
    });
    $('body').on('click', '.decrease', function () {
        let $quantityInput = $("#quantity_wanted");
        if ($quantityInput.val() > parseInt($quantityInput.attr('min')) || !$quantityInput.attr('min')) {
            $quantityInput[0].stepDown();
            $quantityInput.trigger('input');
        }
    });

    // Récupère dynamiquement les valeurs des inputs
    function getFormData() {
        return {
            token: $('input[name=token]').val(),
            productId: $('input[name=id_product]').val(),
            customizationId: $('input[name=id_customization]').val(),
            quantity: $("#quantity_wanted").val(),
            groupParams: getGroupParams()
        };
    }

    // si déclinaison on a des groups sur les produits
    function getGroupParams() {
        let groupParams = {};
        $('input[name^="group["], select[name^="group["]').each(function (index) {
            groupParams[`group[${index + 1}]`] = $(this).val();
        });
        return groupParams;
    }

    // Construire l'URL de requête avec les valeurs dynamiques
    function buildRequestURL(formData) {
        let domain = window.location.origin;
        // console.log(domain);debugger
        let url = new URL(domain + "/index.php");
        let params = {
            controller: "product",
            token: formData.token,
            id_product: formData.productId,
            id_customization: formData.customizationId,
            qty: formData.quantity,
            quickview: 0,
            ajax: 1,
            action: "refresh",
            quantity_wanted: formData.quantity,
            ...formData.groupParams // Ajout des déclinaisons dynamiques
        };
        $.each(params, function (key, value) {
            url.searchParams.append(key, value);
        });
        return url.toString();
    }

    // Vérification du stock (AJAX)
    function checkStock() {
        let formData = getFormData();
        let requestURL = buildRequestURL(formData);
        $.ajax({
            url: requestURL,
            type: "POST",
            dataType: "json",
            cache: false,
            headers: {
                "Content-Type": "application/json",
                "Cache-Control": "no-store, no-cache, must-revalidate"
            },
            success: function (data) {
                console.log("Réponse du serveur :", data);
                if (data) updateViewProduct(data);
            },
            error: function (error) {
                console.error("Erreur lors de la requête :", error);
            }
        });
    }

    // Mise à jour des éléments ajax
    function updateViewProduct(data) {
        $.each(data, function (key, value) {
            let selectorMap = {
                'product_add_to_cart': '.js-product-add-to-cart',
                'product_prices': '.js-product-prices',
            };
            if (key in selectorMap) {
                $(selectorMap[key]).replaceWith(value);
            }
        });
    }

    // Écoute de l'événement input sur le champ de quantité
    $('body').on('input', '#quantity_wanted', function () {
        checkStock();
    });
});


async function search(product = false, id_product = null, id_product_attribute = null) {
    let url = product ? prestashop.urls.pages.product : prestashop.urls.pages.cart;
    if (product) {
        url += "?id_product=" + id_product;
    }

    let quantity = product ? $("#quantity_wanted").val() : null;
    let data = {
        ajax: "1",
        action: "get_deliveries",
        postal_code: $("#postalCodeInput").val(),
        ...(product && {id_product, id_product_attribute, quantity})
    };

    await searchProduct(url, data);
}

async function searchProduct(url, data) {
    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify(data)
        });

        const resp = await response.json();
        var $deliveriesTable = $(".deliveriesTable tbody");
        var $deliveryCostError = $(".deliveryCostError");

        if (resp.success) {
            $deliveriesTable.empty();
            resp.deliveries_options.forEach(({name, price, reduc, delay}) => {
                let formattedPrice = price === "gratuit" ? "Gratuit" : `<span class="text-line-through color-red mr-10">${reduc}</span>${price}`;

                let position = name.indexOf("£");
                if (position !== -1) {
                    let avant = name.substring(0, position);
                    let apres = name.substring(position + 1);
                    name = `${avant} <strong>${apres}</strong>`;
                }

                $deliveriesTable.append(`
                    <tr>
                        <td style="width: 35%" class="text-center">${name}</td>
                        <td style="width: 20%">${formattedPrice}</td>
                        <td style="width: 45%">${delay}</td>
                    </tr>
                `);
            });

            $(".deliveriesTable").show();
            $deliveryCostError.hide();
        } else {
            $(".deliveriesTable").hide();
            $deliveryCostError.show();
        }
    } catch (error) {
        console.error("Erreur lors de la récupération des livraisons :", error);
    }
}