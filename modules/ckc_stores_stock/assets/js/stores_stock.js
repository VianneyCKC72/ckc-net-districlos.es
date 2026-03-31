function openCkcShopDrawer() {
    const $drawer = $("#modalSelectShop");
    if (!$drawer.length) return;

    $drawer.addClass("is-open").attr("aria-hidden", "false");
    $("body").addClass("ckc-shop-drawer-open");

    // focus: mettre le curseur sur le champ CP si présent
    const $cp = $drawer.find("#cp");
    if ($cp.length) {
        setTimeout(() => $cp.trigger("focus"), 50);
    }
}

function closeCkcShopDrawer() {
    const $drawer = $("#modalSelectShop");
    if (!$drawer.length) return;

    $drawer.removeClass("is-open").attr("aria-hidden", "true");
    $("body").removeClass("ckc-shop-drawer-open");
}

$("#selectShop, #changeShop").click(function (e) {
    e.preventDefault();
    openCkcShopDrawer();
});

// fermeture via bouton/overlay
$(document).on("click", "[data-ckc-shop-drawer-close]", function (e) {
    e.preventDefault();
    closeCkcShopDrawer();
});

// fermeture via ESC
$(document).on("keydown", function (e) {
    if (e.key === "Escape" || e.keyCode === 27) {
        if ($("#modalSelectShop").hasClass("is-open")) {
            closeCkcShopDrawer();
        }
    }
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

// Variable pour stocker le timeout du debounce
let fetchStockTimeout;

/** Pack AP5 : #idCombination = JSON { id_product_pack: id_product_attribute } ; ckcPackList doit avoir id_product_pack (pack.tpl). */
function getPackListFromIdCombination(fallbackPackList) {
    const base = Array.isArray(fallbackPackList)
        ? fallbackPackList
        : (Array.isArray(window.ckcPackList) ? window.ckcPackList : []);

    const $el = $('#idCombination');
    if (!$el.length) return base;

    let raw = ($el.val() || $el.attr('value') || '').toString().trim();
    if (!raw) return base;
    if (raw.includes('&quot;')) raw = raw.replaceAll('&quot;', '"');
    if (raw.includes('&#34;')) raw = raw.replaceAll('&#34;', '"');

    let comboMap;
    try {
        comboMap = JSON.parse(raw);
    } catch (e) {
        return base;
    }
    if (!comboMap || typeof comboMap !== 'object') return base;

    return base.map(function (item) {
        const pid = String(item.id_product);
        const packLineKey = (item.id_product_pack != null && String(item.id_product_pack) !== '' && Number(item.id_product_pack) > 0)
            ? String(item.id_product_pack)
            : null;
        let newIdpa = null;
        if (packLineKey && Object.prototype.hasOwnProperty.call(comboMap, packLineKey)) {
            newIdpa = comboMap[packLineKey];
        } else if (Object.prototype.hasOwnProperty.call(comboMap, pid)) {
            newIdpa = comboMap[pid];
        }
        const finalIdpa = (typeof newIdpa !== 'undefined' && newIdpa !== null)
            ? Number(newIdpa)
            : Number(item.id_product_attribute || 0);
        return {
            ...item,
            id_product_attribute: finalIdpa,
            key: `${Number(item.id_product)}:${finalIdpa}`,
        };
    });
}

function fetchStockData(isPack = false, packList = {}) {
    // Annuler l'appel précédent s'il existe
    clearTimeout(fetchStockTimeout);
    
    // Attendre 100ms avant d'exécuter pour éviter les appels multiples
    fetchStockTimeout = setTimeout(function() {
        let id_store = $(".setStore.selected-1").data("shop-id") || null;
        let id_product = $("#product_page_product_id").val();
        let dataProduct = $("#product-details").data('product');
        const isPackPage = ($('#is_pack').val() == '1') || ($('#is_pack').length > 0) || (Array.isArray(window.ckcPackList) && window.ckcPackList.length);

        if (isPackPage) {
            isPack = true;
            const base = (Array.isArray(packList) && packList.length)
                ? packList
                : (Array.isArray(window.ckcPackList) ? window.ckcPackList : []);
            packList = $('#idCombination').length ? getPackListFromIdCombination(base) : base;
        }

        // Sur certaines pages (ex: pack advancedpack), #product-details n'a pas data('product').
        // Si on a une liste pack, on doit quand même pouvoir appeler l'AJAX.
        let id_product_attribute = (dataProduct && dataProduct.id_product_attribute) ? dataProduct.id_product_attribute : 0;

        if (!id_product) {
            return;
        }

        // Ne bloquer que si on n'a ni dataProduct ni packList utile
        const hasPackList = Array.isArray(packList) ? packList.length > 0 : (packList && Object.keys(packList).length > 0);
        if (!dataProduct && !hasPackList) {
            return;
        }

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
    }, 100);
}

function updateStockDisplay(resp) {
    const $container = $('.blockDeliveryDisplayStock').parent();
    
    // Source de vérité unique: PHP (ui + i18n)
    const ui = resp.ui;
    const i18n = resp.i18n;
    const stateName = ui.state;
    
    // Appliquer les classes CSS (même style que produits normaux)
    $container.removeClass('stock-special fin-de-vie victime-succes');
    if (stateName !== 'normal') {
        $container.addClass('stock-special ' + stateName.replace('_', '-'));
    }
    
    // Mettre à jour les textes
    $(".style_findevie").text(ui.title);
    $(".style_findevie_thin").text(ui.subtitle);
    
    $(".stock-display").text(ui.stock);
    
    // Appliquer la couleur (même style que produits normaux)
    if (ui.color) {
        $(".stock-display").removeClass('text-secondary').attr('style', 'color: ' + ui.color + ' !important;');
    } else {
        $(".stock-display").addClass('text-secondary').removeAttr('style');
    }
    
    // Mettre à jour l'image
    $(".blockDeliveryDisplayStock img").attr('src', '/modules/ckc_stores_stock/assets/img/' + ui.image);
    
    // Gérer le bouton "Voir magasin"
    if (!!ui.showStoreButton) {
        $("#selectShop").css('display', 'block');
        $(".remove_findevie").css('display', 'block');
    } else {
        $("#selectShop").attr('style', 'display: none !important');
        $(".remove_findevie").attr('style', 'display: none !important');
    }
    
    // Gérer le bouton "Ajouter au panier"
    if (!!ui.disableCart) {
        disableAddToCartButton();
    } else {
        enableAddToCartButton();
    }
    
    // Gérer les contrôles de quantité
    const $input = $('input[name="qty"], #quantity_wanted');
    const $buttons = $('.bootstrap-touchspin-up, .bootstrap-touchspin-down, .btn-touchspin, button.js-touchspin, .input-group-btn-vertical button, .product-quantity button, .qty button');
    
    const blockQuantity = !!ui.blockQuantity;
    const limitToPlatformStock = !!ui.limitToPlatformStock;
    const limitToTotalStock = !!ui.limitToTotalStock;

    if (blockQuantity) {
        // Bloquer complètement pour victime de succès
        $input.attr('max', 0).val(0).prop('disabled', true);
        $buttons.prop('disabled', true).addClass('disabled').css({'pointer-events': 'none', 'opacity': '0.5'});
        $('.product-quantity, .qty, .input-group').on('click', 'button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    } else if (limitToPlatformStock && resp.platform_stock) {
        // Limiter au stock de la plateforme pour les produits fin de vie
        $input.attr('max', resp.platform_stock).prop('disabled', false);
        $buttons.prop('disabled', false).removeClass('disabled').css({'pointer-events': 'auto', 'opacity': '1'});
        
        // Corriger si la quantité actuelle dépasse le stock plateforme
        const currentQty = parseInt($input.val());
        if (currentQty > resp.platform_stock) {
            $input.val(resp.platform_stock);
            // Déclencher l'événement pour que PrestaShop recalcule le prix (une seule fois)
            $input.trigger('change');
            $input.trigger('touchspin.on.startspin');
            // Afficher le message de limitation
            showQuantityLimitMessage(resp.platform_stock);
        }
        
        // Ajouter un écouteur pour bloquer les saisies au-dessus du max avec debounce
        let inputTimeout;
        $input.off('input.platformLimit').on('input.platformLimit', function() {
            const $this = $(this);
            const val = parseInt($this.val());
            
            if (val > resp.platform_stock) {
                $this.val(resp.platform_stock);
                
                // Afficher le message de limitation
                showQuantityLimitMessage(resp.platform_stock);
                
                // Déclencher 'change' avec debounce pour éviter les rafraîchissements excessifs
                clearTimeout(inputTimeout);
                inputTimeout = setTimeout(function() {
                    $this.trigger('change');
                }, 500); // Attendre 500ms après la dernière saisie
            }
        });
    } else if (limitToTotalStock && resp.total_quantity) {
        // Limiter au stock total national pour les produits fin de vie national
        $input.attr('max', resp.total_quantity).prop('disabled', false);
        $buttons.prop('disabled', false).removeClass('disabled').css({'pointer-events': 'auto', 'opacity': '1'});
        
        // Corriger si la quantité actuelle dépasse le stock total
        const currentQty = parseInt($input.val()) || 0;
        if (currentQty > resp.total_quantity) {
            $input.val(resp.total_quantity);
            // Déclencher l'événement pour que PrestaShop recalcule le prix
            $input.trigger('change');
            $input.trigger('touchspin.on.startspin');
            // Afficher le message de limitation
            showQuantityLimitMessage(resp.total_quantity);
        }
        
        // Ajouter un écouteur pour bloquer les saisies au-dessus du max avec debounce
        let inputTimeout;
        $input.off('input.totalLimit').on('input.totalLimit', function() {
            const $this = $(this);
            const val = parseInt($this.val());
            
            if (val > resp.total_quantity) {
                $this.val(resp.total_quantity);
                
                // Afficher le message de limitation
                showQuantityLimitMessage(resp.total_quantity);
                
                // Déclencher 'change' avec debounce pour éviter les rafraîchissements excessifs
                clearTimeout(inputTimeout);
                inputTimeout = setTimeout(function() {
                    $this.trigger('change');
                }, 500);
            }
        });
    } else {
        // Pas de limitation pour les produits normaux
        $input.removeAttr('max').prop('disabled', false);
        $buttons.prop('disabled', false).removeClass('disabled').css({'pointer-events': 'auto', 'opacity': '1'});
    }
    
    // Mise à jour de la référence
    $("#reference_to_display").text(resp.reference_to_display);
    
    // Gestion magasin sélectionné
    updateStoreDisplay(resp);
    
    const isPackPage = ($('#is_pack').val() == '1') || ($('#is_pack').length > 0) || (Array.isArray(window.ckcPackList) && window.ckcPackList.length);
    const packItemsForStores = (Array.isArray(resp && resp.pack_items) && resp.pack_items.length) ? resp.pack_items : null;

    const isFinDeVieForStores = ui && ui.state
        ? (ui.state === 'fin_de_vie' || ui.state === 'fin_de_vie_national')
        : (resp.fin_de_vie || resp.fin_de_vie_national);

    updateStoresModal(resp.stores, isFinDeVieForStores, packItemsForStores, i18n);
}

// Fonctions utilitaires pour le panier
function disableAddToCartButton() {
    $('.add-to-cart, .product-add-to-cart button[type="submit"]').prop('disabled', true).addClass('disabled');
}

function enableAddToCartButton() {
    $('.add-to-cart, .product-add-to-cart button[type="submit"]').prop('disabled', false).removeClass('disabled');
}

// Afficher un message de limitation de quantité
function showQuantityLimitMessage(maxQty) {
    // Supprimer les anciens messages
    $('.quantity-limit-message').remove();
    
    // Créer le message
    const $message = $('<div class="quantity-limit-message" style="' +
        'color: #dc3545; ' +
        'font-size: 13px; ' +
        'margin-top: 5px; ' +
        'font-weight: 500;' +
    '">Quantité limitée à ' + maxQty + ' unités</div>');
    
    // Trouver le conteneur de quantité et ajouter le message en dessous
    const $qtyContainer = $('.product-quantity, .qty').first();
    if ($qtyContainer.length) {
        $qtyContainer.after($message);
    } else {
        // Fallback : ajouter après l'input
        $('input[name="qty"], #quantity_wanted').closest('.form-group, .input-group').after($message);
    }
    
    // Supprimer après 3 secondes avec animation
    setTimeout(function() {
        $message.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

// Fonction pour gérer l'affichage du magasin sélectionné
function updateStoreDisplay(resp) {
    const i18n = resp.i18n;
    if (resp.id_store) {
        $(".store-selected").show();
        $(".store-selected .nameStore").html(
            '<img src="/modules/ckc_stores_stock/assets/img/nameStore.webp" alt="Magasin" style="width: 50px; height: 50px;">' +
            '<span class="font-weight-normal">Votre magasin</span> <span class="font-weight-bold">Districlos ' + resp.store_name.replace('DISTRICLOS ', '').replace('Districlos ', '') + '</span><br>'
        );
        $(".store-selected .productQuantity").html(resp.quantity > 0 ? `${resp.quantity} ${i18n.product_in_stock}` : i18n.arrival);
        $("#modalProductStock-" + resp.id_store).html(getStockSvg(resp.quantity));
    } else {
        $(".store-selected").hide();
    }
}

// Fonction pour gérer la modal des magasins
function updateStoresModal(stores, isFinDeVie, packItems = null, i18n = {}) {
    const hasPackItems = Array.isArray(packItems) && packItems.length > 0;
    const outOfStockLabel = isFinDeVie
        ? i18n.available_10_days
        : i18n.arrival;

    stores.forEach(store => {
        if (hasPackItems) {
            // Stock pack possible par magasin = min(floor(stock_item_magasin / qty_in_pack))
            let packPossibleStore = null;
            const lines = packItems.map(item => {
                const itemStore = Array.isArray(item.stores)
                    ? item.stores.find(s => Number(s.id) === Number(store.id))
                    : null;
                const itemQty = itemStore ? Number(itemStore.stock) : 0;
                const itemSeuil = itemStore ? !!itemStore.seuil : true;
                const qtyInPack = Number(item.qty_in_pack || 1) > 0 ? Number(item.qty_in_pack || 1) : 1;
                const possibleForItemStore = (itemQty > 0 && itemSeuil) ? Math.floor(itemQty / qtyInPack) : 0;
                packPossibleStore = (packPossibleStore === null) ? possibleForItemStore : Math.min(packPossibleStore, possibleForItemStore);

                // Afficher le stock pack possible pour ce composant (stock / qty du pack)
                const itemStatus = (possibleForItemStore > 0) ? `${possibleForItemStore} en stock` : outOfStockLabel;
                const itemName = (item.name || 'Produit').trim() || 'Produit';
                return `
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                        <svg fill="#79c046" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="width:16px; min-width:16px; margin-top:2px;">
                            <path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/>
                        </svg>
                        <div>
                            <div style="color:#000; line-height:1.2; font-size: 14px;">${itemName}</div>
                            <div style="color:#79c046; line-height:1.2; font-size: 15px;">${itemStatus}</div>
                        </div>
                    </div>
                `;
            }).join('');
            const $el = $("#modalProductStock-" + store.id);
            $el.addClass('ckc-modal-product-stock--pack').html(
                lines
            );
        } else {
            const stockText = store.stock > 0 && store.seuil ? `${store.stock} en stock` : outOfStockLabel;
            const $el = $("#modalProductStock-" + store.id);
            $el.removeClass('ckc-modal-product-stock--pack').html(getStockSvg(stockText));
        }
        
        if (store.selected) {
            $('.shopId-' + store.id).hide().next().show();
            // Ne pas écraser le texte principal avec "à <magasin>"
            // (les textes fin de vie / victime / packs sont gérés par updateStockDisplay)
            
            if (!isFinDeVie) {
                $('#selectShop').text(i18n.change_store);
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
    fetchStockData();

    prestashop.on('updatedProduct', function () {
        fetchStockData();
    });

    // Utiliser un flag pour éviter les appels multiples lors d'ajaxComplete
    let isAjaxProcessing = false;
    
    $(document).on('ajaxComplete', function (event, xhr, settings) {
        if (settings.url.includes('product') || settings.url.includes('favoritestore')) {
            // Éviter les appels multiples simultanés
            if (!isAjaxProcessing) {
                isAjaxProcessing = true;
                if (($('#is_pack').val() == '1') || ($('#is_pack').length > 0) || (Array.isArray(window.ckcPackList) && window.ckcPackList.length)) {
                    fetchStockData(true, window.ckcPackList);
                } else {
                    fetchStockData();
                }
                
                // Réinitialiser le flag après 200ms
                setTimeout(function() {
                    isAjaxProcessing = false;
                }, 200);
            }
        }
    });
});

// Auto: si on est sur un pack (pm_advancedpack), envoyer la liste des composants si disponible
$(document).ready(function () {
    const isPack = ($('#is_pack').val() == '1') || ($('#is_pack').length > 0);
    if (!isPack) return;

    if (Array.isArray(window.ckcPackList) && window.ckcPackList.length) {
        fetchStockData(true, window.ckcPackList);
    }
});

// Pack: après changement de déclinaison, laisser AP5 mettre à jour #idCombination puis fetch (rebuild dans fetchStockData)
function schedulePackStockRefreshFromCombination() {
    const isPack = ($('#is_pack').val() == '1') || ($('#is_pack').length > 0);
    if (!isPack) return;
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            fetchStockData(true, window.ckcPackList);
        });
    });
}

$(document).on('change input', '#idCombination, .ap5-attribute-select, select.product-combination', schedulePackStockRefreshFromCombination);


// Icône info pour productLimit avec wrapper pour tooltip
function createInfoIcon(tooltipContent) {
    return '<span class="info-icon-wrapper">' +
           '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#79c046" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 5px; vertical-align: middle; cursor: pointer;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>' +
           '<span class="custom-tooltip">' + tooltipContent + '</span>' +
           '</span>';
}

if ($('.delivery-option input').length > 0) {
    $('.delivery-option input').change(function () {
        let selectedDelivery = $('input[name*="delivery_option"]:checked').attr('id');

        $.post(
            '/modules/ckc_stores_stock/ajax_checkout.php',
            {selectedDelivery: selectedDelivery},
            null,
            "json"
        ).then(function (resp) {
            // Mettre à jour le nom du magasin (toujours visible)
            $('#stock-summary-content .nameStore').html(
                '<img src="/modules/ckc_stores_stock/assets/img/nameStore.webp" alt="Magasin" style="width: 50px; height: 50px;">' +
                '<span class="font-weight-normal">Votre magasin</span> <span class="font-weight-bold">Districlos ' + resp.data.stock.store_name.replace('DISTRICLOS ', '').replace('Districlos ', '') + '</span>'
            );
            
            // Vérifier s'il y a des produits normaux (pas fin de série)
            const hasNormalProducts = resp.data.stocks && resp.data.stocks.length > 0;
            const hasFinSerieProducts = resp.data.stock.hasFinSerieProducts && resp.data.finSerieStocks && resp.data.finSerieStocks.length > 0;
            
            // Supprimer l'ancienne section fin de série
            $('#stock-summary-content .fin-serie-section').remove();
            
            if (hasNormalProducts) {
                // S'assurer que les éléments existent
                if ($('#stock-summary-content .productLimit').length === 0) {
                    $('#stock-summary-content .nameStore').after('<p class="productLimit" style="color:#79c046;"></p>');
                }
                if ($('#stock-summary-content .products').length === 0) {
                    $('#stock-summary-content .productLimit').after('<div class="products"></div>');
                }
                
                // Supprimer la section .text (elle ne doit plus être utilisée, tout est dans le tooltip)
                $('#stock-summary-content .text').remove();
                
                // Afficher la section normale pour les produits normaux
                if (resp.data.stock.isClickAndCollect) {
                    $('#stock-summary-content .card-header').html('Click & Collect');
                    if (resp.data.stock.stock_ok) {
                        var tooltipText = '';
                        // Message spécial pour le store Essonne (19)
                        if (resp.data.stock.store_id == 19) {
                            tooltipText = 'Disponibilité sous 5 jours ouvrés en magasin, selon stock. Le magasin vous contactera dès que votre commande sera prête.';
                        } else {
                            tooltipText = 'Vous pouvez venir récupérer les produits en stock dans votre magasin 2h après votre commande.';
                        }
                        $('#stock-summary-content .productLimit').html('Super ! Un ou plusieurs produits sont en stock ( hors plateforme ) ' + createInfoIcon(tooltipText));
                    } else {
                        var tooltipText = '';
                        // Message spécial pour le store Essonne (19)
                        if (resp.data.stock.store_id == 19) {
                            tooltipText = 'Disponibilité sous 5 jours ouvrés en magasin, selon stock. Le magasin vous contactera dès que votre commande sera prête.';
                        } else {
                            tooltipText = 'Vous pouvez venir récupérer les produits en stock dans votre magasin.<br><br>Pour les produits en cours de réapprovisionnement, nous vous appellerons aussitôt ces derniers disponibles.';
                        }
                        $('#stock-summary-content .productLimit').html('Un ou des produits sont en cours de réapprovisionnement ( hors plateforme ) ' + createInfoIcon(tooltipText));
                    }
                } else {
                    $('#stock-summary-content .card-header').html('Livraison');
                    var tooltipText = 'La livraison se fait sur rendez-vous uniquement. Nous allons vous contacter suite à votre commande pour déterminer cette date.<br><br>Le délais moyen pour vos produits est entre 10 à 15 jours ouvrés (sous réserve de stock disponible).';
                    $('#stock-summary-content .productLimit').html('Notre magasin vous livre à domicile ou sur votre chantier ! ' + createInfoIcon(tooltipText));
                }
                
                $('#stock-summary-content .products').empty();
                
                for (let i = 0; i < resp.data.stocks.length; i++) {
                    var image = '';
                    var statusColor = '';
                    var statusText = '';
                    
                    if (resp.data.stocks[i].stock_ok) {
                        image = '<img src="/modules/ckc_stores_stock/assets/img/selectionner.webp" class="unveil-loaded">';
                        statusColor = '#79C046';
                        statusText = 'Disponible en magasin';
                    } else {
                        image = '<img src="/modules/ckc_stores_stock/assets/img/moins.webp" class="unveil-loaded">';
                        statusColor = '#F6AC00';
                        statusText = 'Forte demande : expédition/retrait sous 10 jours';
                    }
                    
                    $('#stock-summary-content .products').append(
                        '<p class="productQuantity" style="color:#000000;">' + 
                        image + 
                        '<span>' +
                        resp.data.stocks[i].product_name + 
                        '<br><small style="color: ' + statusColor + '; font-size: 11px;">' + 
                        statusText + 
                        '</small></span></p>'
                    );
                }
            } else {
                // Aucun produit normal, supprimer/vider les sections
                $('#stock-summary-content .productLimit').remove();
                $('#stock-summary-content .products').remove();
                $('#stock-summary-content .text').remove();
            }
            
            // Gérer la section des produits fin de série
            if (hasFinSerieProducts) {
                var finSerieTooltip = 'Ce produit est disponible sur notre plateforme logistique, pour une meilleure disponibilité.';
                let finSerieHtml = '<div class="fin-serie-section mt-3">' +
                    '<p class="productLimit" style="color:#79c046;">Un ou des produits sont en stock sur plateforme ' + createInfoIcon(finSerieTooltip) + '</p>' +
                    '<div class="products fin-serie-products">';
                
                resp.data.finSerieStocks.forEach(function(productStock) {
                    finSerieHtml += '<p class="productQuantity" style="color:#000000;">' +
                        '<img src="/modules/ckc_stores_stock/assets/img/selectionner.webp" class="unveil-loaded">' +
                        productStock.product_name + '</p>';
                });
                
                finSerieHtml += '</div><div class="text"><p>Ce produit est disponible sur notre plateforme logistique, pour une meilleure disponibilité.</p></div></div>';
                
                // Insérer la section fin de série
                if (hasNormalProducts && $('#stock-summary-content .text').length > 0) {
                    // Après le texte explicatif s'il existe
                    $('#stock-summary-content .text').after(finSerieHtml);
                } else {
                    // Directement après le nom du magasin
                    $('#stock-summary-content .nameStore').after(finSerieHtml);
                }
            }

        });
    })
}
