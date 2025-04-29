$("#selectShop, #changeShop").click(function (e) {
    e.preventDefault();
    $("#modalSelectShop").modal("show");
});

function fetchStockData(isPack = false, packList = {}) {
    let id_store = $(".setStore.selected-1").data("shop-id") || null;
    let id_product = $("#product_page_product_id").val();
    let id_product_attribute = $("#group_1").val() || 0;

    $.post('/module/ckc_stores_stock/getStock', {
        ajax: 1,
        id_product,
        id_product_attribute,
        id_store,
        isPack,
        packList
    }, function (response) {
        updateStockDisplay(response);
    }, "json");
}

function updateStockDisplay(resp) {
    $(".productQuantity").text(resp.total_quantity > 0 && resp.seuil ? `${resp.total_quantity} en stock national` : "Arrivage en cours");
    $("#reference_to_display").text(resp.reference_to_display);

    if (resp.id_store) {
        $(".store-selected").show();
        $(".store-selected .nameStore").html(`${resp.store_name}<br>`);
        $(".store-selected .productQuantity").html(resp.quantity > 0 ? `${resp.quantity} produit(s) en stock` : "Arrivage en cours");
        $("#modalProductStock-" + resp.id_store).html(`<svg fill="#79c046" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="width: 20px;margin-right: 1rem;"><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg> ${resp.quantity > 0 ? resp.quantity + " en stock" : "Arrivage en cours"}`);
    } else {
        $(".store-selected").hide();
    }

    resp.stores.forEach(store => {
        let stockText = store.stock > 0 && store.seuil ? `${store.stock} en stock` : "Arrivage en cours";
        $("#modalProductStock-" + store.id).html(`<svg fill="#79c046" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="width: 20px;margin-right: 1rem;"><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg> ${stockText}`);
    });
}

$(document).ready(function () {
    fetchStockData();

    prestashop.on('updatedProduct', function () {
        fetchStockData();
    });

    $(document).on('ajaxComplete', function (event, xhr, settings) {
        if (settings.url.includes('product')) {
            fetchStockData();
        }
    });
});
