// Réinitialise la sélection pickup si le panier a changé
(function() {
    var currentCartId = (window.prestashop && prestashop.cart && prestashop.cart.id) ? prestashop.cart.id : null;
    var lastCartId = localStorage.getItem('custompickup_last_cart');
    if (currentCartId && lastCartId && currentCartId !== lastCartId) {
        localStorage.removeItem('custompickup_last_selected');
    }
    if (currentCartId) {
        localStorage.setItem('custompickup_last_cart', currentCartId);
    }
})();

function showOtherPickups() {
    document.getElementById('other-pickups').style.display = 'block';
    document.getElementById('pickup-selected').style.display = 'none';
    document.getElementById('show-other-pickups').style.display = 'none';
}

function showSelectedPickup() {
    document.getElementById('other-pickups').style.display = 'none';
    document.getElementById('pickup-selected').style.display = 'block';
    document.getElementById('show-other-pickups').style.display = 'flex';
}

const MAIN_PICKUP_CARRIER_ID = (typeof custompickup_main_carrier_id !== 'undefined' && custompickup_main_carrier_id)
    ? custompickup_main_carrier_id
    : 0;

function disablePickupSelectionIfNotMainCarrier() {
    const mainCarrierRadio = document.querySelector('input[type="radio"][name^="delivery_option"][value*="' + MAIN_PICKUP_CARRIER_ID + '"]');
    const pickupRadios = document.querySelectorAll('input[name="selected_pickup"]');
    const pickupSectionContainer = document.getElementById('pickup-section-container');

    if (pickupSectionContainer) {
        if (mainCarrierRadio && !mainCarrierRadio.checked) {
            pickupRadios.forEach(radio => {
                radio.disabled = true;
                radio.checked = false;
            });
            pickupSectionContainer.style.display = 'none';
        } else {
            pickupRadios.forEach(radio => {
                radio.disabled = false;
            });
            pickupSectionContainer.style.display = '';
            if (window.pickupMap && typeof window.pickupMap.invalidateSize === 'function') {
                setTimeout(() => window.pickupMap.invalidateSize(), 200);
            }
        }
    }
}

// Stocker les handlers pour pouvoir les retirer
const pickupChangeHandlers = new WeakMap();

function initPickupSelectionListeners() {
    const radios = document.querySelectorAll('input[name="selected_pickup"]');
    radios.forEach(function (radio) {
        // Retirer l'ancien listener s'il existe
        const oldHandler = pickupChangeHandlers.get(radio);
        if (oldHandler) {
            radio.removeEventListener('change', oldHandler);
        }
        
        const handler = function () {
            // Éviter les déclenchements en cascade
            if (window.custompickupRestoring) {
                return;
            }
            
            const carrierId = this.dataset.carrierId;
            const ckcStoreId = this.dataset.ckcStoreId;
            const storeId = this.value;
            const storeName = this.dataset.storeName;

            // ANNULER les timeouts précédents pour éviter les conflits
            if (window.custompickupRestorePostTimeout) {
                clearTimeout(window.custompickupRestorePostTimeout);
                window.custompickupRestorePostTimeout = null;
            }
            if (window.custompickupDisableFlagTimeout) {
                clearTimeout(window.custompickupDisableFlagTimeout);
                window.custompickupDisableFlagTimeout = null;
            }

            // Activer le flag IMMÉDIATEMENT pour bloquer les appels AJAX du module ckc_stores_stock
            window.custompickupJustSelected = true;
            window.custompickupJustSelectedStoreId = storeId;
            window.custompickupJustSelectedCarrierId = carrierId;
            window.custompickupSelectionTimestamp = Date.now();
            
            // Intercepter $.post IMMÉDIATEMENT pour bloquer les appels AJAX du module ckc_stores_stock
            // Le module fait un appel sans id_store qui récupère le magasin le plus proche
            const originalPost = $.post;
            let postIntercepted = false;
            
            // Stocker l'interception dans window pour qu'elle soit accessible partout
            window.custompickupOriginalPost = originalPost;
            
            $.post = function(url, data, success, dataType) {
                // Vérifier si c'est un appel vers ajax_checkout.php
                if (url.includes('ajax_checkout.php')) {
                    // Si c'est notre propre appel (avec id_store), le laisser passer
                    if (data && typeof data === 'object' && data.id_store) {
                        return originalPost.apply(this, arguments);
                    }
                    
                    // Si c'est un appel du module ckc_stores_stock (sans id_store) et qu'une sélection récente a été faite, le bloquer
                    if (window.custompickupJustSelected) {
                        postIntercepted = true;
                        // Retourner une promesse résolue avec des données vides pour éviter les erreurs
                        const deferred = $.Deferred();
                        setTimeout(() => {
                            deferred.resolve({success: false, data: {}});
                        }, 0);
                        return deferred.promise();
                    }
                }
                return originalPost.apply(this, arguments);
            };
            
            // Désactiver temporairement le listener du module ckc_stores_stock
            // en ajoutant un attribut data pour qu'il puisse vérifier s'il doit s'exécuter
            const deliveryInputs = document.querySelectorAll('.delivery-option input');
            deliveryInputs.forEach(input => {
                input.setAttribute('data-custompickup-blocking', 'true');
            });
            
            // Sauvegarder le storeId sélectionné dans la mémoire
            localStorage.setItem('custompickup_last_selected_store', storeId);
            
            // Sauvegarder aussi l'ID d'adresse pour vérifier les changements
            // Essayer plusieurs méthodes pour récupérer l'ID d'adresse
            let addressId = this.dataset.idAddress || 
                           this.getAttribute('data-id-address') ||
                           document.querySelector('input[name="id_address_delivery"]:checked')?.value || 
                           document.querySelector('select[name="id_address_delivery"]')?.value ||
                           document.querySelector('[name="id_address_delivery"]')?.value;
            
            // Si toujours pas trouvé, essayer de récupérer depuis le formulaire
            if (!addressId) {
                const addressInputs = document.querySelectorAll('input[name="id_address_delivery"]');
                addressInputs.forEach(input => {
                    if (input.checked || input.selected) {
                        addressId = input.value;
                    }
                });
            }
            
            if (addressId) {
                localStorage.setItem('custompickup_last_address_id', addressId);
            }

            fetch(window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '') + '/modules/custompickup/custompickup.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: new URLSearchParams({
                    ajax: 1,
                    action: 'getCartId'
                })
            })
                .then(response => response.json())
                .then(data => {
                    const cartId = data.cart_id;
                    if (!cartId) return;

                    document.cookie = `custompickup_id_store_${cartId}=${storeId}; path=/; max-age=86400`;

                    // Appeler immédiatement l'AJAX pour mettre à jour le stock (sans attendre)
                    $.post('/modules/ckc_stores_stock/ajax_checkout.php', {
                        ajax: 1,
                        selectedDelivery: carrierId,
                        id_store: ckcStoreId
                    }, function (resp) {
                        // Vérifier que c'est toujours la même sélection
                        if (window.custompickupJustSelectedStoreId !== storeId) {
                            return;
                        }
                        
                        // Mettre à jour le nom du magasin (toujours visible)
                        if (resp.data && resp.data.stock && resp.data.stock.store_name) {
                            $('#stock-summary-content .nameStore').html(resp.data.stock.store_name);
                        }
                        
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
                            if ($('#stock-summary-content .text').length === 0) {
                                $('#stock-summary-content .products').after('<div class="text"></div>');
                            }
                            
                            // Afficher la section normale pour les produits normaux
                            if (resp.data.stock.isClickAndCollect) {
                                $('#stock-summary-content .card-header').html('Click & Collect');
                                if (resp.data.stock.stock_ok) {
                                    $('#stock-summary-content .productLimit').html('Super ! Un ou plusieurs produits sont en stock ( hors plateforme )');
                                    // Message spécial pour le store Essonne (19)
                                    if (resp.data.stock.store_id == 19) {
                                        $('#stock-summary-content .text').html('<p>Disponibilité sous 5 jours ouvrés en magasin, selon stock. Le magasin vous contactera dès que votre commande sera prête.</p>');
                                    } else {
                                        $('#stock-summary-content .text').html('<p>Vous pouvez venir récupérer les produits en stock dans votre magasin 2h après votre commande.</p>')
                                    }
                                } else {
                                    $('#stock-summary-content .productLimit').html('Un ou des produits sont en cours de réapprovisionnement ( hors plateforme )');
                                    // Message spécial pour le store Essonne (19)
                                    if (resp.data.stock.store_id == 19) {
                                        $('#stock-summary-content .text').html('<p>Disponibilité sous 5 jours ouvrés en magasin, selon stock. Le magasin vous contactera dès que votre commande sera prête.</p>');
                                    } else {
                                        $('#stock-summary-content .text').html('<p>Vous pouvez venir récupérer les produits en stock dans votre magasin.</p> <p>Pour les produits en cours de réapprovisionnement, nous vous appellerons aussitôt ces derniers disponibles.</p>')
                                    }
                                }
                            } else {
                                $('#stock-summary-content .card-header').html('Livraison');
                                $('#stock-summary-content .productLimit').html('Notre magasin vous livre à domicile ou sur votre chantier !');
                                $('#stock-summary-content .text').html('<p>La livraison se fait sur rendez-vous uniquement. Nous allons vous contacter suite à votre commande pour déterminer cette date.</p><p>Le délais moyen pour vos produits est entre 10 à 15 jours ouvrés (sous réserve de stock disponible).</p>')
                            }
                            
                            $('#stock-summary-content .products').empty();
                            
                            for (let i = 0; i < resp.data.stocks.length; i++) {
                                if (resp.data.stocks[i].stock_ok) {
                                    var image = '<img src="/modules/ckc_stores_stock/assets/img/selectionner.webp" class="unveil-loaded">'
                                } else {
                                    var image = '<img src="/modules/ckc_stores_stock/assets/img/moins.webp" class="unveil-loaded">'
                                }
                                $('#stock-summary-content .products').append('<p class="productQuantity" style="color:#000000;">' + image + resp.data.stocks[i].product_name + '<br></p>')
                            }
                        } else {
                            // Aucun produit normal, supprimer/vider les sections
                            $('#stock-summary-content .productLimit').remove();
                            $('#stock-summary-content .products').remove();
                            $('#stock-summary-content .text').remove();
                        }
                        
                        // Gérer la section des produits fin de série
                        if (hasFinSerieProducts) {
                            let finSerieHtml = '<div class="fin-serie-section mt-3">' +
                                '<p class="productLimit" style="color:#79c046;">Un ou des produits sont en stock sur plateforme</p>' +
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
                    }, "json");

                    // Appeler setCustomPickupCarrier immédiatement (sans délai)
                    fetch(window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '') + '/modules/custompickup/custompickup.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        body: new URLSearchParams({
                            ajax: 1,
                            action: 'setCustomPickupCarrier',
                            id_carrier: carrierId, // Transporteur spécifique du magasin
                            id_store: storeId,
                            id_cart: cartId
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.success && typeof prestashop !== 'undefined') {
                                // Utiliser le transporteur principal pour cocher le radio, pas le transporteur spécifique du magasin
                                const mainCarrierId = MAIN_PICKUP_CARRIER_ID;                                    
                                const deliveryInput = [...document.querySelectorAll('input[name^="delivery_option"]')]
                                    .find(input => input.value.endsWith('=' + mainCarrierId));
                                
                                if (deliveryInput) {
                                    // Ne cocher que si pas déjà coché pour éviter la boucle
                                    if (!deliveryInput.checked) {
                                        // Le flag et l'interception de $.post sont déjà activés au début du handler
                                        deliveryInput.checked = true;
                                    }
                                }
                                
                                // Stocker le timestamp de cette sélection
                                const selectionTimestamp = window.custompickupSelectionTimestamp;
                                
                                // Réactiver $.post après un délai réduit pour laisser notre callback s'exécuter
                                window.custompickupRestorePostTimeout = setTimeout(() => {
                                    // Vérifier que c'est toujours la même sélection
                                    if (window.custompickupSelectionTimestamp !== selectionTimestamp) {
                                        return;
                                    }
                                    
                                    if (window.custompickupOriginalPost) {
                                        $.post = window.custompickupOriginalPost;
                                        delete window.custompickupOriginalPost;
                                    } else {
                                        $.post = originalPost;
                                    }
                                    
                                    // Réactiver les listeners du module ckc_stores_stock
                                    const deliveryInputs = document.querySelectorAll('.delivery-option input');
                                    deliveryInputs.forEach(input => {
                                        input.removeAttribute('data-custompickup-blocking');
                                    });
                                    
                                    // Désactiver le flag après que toutes les opérations soient terminées
                                    window.custompickupDisableFlagTimeout = setTimeout(() => {
                                        // Double vérification du timestamp et du storeId
                                        if (window.custompickupSelectionTimestamp === selectionTimestamp && 
                                            window.custompickupJustSelectedStoreId === storeId) {
                                            window.custompickupJustSelected = false;
                                            window.custompickupJustSelectedStoreId = null;
                                            window.custompickupJustSelectedCarrierId = null;
                                            window.custompickupSelectionTimestamp = null;
                                        }
                                    }, 500);
                                }, 500);
                                
                                // Ne PAS émettre updateDeliveryForm pour éviter le clignotement
                                // Le bloc gauche est déjà mis à jour dans le handler
                                // Le stock est déjà mis à jour via l'appel AJAX direct ci-dessus
                            }
                        });
                });

            // Affichage visuel de la sélection (non supprimé car utile pour l'UI)
            const selectedStoreName = this.dataset.storeName;
            const storeAddress = this.dataset.storeAddress;
            const storeCity = this.dataset.storeCity;
            const distanceKm = this.dataset.distanceKm;
            const phone = this.dataset.phone;
            const hours = this.dataset.hours;

            let html = '<label><strong>' + selectedStoreName + '</strong><br>';
            if (distanceKm) {
                html += '<p class="pickup-distance">Se situe à ' + distanceKm + ' km de chez vous</p>';
            }
            html += '<p class="pickup-address"><img src="/modules/custompickup/views/img/icons/pickup-point.svg" width="16" height="16" class="icon-space">' + storeAddress + ' - ' + storeCity + '</p>';
            if (phone) {
                html += '<p class="pickup-phone"><img src="/modules/custompickup/views/img/icons/pickup-phone.svg" width="16" height="16" class="icon-space">' + phone + '</p>';
            }
            if (hours) {
                html += '<div class="opening-hours" data-hours=\'' + hours.replace(/'/g, '&apos;') + '\'><img src="/modules/custompickup/views/img/icons/pickup-clock.svg" width="16" height="16" class="icon-space"><ul class="hours-list"></ul></div>';
            }
            html += '</label>';

            document.getElementById('pickup-selected').innerHTML = html;
            renderOpeningHours();
            showSelectedPickup();
        };
        
        // Stocker le handler pour pouvoir le retirer plus tard
        pickupChangeHandlers.set(radio, handler);
        radio.addEventListener('change', handler);
    });
    renderOpeningHours();
}

// Variable pour éviter les requêtes multiples
let isUpdatingStock = false;

// Mise à jour du cart-stock.tpl pour les retraits
function updateCartStockForPickup(carrierId, storeId) {
    if (isUpdatingStock) {
        return;
    }
    
    // Si une sélection récente a été faite, vérifier que c'est le bon magasin ET le bon transporteur
    if (window.custompickupJustSelected && window.custompickupJustSelectedStoreId) {
        if (window.custompickupJustSelectedStoreId !== storeId) {
            return;
        }
        // Si le transporteur est différent mais que c'est le transporteur principal, c'est OK
        // (le transporteur principal 1682 peut être utilisé pour tous les magasins)
        if (window.custompickupJustSelectedCarrierId && 
            window.custompickupJustSelectedCarrierId !== carrierId && 
            carrierId !== MAIN_PICKUP_CARRIER_ID) {
            return;
        }
    }
    
    // Si une sélection récente a été faite mais qu'on n'a pas de storeId dans le flag, utiliser le storeId passé
    // Cela peut arriver si updateCartStockForPickup est appelé avant que le flag ne soit complètement initialisé
    if (window.custompickupJustSelected && !window.custompickupJustSelectedStoreId) {
        window.custompickupJustSelectedStoreId = storeId;
        window.custompickupJustSelectedCarrierId = carrierId;
    }

    if (window.location.pathname.includes('order') || window.location.pathname.includes('commande') || document.getElementById('stock-summary-content')) {

        // Marquer le début de la requête
        isUpdatingStock = true;

        const selectedDelivery = 'delivery_option_' + carrierId;

        const postData = {
            ajax: "1",
            action: "stock",
            selectedDelivery: selectedDelivery,
            debug: "1",
            timestamp: Date.now() // Force une nouvelle requête
        };

        if (localStorage.getItem('custompickup_last_selected_store')) {
            postData.id_store = localStorage.getItem('custompickup_last_selected_store');
        }else if (storeId !== null) {
            postData.id_store = storeId;
        } else {
        }

        fetch(window.location.origin + '/commande', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            },
            body: new URLSearchParams(postData)
        })
            .then(response => {
                return response.json();
            })
            .then(function (resp) {
                if (resp.data && resp.data.stock) {
                    updateCartStockDisplay(resp.data);

                    // Marquer que le cart-stock a été mis à jour par le module seulement pour les retraits
                    if (resp.data.stock.isClickAndCollect) {
                        window.custompickupStockUpdated = true;
                        setTimeout(() => {
                            window.custompickupStockUpdated = false;
                        }, 1000);
                    }
                }

                // Marquer la fin de la requête
                isUpdatingStock = false;
            })
            .catch(function(error) {
                // Marquer la fin de la requête même en cas d'erreur
                isUpdatingStock = false;
            });
    }
}

// Mise à jour de l'affichage du cart-stock.tpl
function updateCartStockDisplay(stockData) {
    if (!stockData.stock) {
        return;
    }

    // Mise à jour du header
    if (stockData.stock.isClickAndCollect) {
        $('#stock-summary-content .card-header').html('Click And Collect');
    } else {
        $('#stock-summary-content .card-header').html('Livraison');
    }

    // Mise à jour du message de stock
    if (stockData.stock.isClickAndCollect) {
        if (stockData.stock.stock_ok) {
            $('#stock-summary-content .productLimit').html('Super ! Tous vos produits sont en stock');
            // Message spécial pour le store Essonne (19)
            if (stockData.stock.store_id == 19) {
                $('#stock-summary-content .text').html('<p>Disponibilité sous 5 jours ouvrés en magasin, selon stock. Le magasin vous contactera dès que votre commande sera prête.</p>');
            } else {
                $('#stock-summary-content .text').html('<p>Vous pouvez venir récupérer les produits en stock dans votre magasin 2h après votre commande.</p>');
            }
        } else {
            $('#stock-summary-content .productLimit').html('Un ou des produits sont en cours de réapprovisionnement');
            // Message spécial pour le store Essonne (19)
            if (stockData.stock.store_id == 19) {
                $('#stock-summary-content .text').html('<p>Disponibilité sous 5 jours ouvrés en magasin, selon stock. Le magasin vous contactera dès que votre commande sera prête.</p>');
            } else {
                $('#stock-summary-content .text').html('<p>Vous pouvez venir récupérer les produits en stock dans votre magasin.</p> <p>Pour les produits en cours de réapprovisionnement, nous vous appellerons aussitôt ces derniers disponibles.</p>');
            }
        }
    } else {
        $('#stock-summary-content .productLimit').html('Notre magasin vous livre à domicile ou sur votre chantier !');
        $('#stock-summary-content .text').html('<p>La livraison se fait sur rendez-vous uniquement. Nous allons vous contacter suite à votre commande pour déterminer cette date.</p><p>Le délais moyen pour vos produits est entre 10 à 15 jours ouvrés (sous réserve de stock disponible).</p>');
    }

    // Mise à jour de la liste des produits
    $('#stock-summary-content .products').empty();
    if (stockData.stocks) {
        for (let i = 0; i < stockData.stocks.length; i++) {
            if (stockData.stocks[i].stock_ok) {
                var image = '<img src="https://www.cloture-discount.fr/themes/auto_store/assets/img/selectionner.png" class="unveil-loaded">';
            } else {
                var image = '<img src="https://www.cloture-discount.fr/themes/auto_store/assets/img/moins.svg" class="unveil-loaded">';
            }
            $('#stock-summary-content .products').append('<p class="productQuantity" style="color:#000000;">' + image + stockData.stocks[i].product_name + '<br></p>');
        }
    }

    // Mise à jour du nom du magasin
    if (stockData.stock.store_name) {
        $('#stock-summary-content .nameStore').html(stockData.stock.store_name);
    }
}

function initCarrierEvents() {
    document.querySelectorAll('input[type="radio"][name^="delivery_option"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            disablePickupSelectionIfNotMainCarrier();

            // ✅ NOUVEAU : Mise à jour du cart-stock lors du changement de transporteur
            updateCartStockOnCarrierChange(this);
        });
    });
}

// Mise à jour du cart-stock lors du changement de transporteur
function updateCartStockOnCarrierChange(radioElement) {
    if (!window.location.pathname.includes('order') && !window.location.pathname.includes('commande') && !document.getElementById('stock-summary-content')) {
        return;
    }

    // Extraire l'ID du transporteur depuis la valeur du radio
    const carrierValue = radioElement.value;
    const carrierId = carrierValue.split(',')[0]; // Format: "1682,"

    if (!carrierId) {
        return;
    }

    // Vérifier si c'est un transporteur de retrait
    const isPickupCarrier = carrierId == MAIN_PICKUP_CARRIER_ID ||
        radioElement.closest('label')?.textContent?.includes('Retrait');
    
    // Si une sélection de retrait récente a été faite ET qu'on reste sur un retrait,
    // ne pas mettre à jour le stock ici (le stock sera mis à jour dans le handler de sélection)
    // MAIS si on passe à une livraison classique, on doit nettoyer et mettre à jour
    if (window.custompickupJustSelected && isPickupCarrier) {
        return;
    }

    if (isPickupCarrier) {

        // Récupérer le storeId depuis la sélection de pickup OU depuis la mémoire
        const selectedPickupRadio = document.querySelector('input[name="selected_pickup"]:checked');
        let storeId = null;

        if (selectedPickupRadio) {
            storeId = selectedPickupRadio.value;
        } else {
            // Essayer de récupérer depuis la mémoire (localStorage) SEULEMENT si pas de sélection récente
            if (!window.custompickupJustSelected) {
                const rememberedStoreId = localStorage.getItem('custompickup_last_selected_store');
                if (rememberedStoreId) {
                    storeId = rememberedStoreId;

                    // Restaurer visuellement la sélection
                    const radioToRestore = document.querySelector(`input[name="selected_pickup"][value="${storeId}"]`);
                    if (radioToRestore) {
                        radioToRestore.checked = true;
                        // Déclencher l'événement change pour mettre à jour l'affichage
                        radioToRestore.dispatchEvent(new Event('change'));
                    }
                }
            } else {
                // Si une sélection récente a été faite, utiliser le magasin sélectionné
                storeId = window.custompickupJustSelectedStoreId;
            }
        }

        if (storeId) {
            updateCartStockForPickup(carrierId, storeId);
        }
    } else {
        // Annuler tous les timeouts en cours
        if (window.custompickupRestorePostTimeout) {
            clearTimeout(window.custompickupRestorePostTimeout);
            window.custompickupRestorePostTimeout = null;
        }
        if (window.custompickupDisableFlagTimeout) {
            clearTimeout(window.custompickupDisableFlagTimeout);
            window.custompickupDisableFlagTimeout = null;
        }
        
        // Désactiver tous les flags
        window.custompickupJustSelected = false;
        window.custompickupJustSelectedStoreId = null;
        window.custompickupJustSelectedCarrierId = null;
        window.custompickupSelectionTimestamp = null;
        
        // Restaurer $.post si intercepté
        if (window.custompickupOriginalPost) {
            $.post = window.custompickupOriginalPost;
            delete window.custompickupOriginalPost;
        }
        
        // Réactiver les listeners du module ckc_stores_stock
        const deliveryInputs = document.querySelectorAll('.delivery-option input');
        deliveryInputs.forEach(input => {
            input.removeAttribute('data-custompickup-blocking');
        });
        
        // Nettoyer uniquement les flags de sauvegarde du transporteur
        // GARDER custompickup_last_selected_store pour pouvoir restaurer le magasin si on revient au retrait
        localStorage.removeItem('selected_delivery_option');
        localStorage.removeItem('is_pickup_carrier');
        
        // Supprimer le cookie id_store
        fetch(window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '') + '/modules/custompickup/custompickup.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams({
                ajax: 1,
                action: 'getCartId'
            })
        })
        .then(response => response.json())
        .then(data => {
            const cartId = data.cart_id;
            if (cartId) {
                // Supprimer le cookie
                document.cookie = `custompickup_id_store_${cartId}=; path=/; max-age=0; expires=Thu, 01 Jan 1970 00:00:00 GMT`;
                
                // Réinitialiser l'id_store côté serveur
                return fetch(window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '') + '/modules/custompickup/custompickup.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams({
                        ajax: 1,
                        action: 'setCustomPickupCarrier',
                        id_carrier: carrierId,
                        id_store: '',
                        id_cart: cartId
                    })
                });
            }
        })
        .then(() => {
            // Attendre que le serveur ait bien pris en compte la réinitialisation
            // puis appeler le module ckc_stores_stock
            setTimeout(() => {
                const selectedRadio = document.querySelector('input[name^="delivery_option"]:checked');
                if (selectedRadio) {
                    const selectedDelivery = selectedRadio.id;
                    
                    // Appel direct à l'API du module ckc_stores_stock
                    $.post('/modules/ckc_stores_stock/ajax_checkout.php', {
                        selectedDelivery: selectedDelivery
                    }, function (resp) {
                        if (resp.data && resp.data.stock) {
                            // Mettre à jour le nom du magasin
                            if (resp.data.stock.store_name) {
                                $('#stock-summary-content .nameStore').html(resp.data.stock.store_name);
                            }
                            
                            // Mettre à jour l'affichage
                            if (resp.data.stock.isClickAndCollect) {
                                $('#stock-summary-content .card-header').html('Click And Collect');
                            } else {
                                $('#stock-summary-content .card-header').html('Livraison');
                                $('#stock-summary-content .productLimit').html('Notre magasin vous livre à domicile ou sur votre chantier !');
                                $('#stock-summary-content .text').html('<p>La livraison se fait sur rendez-vous uniquement. Nous allons vous contacter suite à votre commande pour déterminer cette date.</p><p>Le délais moyen pour vos produits est entre 10 à 15 jours ouvrés (sous réserve de stock disponible).</p>');
                            }
                            
                            // Mettre à jour les produits
                            $('#stock-summary-content .products').empty();
                            if (resp.data.stocks) {
                                for (let i = 0; i < resp.data.stocks.length; i++) {
                                    const image = resp.data.stocks[i].stock_ok 
                                        ? '<img src="/modules/ckc_stores_stock/assets/img/selectionner.webp" class="unveil-loaded">'
                                        : '<img src="/modules/ckc_stores_stock/assets/img/moins.webp" class="unveil-loaded">';
                                    $('#stock-summary-content .products').append('<p class="productQuantity" style="color:#000000;">' + image + resp.data.stocks[i].product_name + '<br></p>');
                                }
                            }
                        }
                    }, "json");
                }
            }, 300);
        })
        .catch(() => {
            // En cas d'erreur, appeler quand même le module
            setTimeout(() => {
                const selectedRadio = document.querySelector('input[name^="delivery_option"]:checked');
                if (selectedRadio) {
                    const selectedDelivery = selectedRadio.id;
                    $.post('/modules/ckc_stores_stock/ajax_checkout.php', {
                        selectedDelivery: selectedDelivery
                    }, null, "json");
                }
            }, 300);
        });
    }
}

// Mise à jour du cart-stock.tpl pour les livraisons classiques
function updateCartStockForDelivery(carrierId) {
    if (window.location.pathname.includes('order') || window.location.pathname.includes('commande') || document.getElementById('stock-summary-content')) {
        const selectedDelivery = 'delivery_option_' + carrierId;

        // Faire la même requête AJAX que custom.js
        fetch(window.location.origin + '/commande', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                ajax: "1",
                action: "stock",
                selectedDelivery: selectedDelivery,
                debug: "1"
            })
        })
            .then(response => response.json())
            .then(function (resp) {
                if (resp.data && resp.data.stock) {
                    // Mettre à jour l'affichage directement comme custom.js
                    if (resp.data.stock.isClickAndCollect) {
                        $('#stock-summary-content .card-header').html('Click And Collect');
                        if (resp.data.stock.stock_ok) {
                            $('#stock-summary-content .productLimit').html('Super ! Tous vos produits sont en stock');
                            // Message spécial pour le store Essonne (19)
                            if (resp.data.stock.store_id == 19) {
                                $('#stock-summary-content .text').html('<p>Disponibilité sous 5 jours ouvrés en magasin, selon stock. Le magasin vous contactera dès que votre commande sera prête.</p>');
                            } else {
                                $('#stock-summary-content .text').html('<p>Vous pouvez venir récupérer les produits en stock dans votre magasin 2h après votre commande.</p>');
                            }
                        } else {
                            $('#stock-summary-content .productLimit').html('Un ou des produits sont en cours de réapprovisionnement');
                            // Message spécial pour le store Essonne (19)
                            if (resp.data.stock.store_id == 19) {
                                $('#stock-summary-content .text').html('<p>Disponibilité sous 5 jours ouvrés en magasin, selon stock. Le magasin vous contactera dès que votre commande sera prête.</p>');
                            } else {
                                $('#stock-summary-content .text').html('<p>Vous pouvez venir récupérer les produits en stock dans votre magasin.</p> <p>Pour les produits en cours de réapprovisionnement, nous vous appellerons aussitôt ces derniers disponibles.</p>');
                            }
                        }
                    } else {
                        $('#stock-summary-content .card-header').html('Livraison');
                        $('#stock-summary-content .productLimit').html('Notre magasin vous livre à domicile ou sur votre chantier !');
                        $('#stock-summary-content .text').html('<p>La livraison se fait sur rendez-vous uniquement. Nous allons vous contacter suite à votre commande pour déterminer cette date.</p><p>Le délais moyen pour vos produits est entre 10 à 15 jours ouvrés (sous réserve de stock disponible).</p>');
                    }

                    // Mettre à jour la liste des produits
                    $('#stock-summary-content .products').empty();
                    if (resp.data.stocks) {
                        for (let i = 0; i < resp.data.stocks.length; i++) {
                            if (resp.data.stocks[i].stock_ok) {
                                var image = '<img src="https://www.cloture-discount.fr/themes/auto_store/assets/img/selectionner.png" class="unveil-loaded">';
                            } else {
                                var image = '<img src="https://www.cloture-discount.fr/themes/auto_store/assets/img/moins.svg" class="unveil-loaded">';
                            }
                            $('#stock-summary-content .products').append('<p class="productQuantity" style="color:#000000;">' + image + resp.data.stocks[i].product_name + '<br></p>');
                        }
                    }

                    // Mettre à jour le nom du magasin
                    if (resp.data.stock.store_name) {
                        $('#stock-summary-content .nameStore').html(resp.data.stock.store_name);
                    }
                }
            })
            .catch(function(error) {
            });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Détecter si c'est un refresh de page
    const isPageRefresh = performance.navigation.type === 1 || performance.getEntriesByType('navigation')[0]?.type === 'reload';
    
    initPickupSelectionListeners();
    disablePickupSelectionIfNotMainCarrier();
    initCarrierEvents();

    // Initialiser la mise à jour du cart-stock au chargement
    initCartStockOnPageLoad();

    // Restaurer l'état du transporteur de retrait après un délai
    setTimeout(restorePickupDeliveryState, 1000);
});

// Initialiser la mise à jour du cart-stock au chargement de la page
function initCartStockOnPageLoad() {
    if (!window.location.pathname.includes('order') && !window.location.pathname.includes('commande') && !document.getElementById('stock-summary-content')) {
        return;
    }

    // Récupérer le transporteur actuellement sélectionné
    const selectedCarrierRadio = document.querySelector('input[type="radio"][name^="delivery_option"]:checked');
    if (selectedCarrierRadio) {
        const carrierValue = selectedCarrierRadio.value;
        const carrierId = carrierValue.split(',')[0];
        const isPickupCarrier = carrierId == MAIN_PICKUP_CARRIER_ID ||
            selectedCarrierRadio.closest('label')?.textContent?.includes('Retrait');

        // ✅ NOUVEAU : Persister l'état du transporteur de retrait
        if (isPickupCarrier) {
            localStorage.setItem('selected_delivery_option', selectedCarrierRadio.id);
            localStorage.setItem('is_pickup_carrier', 'true');
        }

        if (isPickupCarrier) {
            updateCartStockOnCarrierChange(selectedCarrierRadio);
        }
    }
}

// ✅ NOUVEAU : Fonction pour restaurer l'état du transporteur de retrait
// Simplifiée pour s'inspirer de pickup.js qui fonctionne bien
function restorePickupDeliveryState() {
    if (!document.getElementById('stock-summary-content')) {
        return;
    }

    const savedDelivery = localStorage.getItem('selected_delivery_option');
    const isPickupCarrier = localStorage.getItem('is_pickup_carrier') === 'true';

    if (!savedDelivery || !isPickupCarrier) {
        return;
    }

    // Vérifier si le transporteur sauvegardé est toujours sélectionné
    const currentDelivery = document.querySelector('input[name*="delivery_option"]:checked')?.id;
    if (currentDelivery !== savedDelivery) {
        return;
    }

    // Récupérer le storeId depuis la mémoire (avec backup cookie)
    let rememberedStoreId = localStorage.getItem('custompickup_last_selected_store');
    
    // Si le localStorage a été nettoyé, essayer de récupérer depuis le cookie
    if (!rememberedStoreId) {
        rememberedStoreId = getStoreIdFromCookie();
        // Si trouvé dans le cookie, restaurer dans localStorage
        if (rememberedStoreId) {
            localStorage.setItem('custompickup_last_selected_store', rememberedStoreId);
        }
    }
    
    if (rememberedStoreId) {
        // Extraire l'ID du transporteur depuis la valeur du radio
        const selectedCarrierRadio = document.querySelector('input[name*="delivery_option"]:checked');
        if (selectedCarrierRadio) {
            const carrierValue = selectedCarrierRadio.value;
            const carrierId = carrierValue.split(',')[0];

            // Restaurer visuellement la sélection du magasin
            const radioToRestore = document.querySelector(`input[name="selected_pickup"][value="${rememberedStoreId}"]`);
            if (radioToRestore) {
                radioToRestore.checked = true;
                // Déclencher l'événement change pour mettre à jour l'affichage (comme dans pickup.js)
                radioToRestore.dispatchEvent(new Event('change'));
            }

            // Mettre à jour l'affichage du stock
            updateCartStockForPickup(carrierId, rememberedStoreId);
        }
    }
}

if (typeof prestashop !== 'undefined') {
    prestashop.on('updatedDeliveryForm', function () {
        initPickupSelectionListeners();
        disablePickupSelectionIfNotMainCarrier();
        initCarrierEvents();

        // ✅ MODIFICATION : Restaurer l'état du transporteur de retrait après mise à jour
        setTimeout(() => {
            initCartStockOnPageLoad();
            restorePickupDeliveryState();
        }, 500);
    });
}

function renderOpeningHours() {
    document.querySelectorAll('.opening-hours').forEach(function (container) {
        const rawData = container.getAttribute('data-hours');
        let hours;
        try {
            hours = JSON.parse(rawData);
        } catch (e) {
            container.querySelector('.hours-list').innerHTML = '<li>Erreur de lecture des horaires</li>';
            return;
        }

        const days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        const ul = container.querySelector('.hours-list');
        ul.innerHTML = '';

        days.forEach((day, index) => {
            let value = hours[index];
            let display = Array.isArray(value) && value.length > 0 ? value.join(', ') : (typeof value === 'string' ? value : 'Fermé');

            const li = document.createElement('li');
            const dayEl = document.createElement('span');
            const timeEl = document.createElement('span');
            dayEl.textContent = day;
            timeEl.textContent = display;

            const today = new Date().getDay();
            const jsDay = (index === 6) ? 0 : index + 1;
            if (jsDay === today) {
                dayEl.classList.add('today');
                timeEl.classList.add('today');
            }

            li.appendChild(dayEl);
            li.appendChild(timeEl);
            ul.appendChild(li);
        });
    });
}