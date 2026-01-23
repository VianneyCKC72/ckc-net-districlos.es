/* global $, prestashop, dealt_check_availability_url, dealt_product_id, dealt_service_product_id, tva_attestation_url */
$(function () {

    var FORCE_OPEN_FOR_DEBUG = false;
    var RELOAD_GUARD = false;

    // ---------- Utilities
    function normalizeAvailable(val) {
        return val === true || val === 1 || val === '1' || (typeof val === 'string' && val.toLowerCase() === 'true');
    }

    // ---------- Service modal (eligibility)
    function moveServiceModalToBody($m) {
        if (!$m || !$m.length) return;
        if ($m.parent()[0] === document.body) return; // already in body
        $('body').append($m.detach());
    }

    // Move now if present
    var $serviceModal = $('#dealt-service-modal');
    if ($serviceModal.length) {
        moveServiceModalToBody($serviceModal);
    } else {
        // Watch once until found, then disconnect
        var serviceObs = new MutationObserver(function () {
            var $found = $('#dealt-service-modal');
            if ($found.length) {
                moveServiceModalToBody($found);
                serviceObs.disconnect();
            }
        });
        serviceObs.observe(document.documentElement, {childList: true, subtree: true});
    }

    function openServiceModal($product_id, $service_id) {
        var $m = $('#dealt-service-modal');
        $m.attr('main-product-id', $product_id);
        $m.attr('service-product-id', $service_id);
        if (!$m.length) return;
        $m.attr({role: 'dialog', 'aria-modal': 'true'}).fadeIn(200);
    }

    function closeServiceModal() {
        $('#dealt-service-modal').fadeOut(150);
    }

    $(document).on('click', '#dealt-close-modal', function(){
        closeServiceModal()
        $service_product_id = $(this).closest('#dealt-service-modal').attr("service-product-id")
        if($("#product").length){
            $(".dealt-offer-box[data-service-product-id="+$service_product_id+"]").find('.dealt-check-box').css('display', 'flex');
        } else {
            $(".zipcode-check-btn[data-service-product-id="+$service_product_id+"]").closest(".dealt-check-box").css('display', 'flex');
        }

    });

    $(document).on('click', '#dealt-service-modal', function (e) {
        if (e.target === this) closeServiceModal();
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') closeServiceModal();
    });

    // Gérer la touche Entrée sur l'input code postal
    $(document).on('keypress', '[data-dealt-zipcode-check] [data-role="zipcode-input"]', function (e) {
        if (e.key === 'Enter' || e.which === 13) {
            e.preventDefault();
            var $input = $(this);
            var $block = $input.closest('[data-dealt-zipcode-check]');
            var $btn = $block.find('[data-role="zipcode-check-btn"]');
            if ($btn.length) {
                $btn.click();
            }
        }
    });

    // ZIP check → open modal on success
    $(document).on('click', '[data-dealt-zipcode-check] [data-role="zipcode-check-btn"]', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var $block = $btn.closest('[data-dealt-zipcode-check]');
        var $input = $block.find('input[data-role="zipcode-input"]');
        var $res = $block.find('[data-role="zipcode-result"]');
        var $retry = $block.find('[data-role="retry-block"]');
        var $box = $block.find('[data-role="check-box"]');

        if($("#product").length){
            var $parent = $block.closest('.dealt-offer-box');
            var offerId = $parent.data('offer-id');
            var storeId = $parent.data('store-id');
            window.dealt_check_availability_url = $parent.data('eligibility-url');
            var $product_id = $parent.data('product-id');
            var $service_id = $parent.data('service-product-id');
        } else {
            var offerId = $btn.data('offer-id');
            var storeId = $btn.data('store-id');
            window.dealt_check_availability_url = $btn.data('eligibility-url');
            var $product_id = $btn.data('main-product-id');
            var $service_id = $btn.data('service-product-id');
        }


        if (FORCE_OPEN_FOR_DEBUG) {
            openServiceModal();
            return;
        }

        var zipcode = ($input.val() || '').trim();

        /*console.log(window.dealt_check_availability_url)*/

        if (!zipcode) {
            $res.css('color', 'red').text('Veuillez saisir un code postal.').show();
            $input.focus();
            return;
        }
        if (!offerId || !storeId) {
            $res.css('color', 'red').text('Informations manquantes.').show();
            return;
        }
        if (!window.dealt_check_availability_url) {
            $res.css('color', 'red').text('Configuration manquante.').show();
            return;
        }

        $btn.addClass('is-loading').attr('aria-busy', 'true').css('pointer-events', 'none');
        $res.css('color', '#333').text('Vérification en cours...').show();

        $.ajax({
            url: dealt_check_availability_url,
            method: 'GET',
            data: {zipcode: zipcode, offer_id: offerId, store_id: storeId},
            success: function (resp) {
                try {
                    if (typeof resp === 'string') resp = JSON.parse(resp);
                } catch (e) {
                }
                if (resp && normalizeAvailable(resp.available)) {
                    $res.hide();
                    $retry.show();
                    if ($box.length) $box.hide();
                    openServiceModal($product_id, $service_id);
                    $(".dealt-conditions-list").empty()
                    $(".dealt-conditions-list").append(resp.dealt_expert_requirements_description)

                } else {
                    $res.css('color', 'red').text('Service indisponible à cette adresse.').show();
                    $retry.hide();
                }
            },
            error: function () {
                $res.css('color', 'red').text('Erreur lors de la vérification.').show();
                $retry.hide();
            },
            complete: function () {
                $btn.removeClass('is-loading').removeAttr('aria-busy').css('pointer-events', '');
            }
        });
    });

    // Retry ZIP
    $(document).on('click', '[data-dealt-zipcode-check] [data-role="try-again"]', function (e) {
        e.preventDefault();
        var $block = $(this).closest('[data-dealt-zipcode-check]');
        $block.find('[data-role="retry-block"]').hide();
        $block.find('[data-role="check-box"]').show();
        $block.find('[data-role="zipcode-result"]').hide().text('');
        $block.find('input[data-role="zipcode-input"]').val('').focus();
    });

    // Add product + service to cart
    $(document).on('click', '#dealt-add-to-cart', function (e) {
        e.preventDefault();

        var dealt_product_id = $(this).parent().parent().attr('main-product-id')
        var dealt_service_product_id = $(this).parent().parent().attr('service-product-id')

        var productId = (typeof dealt_product_id !== 'undefined' ? dealt_product_id : null);
        var id_product_attribute = (typeof $("#dealt_ajaxurl").data("id-product-attribute") !== 'undefined' ? $("#dealt_ajaxurl").data("id-product-attribute") : localStorage.getItem("dealt_id_product_attribute"))
        var serviceId = (typeof dealt_service_product_id !== 'undefined' ? dealt_service_product_id : null);
        if (!productId || !serviceId) {
            alert('Produit ou service introuvable.');
            return;
        }

        localStorage.setItem("dealt_product_id", productId);
        localStorage.setItem("dealt_service_product_id", serviceId);
        localStorage.setItem("dealt_id_product_attribute", id_product_attribute);

        if($("#product").length){
            var qty = $("#quantity_wanted").val()
            var packQtyML = $("#quantity_ml").val()
            var isCart = false;
        } else {
            var qty = $("#quantity_wanted[data-product-id="+productId+"]").val()
            var isCart = true;
        }

        function addProductService(){
            qty = qty || 1;
            packQtyML = packQtyML || 1

            var ap5_quantityWanted = parseInt($('input[name=qty]').val());
            if (isNaN(ap5_quantityWanted) || ap5_quantityWanted <= 0) {
                ap5_quantityWanted = 1;
            }

            var idProductAttributeList = $('#idCombination').val();

            var productPackQuantityList = [];
            var productPackChoice = [];
            let attributeChanged = null;

            $('.ap5-attributes').each(function (index, element) {
                id_product_pack = $(this).attr('data-id-product-pack');
                productChoice = { idProductPack: id_product_pack, attributesList: []};
                $('select, input[type=hidden], input[type=radio]:checked', $(element)).each(function(){
                    productChoice.attributesList.push(parseInt($(this).val()));
                    attributeChanged = parseInt($(this).val());
                });
                productPackChoice.push(productChoice);
            });

            var productPackQuantityList = [];
            $('.ap5-quantity-wanted').each(function (index, element) {
                let id_product_pack = $(this).attr('data-id-product-pack');
                let productPackQuantity = { idProductPack: id_product_pack, quantity: $(this).val() };
                productPackQuantityList.push(productPackQuantity);
            });
            var productPackCustomizationList = [];
            $('.ap5-customization-form').each(function () {
                let id_product_pack = $(this).attr('data-id-product-pack');
                $('.ap5-customization-block-input', $(this)).each(function (index, element) {
                    let id_customization_field = $(this).attr('data-id-customization-field');
                    let productPackCustomization = {
                        idProductPack: id_product_pack,
                        idCustomizationField: id_customization_field,
                        value: $(this).val()
                    };
                    productPackCustomizationList.push(productPackCustomization);
                });
            });

            var ajaxUrl = $("#dealt_ajaxurl").text()
            $.ajax({
                url: ajaxUrl,
                type: "post",
                data: {
                    productId: productId,
                    id_product_attribute : id_product_attribute,
                    serviceId : serviceId,
                    qty : qty,
                    productPackQuantityList: productPackQuantityList,
                    productPackCustomizationList: productPackCustomizationList,
                    id_product_attribute_list: idProductAttributeList,
                    productPackChoice: productPackChoice,
                    packQtyML : packQtyML,
                    packQty : ap5_quantityWanted,
                    addToCart: true,
                    isCart : isCart,
                    ajax: true,
                },
                dataType: 'json',
                success: function (response) {
                    if(response == "success" || response.id_product == productId) {
                        closeServiceModal();
                        // Sur la page panier, un reload unique est plus sûr qu’un emit
                        if (!RELOAD_GUARD) {
                            RELOAD_GUARD = true;
                            window.location.href = window.location.origin + '/panier?action=show'
                        }
                    }
                },
                error: function (jqXHR, exception, error) {
                    alert('Erreur lors de l’ajout au panier.');
                    console.log(jqXHR.statusText)
                    console.log(exception)
                    console.log(error)
                }
            });
        }
        function addToCart(id, qty) {
            qty = qty || 1;
            var url = (prestashop && prestashop.urls && prestashop.urls.pages && prestashop.urls.pages.cart)
                ? prestashop.urls.pages.cart
                : (window.location.origin + '/?controller=cart');
            return $.post(url, {ajax: 1, action: 'update', id_product: id, qty: qty, add: 1}, null, 'json');
        }
        addProductService();
        /*        addToCart(productId)
                    .then(function () {
                        return addToCart(serviceId);
                    })
                    .then(function () {
                        closeServiceModal();
                        // Sur la page panier, un reload unique est plus sûr qu’un emit
                        if (!RELOAD_GUARD) {
                            RELOAD_GUARD = true;
                            location.reload();
                        }
                    })
                    .catch(function () {
                        alert('Erreur lors de l’ajout au panier.');
                    });*/
    });


    $(document).on('input change', '.ap5-attributes #quantity_ml', function(){
        let packQuantity = $('#quantity_ml').val()
        packQuantity  = packQuantity || 1
        if(packQuantity > 0){
            updateServiceList();
        }
    })

    // ---------- TVA modal
    function moveTvaModalToBody() {
        var $modal = $('#dealt-tva-modal');
        var $overlay = $('#dealt-tva-modal-overlay');

        var moved = false;

        if ($modal.length && $modal.parent()[0] !== document.body) {
            $('body').append($modal.detach());
            moved = true;
        }
        if ($overlay.length && $overlay.parent()[0] !== document.body) {
            $('body').append($overlay.detach());
            moved = true;
        }
        return moved;
    }

    // Move now if present
    moveTvaModalToBody();

    // Watch until first move, then disconnect (prevents loops)
    var tvaObs = new MutationObserver(function () {
        if (moveTvaModalToBody()) {
            tvaObs.disconnect();
        }
    });
    tvaObs.observe(document.documentElement, {childList: true, subtree: true});

    function openTvaModal() {
        var $m = $('#dealt-tva-modal');
        var $o = $('#dealt-tva-modal-overlay');
        if (!$m.length) return;

        // reset UI
        $('#dealt-tva-consent').prop('checked', false);
        $('#dealt-tva-validate').prop('disabled', true).removeClass('is-loading').removeAttr('aria-busy');
        $('#tva-error-msg').hide();

        $m.attr({role: 'dialog', 'aria-modal': 'true'});
        if ($o.length) $o.fadeIn(200);
        $m.fadeIn(200);
    }

    function closeTvaModal() {
        $('#dealt-tva-modal, #dealt-tva-modal-overlay').fadeOut(150);
    }

    // Open without redirect
    $(document).on('click', '.dealt-tva-open-popup', function (e) {
        e.preventDefault();
        openTvaModal();
    });

    // Close handlers
    $(document).on('click', '#dealt-tva-close', closeTvaModal);
    $(document).on('click', '#dealt-tva-modal', function (e) {
        if (e.target === this) closeTvaModal();
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') closeTvaModal();
    });

    // Toggle submit availability
    $(document).on('change', '#dealt-tva-consent', function () {
        $('#dealt-tva-validate').prop('disabled', !this.checked);
        if (this.checked) $('#tva-error-msg').hide();
    });

// --- Soumission AJAX vers le contrôleur (enregistre en BDD)
    $(document).on('submit', '#dealt-tva-form', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $('#dealt-tva-validate');
        var isChecked = $('#dealt-tva-consent').is(':checked');

        if (typeof window.tva_attestation_url === 'undefined' || !window.tva_attestation_url) {
            alert('URL de validation introuvable.');
            return;
        }

        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).addClass('is-loading').attr('aria-busy', 'true');

        var payload = $form.serialize();
        if (payload.indexOf('submit_tva_attestation=1') === -1) {
            payload += (payload ? '&' : '') + 'submit_tva_attestation=1';
        }
        payload += (payload ? '&' : '') + 'id_main_product='+localStorage.getItem('dealt_product_id');

        $.ajax({
            url: window.tva_attestation_url,
            method: 'POST',
            data: payload,
            dataType: 'json',
        })
            .done(function (res) {
                if (res && res.success) {
                    $('#dealt-tva-modal, #dealt-tva-modal-overlay').fadeOut(150);
                    location.reload();
                } else {
                    var msg = (res && res.error) ? res.error : 'Une erreur est survenue.';
                    alert('[TVA] ' + msg);
                }
            })
            .fail(function (xhr, textStatus, errorThrown) {
                // Essaie d’extraire un JSON d’erreur lisible
                var raw = xhr.responseText || '';
                var msg = 'Erreur serveur (' + (xhr.status || '???') + ').';

                try {
                    var parsed = JSON.parse(raw);
                    if (parsed && parsed.error) {
                        msg = '[TVA] ' + parsed.error + (parsed.code ? ' (code ' + parsed.code + ')' : '');
                    }
                } catch (_) {
                    // pas du JSON → garde le message brut tronqué pour debug rapide
                    var snippet = raw.replace(/<[^>]+>/g, '').trim().slice(0, 300);
                    if (snippet) msg += '\n\n' + snippet;
                }

                console.error('[TVA] AJAX FAIL', {textStatus, errorThrown, status: xhr.status, resp: raw});
                alert(msg);
            })
            .always(function () {
                $btn.prop('disabled', !$('#dealt-tva-consent').is(':checked'))
                    .removeClass('is-loading')
                    .removeAttr('aria-busy');
            });
    });

    function updateServiceList() {

        let parent = $(this).closest('.custom-quantity-selector');
        let quantityInputEl = $(parent).find('input#quantity_wanted');
        let quantityInput = quantityInputEl.val();

        /*    let length = $('#dealt_product_length').text();

            let totalLength = length * quantityInput;*/
        let totalLength = $('#quantity_ml').val()
        let id_product = $("input#product_page_product_id").val()
        let ajaxUrl = $("#dealt_ajaxurl").text()

        $.ajax({
            url: ajaxUrl,
            type: "post",
            data: {
                totalLength: totalLength,
                id_product: id_product,
                changeLengthDealt: true,
                ajax: true,
            },
            dataType: 'json',
            success: function (response) {
                if (response.length > 0) {
                    html = '';
                    $picto = '<div class="picto"><svg xml:space="preserve" style="max-height: 500px" viewBox="76.395312 78.96609 131.544 126.438165" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="Pictos" version="1.1" width="131.544" height="126.438165"> <style type="text/css">.st0{fill: none;stroke: #090106;stroke-width: 4;stroke-linecap: round;stroke-linejoin: round;}</style><g><path class="st0" d="M196,193.1c-17.5,3.1-17-15.3-6.4-12.8c4.9,1.1,3.6,9.3,0,10.7c-1.8,0.7-3.6,1.7-5,3c0,0,0,0,0,0c-1,1-0.9,2.6,0.1,3.6c4.8,4.1,10.4,4.5,14.2,0.5c5-5.3,6.9-13.4-4.8-22.8c-1.3-1-5.7-5-12-10.3c-11.3-9.4-28.4-23.6-41-35.6c-4-3.8-8.6-8.2-10.6-13.8c-0.9-3.1-1.5-6.3-1.7-9.5c-0.2-3.3-0.8-6.6-1.7-9.7c-2.6-7.2-8.9-12.4-16.5-13.6c-3.7-0.5-7.5,0-10.9,1.5c-3.4,1.5-6.4,3.9-8.4,7c-0.1,0.2-0.2,0.5-0.1,0.7c0.1,0.2,0.3,0.4,0.5,0.5l17.4,6.7c1.3,5.2,2.5,10.8,3.4,16.5c-4.4,1.8-8.8,3.6-13.2,5.3c-5.6-3.2-11.4-6-17.5-8.2c-0.2-0.1-0.5-0.1-0.7,0.1c-0.2,0.1-0.4,0.3-0.4,0.6c-0.6,3.9-0.3,7.9,1.1,11.6c1.7,4.3,4.8,7.8,8.7,10.2c3.6,2.3,8.9,2.8,13.5,3.3c1.5,0.1,2.9,0.3,4.2,0.5c7.6,1.1,12.6,4.5,19.1,10.4c16.7,15.1,38.4,39.7,46.9,48.9" /></g></svg></div>'
                    $pin = '<svg xml:space="preserve" style="max-height: 500px" viewBox="95.067189 89.400024 77.676165 107.726031" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="Pictos" version="1.1" width="77.676165" height="107.726031"><style type="text/css">.st0 {fill: none;stroke: #090106;stroke-width: 4;stroke-linecap: round;stroke-linejoin: round;}</style><g id="place"><path class="st0" d="M134,140.4c-6.9,0-12.5-5.5-12.5-12.4c0-6.9,5.5-12.5,12.4-12.5c6.9,0,12.5,5.5,12.5,12.4c0.1,6.8-5.4,12.4-12.2,12.5C134.1,140.4,134.1,140.4,134,140.4z" /></g><path class="st0" d="M128.4,185.7c-9.9-12.2-29.2-38.5-29.2-57.7c0-19.2,15.5-34.8,34.7-34.9s34.8,15.5,34.9,34.7c0.1,25.8-33.8,64-33.8,64c11.5,4.2,14.3-11.5,24.8-2.8" /></svg>'
                    response.forEach(function (service, key) {
                        html += '<div class="dealt-offer-box" '
                        html += 'data-dealt="1" '
                        html += 'data-product-id="' + service.dealt_product_id + '" '
                        html += 'data-service-product-id="' + service.dealt_service_product_id + '" '
                        html += 'data-offer-id="' + service.dealt_offer_id + '" '
                        html += 'data-store-id="' + service.dealt_store_id + '" '
                        html += 'data-eligibility-url="' + service.dealt_check_availability_url + '">'
                        html += $picto
                        html += '<div class="dealt-offer-content">'
                        html += '<div class="dealt-offer-title">Vous souhaitez une pose ? Vérifiez l\'éligibilité</div>'
                        html += '<p class="dealt-nom-produit">' + service.dealt_service_title + '</p>'
                        html += '<div class="dealt-offer-price">'
                        html += '+' + service.dealt_service_price
                        if (service.dealt_service_url.length > 0) {
                            html += '<a href="' + service.dealt_service_url + '" rel="noopener noreferrer nofollow" class="dealt-conditions-link" target="_blank">Détails des conditions du service</a>'
                        }
                        html += '</div>'
                        html += '<div data-dealt-zipcode-check><div data-role="check-box" class="dealt-check-box"><div class="dealt-check-label">'
                        html += $pin
                        html += '<label for="dealt-zipcode-input">Vérifiez la disponibilité du service</label>'
                        html += '</div>'
                        html += '<div class="dealt-zipcode-check"><div class="dealt-zipcode-wrapper"><input id="dealt-zipcode-input" name="dealt_zipcode" type="text" '
                        html += 'placeholder="Code postal" inputmode="numeric" pattern="^\\d{5}$" maxlength="5" aria-label="Code postal (5 chiffres)" data-role="zipcode-input" />'
                        html += '<button class="zipcode-search-icon" type="button" data-role="zipcode-check-btn" aria-label="Rechercher">'
                        html += '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg>'
                        html += '</button>'
                        html += '</div></div>'
                        html += '</div>'
                        html += '<div data-role="retry-block" class="dealt-retry-box" hidden>'
                        html += '<button type="button" id="dealt-add-service-only" class="btn btn-primary">'
                        html += 'Ajouter le service de montage</button>'
                        html += '<div><a href="#" id="dealt-try-again" data-role="try-again">Réessayer avec un autre code postal</a></div>'
                        html += '</div>'
                        html += '</div>'
                        html += '<div data-role="zipcode-result" class="dealt-result-msg" aria-live="polite"></div>'
                        html += '</div>'

                        html += '</div>'
                        html += '</div>'

                    });

                    $('.dealt-offer-box').each(function () {
                        $(this).remove()
                    })
                    $('.product-additional-info.js-product-additional-info').prepend(html)
                }
            }
        });
    }

    if($("#cart").length){

        let availables_services_id = [];
        let all_products_cart_id = [];
        dealt_cart_services.map((product) => availables_services_id.push(product.service_product_id))

        $(".custom-quantity-selector").each(function (){
            if($(this).find("input#quantity_wanted").data('product-id') == localStorage.getItem('dealt_service_product_id')){
                $(this).css('display', 'none');
                $(".dealt-cart-service-box").each(function (){
                    $(this).css('display', 'none');
                });
            }
            all_products_cart_id.push($(this).find("input#quantity_wanted").data('product-id'))
        })

        const intersection = availables_services_id.filter(element => all_products_cart_id.includes(element));

        const localMatch = all_products_cart_id.includes(parseInt(localStorage.getItem('dealt_service_product_id'), 10));

        if(!intersection.length && !localMatch){
            $(".btn-add.dealt-tva-open-popup").css('pointer-events', 'none')
            $(".btn-add.dealt-tva-open-popup").css('background', '#b6c7a8 0% 0% no-repeat padding-box')
            $("#tva-submit-btn").attr('disabled', true)
        } else {
            $(".btn-add.dealt-tva-open-popup").css('pointer-events', 'auto')
            $(".btn-add.dealt-tva-open-popup").css('background', '#7CBA49 0% 0% no-repeat padding-box')
            $("#tva-submit-btn").attr('disabled', false)
        }
    }

});
