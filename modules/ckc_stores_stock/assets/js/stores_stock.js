$("#selectShop, #changeShop").click(function (e) {
    e.preventDefault();
    $("#modalSelectShop").modal("show");
});

$('.btn-coordonnes').click(function () {
    let address = $(this).data('address')
    $('#address' + address).toggle()
})

$("#cp").keyup(function () {
    NosMagasins();
});

function NosMagasins() {
    var mycodepostal = $("input[name='cp']").val();
    if (mycodepostal.length > 2) {
        $(".agenceShop").hide();
        mycodepostal = mycodepostal.toUpperCase();
        toUpper = function (x) {
            return x.toUpperCase();
        };

        $(".agenceShop").each(function () {
            let cpList = $(this).data('zipcodes').split(',');
            let myCodePostal = mycodepostal.toString();

            let match = cpList.some(cp => {
                let regex = new RegExp('^' + cp.replace('*', '.*') + '$');
                return regex.test(myCodePostal);
            });

            if (match) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    } else {
        $(".agenceShop").show();
    }
}

function fetchStockData(isPack = false, packList = {}) {
    let id_store = $(".setStore.selected-1").data("shop-id") || null;
    let id_product = $("#product_page_product_id").val();
    let dataProduct = $("#product-details").data('product');
    if (dataProduct) {
        let id_product_attribute = dataProduct.id_product_attribute || null;
        
        $.post('/modules/ckc_stores_stock/ajax.php', {
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
}

// Configuration centralisée des états de stock
const STOCK_STATES = {
    victime_succes: {
        title: "Victime de son succès",
        subtitle: "Ce produit n'est plus disponible",
        stock: "Produit épuisé",
        color: "#ff144e",
        image: 'deliveryFinDeVie.png',
        showStoreButton: false,
        disableCart: true,
        blockQuantity: true
    },
    fin_de_vie: {
        title: "Fin de série !",
        subtitle: "Commandez vite, stock bas !",
        stock: (qty, seuil, platformStock) => `${platformStock || 0} produits en stock`,
        color: "#ff144e",
        image: 'deliveryFinDeVie.png',
        showStoreButton: false,
        disableCart: false,
        blockQuantity: false
    },
    normal: {
        title: "Stock en magasin",
        subtitle: "",
        stock: (qty, seuil) => qty > 0 && seuil ? `${qty} en stock national` : "Arrivage en cours",
        color: null,
        image: 'delivery.png',
        showStoreButton: true,
        disableCart: false,
        blockQuantity: false
    }
};

function updateStockDisplay(resp) {
    const $container = $('.blockDeliveryDisplayStock').parent();
    
    // Déterminer l'état
    const stateName = resp.victime_succes ? 'victime_succes' : (resp.fin_de_vie ? 'fin_de_vie' : 'normal');
    const state = STOCK_STATES[stateName];
    
    // Appliquer les classes CSS
    $container.removeClass('stock-special fin-de-vie victime-succes');
    if (stateName !== 'normal') {
        $container.addClass('stock-special ' + stateName.replace('_', '-'));
    }
    
    // Mettre à jour les textes
    $(".style_findevie").text(state.title);
    $(".style_findevie_thin").text(state.subtitle);
    
    const stockText = typeof state.stock === 'function' 
        ? state.stock(resp.total_quantity, resp.seuil, resp.platform_stock) 
        : state.stock;
    $(".stock-display").text(stockText);
    
    // Appliquer la couleur
    if (state.color) {
        $(".stock-display").removeClass('text-secondary').attr('style', 'color: ' + state.color + ' !important;');
    } else {
        $(".stock-display").addClass('text-secondary').removeAttr('style');
    }
    
    // Mettre à jour l'image
    $(".blockDeliveryDisplayStock img").attr('src', '/modules/ckc_stores_stock/assets/img/' + state.image);
    
    // Gérer le bouton "Voir magasin"
    if (state.showStoreButton) {
        $(".remove_findevie").css('display', 'block');
    } else {
        $(".remove_findevie").attr('style', 'display: none !important');
    }
    
    // Gérer le bouton "Ajouter au panier"
    if (state.disableCart) {
        disableAddToCartButton();
    } else {
        enableAddToCartButton();
    }
    
    // Gérer les contrôles de quantité
    const $input = $('input[name="qty"], #quantity_wanted');
    const $buttons = $('.bootstrap-touchspin-up, .bootstrap-touchspin-down, .btn-touchspin, button.js-touchspin, .input-group-btn-vertical button, .product-quantity button, .qty button');
    
    if (state.blockQuantity) {
        $input.attr('max', 0).val(0).prop('disabled', true);
        $buttons.prop('disabled', true).addClass('disabled').css({'pointer-events': 'none', 'opacity': '0.5'});
        $('.product-quantity, .qty, .input-group').on('click', 'button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    }
    
    // Mise à jour de la référence
    $("#reference_to_display").text(resp.reference_to_display);
    
    // Gestion magasin sélectionné
    updateStoreDisplay(resp);
    
    // Mise à jour des stocks par magasin
    updateStoresModal(resp.stores, resp.fin_de_vie);
}

// Fonctions utilitaires pour le panier
function disableAddToCartButton() {
    $('.add-to-cart, .product-add-to-cart button[type="submit"]').prop('disabled', true).addClass('disabled');
}

function enableAddToCartButton() {
    $('.add-to-cart, .product-add-to-cart button[type="submit"]').prop('disabled', false).removeClass('disabled');
}

// Fonction pour gérer l'affichage du magasin sélectionné
function updateStoreDisplay(resp) {
    if (resp.id_store) {
        $(".store-selected").show();
        $(".store-selected .nameStore").html(`${resp.store_name}<br>`);
        $(".store-selected .productQuantity").html(resp.quantity > 0 ? `${resp.quantity} produit(s) en stock` : "Arrivage en cours");
        $("#modalProductStock-" + resp.id_store).html(getStockSvg(resp.quantity));
    } else {
        $(".store-selected").hide();
    }
}

// Fonction pour gérer la modal des magasins
function updateStoresModal(stores, isFinDeVie) {
    stores.forEach(store => {
        const stockText = store.stock > 0 && store.seuil ? `${store.stock} en stock` : "Arrivage en cours";
        $("#modalProductStock-" + store.id).html(getStockSvg(stockText));
        
        if (store.selected) {
            $('.shopId-' + store.id).hide().next().show();
            const quantityText = store.stock ? `${store.stock} en stock` : "Arrivage en cours";
            $('.stock-display').html(quantityText + ' à <br>' + store.name);
            
            if (!isFinDeVie) {
                $('#selectShop').text('Changer de magasin');
            }
        }
    });
}

// Fonction helper pour générer le SVG de stock
function getStockSvg(text) {
    return `<svg fill="#79c046" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="width: 20px;margin-right: 1rem;">
        <path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/>
    </svg> ${text}`;
}

$(document).ready(function () {
    // Initialiser les contrôles au chargement
    const $container = $('.blockDeliveryDisplayStock').parent();
    const stockState = $container.data('stock-state');
    const maxStock = parseInt($container.data('total-quantity'));
    
    if (stockState && STOCK_STATES[stockState]) {
        const state = STOCK_STATES[stockState];
        
        // Appliquer les contrôles initiaux
        if (state.disableCart) {
            disableAddToCartButton();
        }
        
        if (state.blockQuantity) {
            const $input = $('input[name="qty"], #quantity_wanted');
            const $buttons = $('.bootstrap-touchspin-up, .bootstrap-touchspin-down, .btn-touchspin, button.js-touchspin, .input-group-btn-vertical button, .product-quantity button, .qty button');
            $input.attr('max', 0).val(0).prop('disabled', true);
            $buttons.prop('disabled', true).addClass('disabled').css({'pointer-events': 'none', 'opacity': '0.5'});
        }
    }
    
    fetchStockData();

    prestashop.on('updatedProduct', function () {
        fetchStockData();
    });

    $(document).on('ajaxComplete', function (event, xhr, settings) {
        if (settings.url.includes('product') || settings.url.includes('favoritestore')) {
            fetchStockData();
        }
    });
});


if ($('.delivery-option input').length > 0) {
    $('.delivery-option input').change(function () {
        let selectedDelivery = $('input[name*="delivery_option"]:checked').attr('id');

        $.post(
            '/module/ckc_stores_stock/getStockCheckout',
            {ajax: "1", selectedDelivery: selectedDelivery},
            null,
            "json"
        ).then(function (resp) {
            if (resp.data.stock.isClickAndCollect) {
                $('#stock-summary-content .card-header').html('Click And Collect');
                if (resp.data.stock.stock_ok) {
                    $('#stock-summary-content .productLimit').html('Super ! Tous vos produits sont en stock');
                    $('#stock-summary-content .text').html('<p>Vous pouvez venir récupérer les produits en stock dans votre magasin 2h après votre commande.</p>')
                } else {
                    $('#stock-summary-content .productLimit').html('Un ou des produits sont en cours de réapprovisionnement');
                    $('#stock-summary-content .text').html('<p>Vous pouvez venir récupérer les produits en stock dans votre magasin.</p> <p>Pour les produits en cours de réapprovisionnement, nous vous appellerons aussitôt ces derniers disponibles.</p>')
                }
            } else {
                $('#stock-summary-content .card-header').html('Livraison');
                $('#stock-summary-content .productLimit').html('Notre magasin vous livre à domicile ou sur votre chantier !');
                $('#stock-summary-content .text').html('<p>La livraison se fait sur rendez-vous uniquement. Nous allons vous contacter suite à votre commande pour déterminer cette date.</p><p>Le délais moyen pour vos produits est entre 10 à 15 jours ouvrés (sous réserve de stock disponible).</p>')
            }
            $('#stock-summary-content .products').empty()

            for (let i = 0; i < resp.data.stocks.length; i++) {
                if (resp.data.stocks[i].stock_ok) {
                    var image = '<img src="/modules/ckc_stores_stock/assets/img/selectionner.webp" class="unveil-loaded">'
                } else {
                    var image = '<img src="/modules/ckc_stores_stock/assets/img/moins.webp" class="unveil-loaded">'
                }

                $('#stock-summary-content .products').append('<p class="productQuantity" style="color:#000000;">' + image + resp.data.stocks[i].product_name + '<br></p>')
            }
            $('#stock-summary-content .nameStore').html(resp.data.stock.store_name);

        });
    })
}
