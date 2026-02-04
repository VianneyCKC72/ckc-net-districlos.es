$(document).ready(function () {

    $('.exitCart').click(function (e) {
        e.preventDefault()
        $('#confirmModal').modal('show')
    })

    if ($('body').hasClass('page-order')) {

        // Supprimer le doublon des boutons sociaux
        $('#checkout-guest-form .ets_solo_social_wrapper:not(:first)').remove();

        // Déplacer "Recevoir les bons plans et promotions par SMS" après la checkbox newsletter
        if ($('input[name="sms_optin"]').length && $('input[name="newsletter"]').length) {
            $('input[name="sms_optin"]').closest('.form-group.row')
                .insertAfter($('input[name="newsletter"]').closest('.form-group'));
        }

        // Vérifier et déplacer les boutons Facebook / Google si nécessaire
        if ($('.ets_solo_social_wrapper').length && $('.nav.nav-inline').length) {
            if (!$('.nav.nav-inline').next().hasClass('ets_solo_social_wrapper')) {
                $('.ets_solo_social_wrapper').insertAfter('.nav.nav-inline');
            }
        }

        // Masquer le titre "Civilité"
        if ($('label[for="field-id_gender"]').length) {
            $('label[for="field-id_gender"]').hide();
        }

        // Masquer les textes "Optionnel"
        $('.form-control-comment').each(function () {
            if ($(this).text().trim() === "Optionnel") {
                $(this).hide();
            }
        });

        // Mise à jour du texte "Connectez-vous" ou "Créez un compte"
        function updateAccountSwitch() {
            let activeTab = $(".tab-pane.active").attr("id");
            let switchContainer = $(".account-switch");

            if (activeTab === "checkout-login-form") {
                switchContainer.html(`
                <h2 class="sous-titre">Connexion</h2>
                <p>
                    Pas encore de compte ?
                    <a href="#checkout-guest-form" data-toggle="tab" role="tab" aria-controls="checkout-guest-form">
                        Créez un compte →
                    </a>
                </p>
            `);
            } else {
                switchContainer.html(`
                <h2 class="sous-titre">Créer un compte</h2>
                <p>
                    Vous avez déjà un compte ?
                    <a href="#checkout-login-form" data-toggle="tab" role="tab" aria-controls="checkout-login-form">
                        Connectez-vous →
                    </a>
                </p>
            `);
            }

            $('a[data-toggle="tab"]').off("click").on("click", function () {
                setTimeout(updateAccountSwitch, 300);
            });
        }

        $('a[data-toggle="tab"]').on("shown.bs.tab", function () {
            updateAccountSwitch();
        });

        updateAccountSwitch();

        if (typeof default_address_id != 'undefined') {
            $("article.address-item").each(function () {
                if ($(this).find("input").val() == default_address_id) {
                    $(this).addClass('selected')
                    $(this).find("input").prop('checked', true)
                } else {
                    $(this).removeClass('selected')
                    $(this).find("input").prop('checked', false)
                }
            })
        }

        $(".payment-options .payment-option label span").each(function () {
            if ($(this).text() == 'Payer par Carte de crédit') {
                $(this).after('<img style="width: 40px;" src="/themes/classic/assets/img/payment/mastercard.png" alt="Mastercard">')
                $(this).after('<img style="width: 40px;" src="/themes/classic/assets/img/payment/visa.png" alt="Visa">')
                $(this).after('<img style="width: 40px; margin-left: 15px;" src="/themes/classic/assets/img/payment/cb.png" alt="Carte Bleue">')
            }
        });
    }


    if ($('body').hasClass('page-cart')) {

        var $cartSummary = $('.cart-summary');
        if ($cartSummary.length === 0) return;

        var $noItems = $('.cart-overview .no-items');
        if ($noItems.length > 0 && $noItems.is(':visible')) return;

        var isSticky = false;
        var stickyOffsetTop = null;

        var $moyenPaiement = null;
        var $blockPromo = null;
        var $h3 = null;

        // Fonction pour nettoyer les doublons de bloc promo
        function cleanDuplicateBlockPromo() {
            var $blockCheckout = $cartSummary.find('.block-checkout');
            var $blockPromos = $blockCheckout.find('.block-promo');
            if ($blockPromos.length > 1) {
                // Garder seulement le premier et supprimer les autres
                $blockPromos.slice(1).remove();
            }
        }

        // Fonction pour nettoyer les doublons de cart-detailed-totals
        function cleanDuplicateCartDetailedTotals() {
            var $blockCheckout = $cartSummary.find('.block-checkout');
            
            // Il ne doit y avoir qu'un seul .js-cart-detailed-totals (celui avec les subtotaux)
            var $jsCartDetailedTotals = $blockCheckout.find('.js-cart-detailed-totals');
            if ($jsCartDetailedTotals.length > 1) {
                // Garder seulement le premier et supprimer les autres
                $jsCartDetailedTotals.slice(1).remove();
            }
            
            // S'assurer qu'il n'y a qu'un seul cart-detailed-totals avec js- dans block-checkout
            var $allCartDetailedTotals = $blockCheckout.find('.cart-detailed-totals');
            var $jsTotals = $allCartDetailedTotals.filter('.js-cart-detailed-totals');
            var $totalTTC = $allCartDetailedTotals.filter(function() {
                return $(this).find('.cart-total').length > 0;
            });
            
            // Il doit y avoir exactement 1 js-cart-detailed-totals et 1 avec cart-total
            if ($jsTotals.length > 1) {
                $jsTotals.slice(1).remove();
            }
            if ($totalTTC.length > 1) {
                $totalTTC.slice(1).remove();
            }
        }

        function isDesktop() {
            return $(window).width() >= 992;
        }

        function pageIsTallEnough() {
            var windowHeight = $(window).height();
            var documentHeight = $(document).height();
            return documentHeight > (windowHeight + 200);
        }

        function updateStickyOffset() {
            $cartSummary.removeClass('sticky-summary');
            stickyOffsetTop = $cartSummary.offset().top;
        }

        updateStickyOffset();

        $(window).on('resize', function () {
            if (!isDesktop()) {
                removeSticky();
            } else {
                updateStickyOffset();
            }
        });

        $(window).on('scroll', function () {
            if (!isDesktop() || !pageIsTallEnough()) return;

            var currentScroll = $(this).scrollTop();

            if (currentScroll > stickyOffsetTop + 100 && !isSticky) {
                applySticky();
            } else if (currentScroll <= stickyOffsetTop + 100 && isSticky) {
                removeSticky();
            }
        });

        function applySticky() {
            if (isSticky) return; // Éviter les appels multiples
            isSticky = true;
            $cartSummary.addClass('sticky-summary');

            $moyenPaiement = $cartSummary.find('.moyen_de_paiement').detach();
            // Ne détacher que le bloc promo qui est dans block-checkout, pas celui dans cart-detailed-totals
            var $blockPromoInCheckout = $cartSummary.find('.block-checkout .block-promo').first();
            if ($blockPromoInCheckout.length) {
                $blockPromo = $blockPromoInCheckout.detach();
            }
            $h3 = $cartSummary.find('h3').detach();
        }

        function removeSticky() {
            if (!isSticky) return; // Éviter les appels multiples
            isSticky = false;
            $cartSummary.removeClass('sticky-summary');

            var $inside = $cartSummary.find('.cart-summary-inside');
            var $blockCheckout = $inside.find('.block-checkout');

            // Nettoyer les doublons avant de réinsérer
            cleanDuplicateBlockPromo();

            if ($h3 && $h3.length) {
                $inside.prepend($h3);
                $h3 = null;
            }
            // Réinsérer le bloc promo dans block-checkout, après le total TTC, seulement s'il n'existe pas déjà
            if ($blockPromo && $blockPromo.length) {
                var $existingBlockPromo = $blockCheckout.find('.block-promo');
                if ($existingBlockPromo.length === 0) {
                    var $cartDetailedTotals = $blockCheckout.find(".cart-detailed-totals").last();
                    if ($cartDetailedTotals.length) {
                        $cartDetailedTotals.after($blockPromo);
                    } else {
                        $blockCheckout.append($blockPromo);
                    }
                } else {
                    // Si un bloc promo existe déjà, supprimer celui qu'on a détaché pour éviter les doublons
                    $blockPromo.remove();
                }
                $blockPromo = null;
            }
            if ($moyenPaiement && $moyenPaiement.length) {
                $inside.append($moyenPaiement);
                $moyenPaiement = null;
            }
            
            // Nettoyer à nouveau après réinsertion pour être sûr
            cleanDuplicateBlockPromo();
            cleanDuplicateCartDetailedTotals();
        }

        // Fonction pour réinitialiser le formulaire de code promo après mise à jour AJAX
        // Rendre cette fonction globale pour qu'elle soit accessible depuis d'autres fichiers
        window.reinitializePromoForm = function() {
            var $cartSummary = $('.cart-summary');
            if (!$cartSummary.length) return;
            
            var $blockPromo = $cartSummary.find('.block-checkout .block-promo').first();
            if ($blockPromo.length) {
                var $promoCollapse = $blockPromo.find('#promoCollapse');
                var $promoForm = $blockPromo.find('form[data-link-action="add-voucher"]');
                
                // S'assurer que le formulaire existe
                if ($promoForm.length && $promoCollapse.length) {
                    // Réinitialiser les événements du collapse
                    $promoCollapse.off('show.bs.collapse hide.bs.collapse');
                    var $chevron = $blockPromo.find('.chevron-toggle svg');
                    
                    $promoCollapse.on('show.bs.collapse', function () {
                        if ($chevron.length) {
                            $chevron.css('transform', 'rotate(180deg)');
                        }
                    });
                    
                    $promoCollapse.on('hide.bs.collapse', function () {
                        if ($chevron.length) {
                            $chevron.css('transform', 'rotate(0deg)');
                        }
                    });
                    
                    // Réinitialiser les événements du chevron
                    $blockPromo.find('.chevron-toggle').off('click');
                    $blockPromo.find('.chevron-toggle').on('click', function(e) {
                        e.preventDefault();
                        $promoCollapse.collapse('toggle');
                    });
                    
                    // Réinitialiser les événements du collapse-toggle
                    $blockPromo.find('.collapse-toggle').off('click');
                    $blockPromo.find('.collapse-toggle').on('click', function(e) {
                        e.preventDefault();
                        $promoCollapse.collapse('toggle');
                    });
                }
            }
        };

        // Nettoyer les doublons après les mises à jour AJAX du panier
        $(document).ajaxComplete(function(event, xhr, settings) {
            if ($('body').hasClass('page-cart')) {
                // Vérifier si c'est une requête liée au panier
                var isCartRequest = false;
                if (settings.url && (settings.url.includes('panier') || settings.url.includes('cart') || settings.url.includes('ajax=1'))) {
                    isCartRequest = true;
                }
                if (settings.data && (settings.data.includes('addDiscount') || settings.data.includes('add-voucher') || settings.data.includes('deleteDiscount'))) {
                    isCartRequest = true;
                }
                
                if (isCartRequest) {
                    setTimeout(function() {
                        cleanDuplicateCartDetailedTotals();
                        cleanDuplicateBlockPromo();
                        reinitializePromoForm();
                    }, 150);
                }
            }
        });

        // Intercepter les réponses AJAX pour mettre à jour cart_voucher
        $(document).ajaxSuccess(function(event, xhr, settings) {
            if ($('body').hasClass('page-cart')) {
                try {
                    var response = typeof xhr.responseJSON !== 'undefined' ? xhr.responseJSON : JSON.parse(xhr.responseText);
                    if (response && response.cart_voucher) {
                        // Le cart_voucher a été mis à jour, réinitialiser le formulaire après
                        setTimeout(function() {
                            reinitializePromoForm();
                        }, 100);
                    }
                } catch(e) {
                    // Pas une réponse JSON, ignorer
                }
            }
        });

        // Écouter aussi les événements PrestaShop
        if (typeof prestashop !== 'undefined') {
            prestashop.on('updatedCart', function(event) {
                if ($('body').hasClass('page-cart')) {
                    setTimeout(function() {
                        cleanDuplicateCartDetailedTotals();
                        cleanDuplicateBlockPromo();
                        reinitializePromoForm();
                    }, 200);
                }
            });
        }
    }

    const $chevron = $('.chevron-toggle svg');
    const $collapse = $('#promoCollapse');
    if ($('body').hasClass('page-cart') || $('body').hasClass('page-order')) {
        // Clic manuel sur le chevron

        $('.chevron-toggle').off('click');
        $(document).on('click', '.chevron-toggle', function (e) {
            toggleCollapse(e)
        });

        function toggleCollapse(e) {
            e.preventDefault();
            $('#promoCollapse').collapse('toggle');
        }

        // Rotation du chevron
        $collapse.on('show.bs.collapse', function () {
            $chevron.css('transform', 'rotate(180deg)');
        });

        $collapse.on('hide.bs.collapse', function () {
            $chevron.css('transform', 'rotate(0deg)');
        });


        // 1. Ajouter un * aux labels des champs requis
        $('input[required], select[required], textarea[required]').each(function () {
            const label = $("label[for='" + $(this).attr("id") + "']");
            if (label.length && !label.html().includes("*")) {
                label.append(' <span style="color:red">*</span>');
            }
        });

        // 2. Ajouter la mention "* champ requis" en bas du formulaire (si pas déjà présent)
        if ($('#delivery-address .required-note').length === 0) {
            $('#delivery-address .form-footer').before('<div class="required-note" style="margin-top: 1em; color: #666;"><span style="color:red">*</span> champ requis</div>');
        }

        // 3. Vérification à la soumission
        $('.js-address-form form, .js-address-form').on('submit', function (e) {
            let valid = true;
            $('input[required], select[required], textarea[required]').each(function () {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid'); // Ajoute une classe pour le style si souhaité
                    valid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!valid) {
                alert("Veuillez remplir tous les champs obligatoires.");
                e.preventDefault();
            }
        });
    }


});
