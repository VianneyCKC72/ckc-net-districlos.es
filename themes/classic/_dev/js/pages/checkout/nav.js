/**
 * Checkout — nav, adresses, livraison, stock mobile, promo collapse
 */
export default function initCheckoutNav() {
    $('.exitCart').click(function (e) {
        e.preventDefault()
        $('#confirmModal').modal('show')
    })

    if ($('body').hasClass('page-order')) {

        // Mode de livraison : état visuel de la card sélectionnée
        function syncDeliveryOptionSelection() {
            $('.js-delivery-option').each(function () {
                var checked = $(this).find('input[type="radio"]').is(':checked');
                $(this).toggleClass('-selected', checked);
            });
        }
        $(document).on('change', 'input[name^="delivery_option"]', syncDeliveryOptionSelection);
        syncDeliveryOptionSelection();

        // Supprimer le doublon des boutons sociaux
        $('#checkout-guest-form .ets_solo_social_wrapper:not(:first)').remove();

        $(document).on('mousedown', '.account-switch [data-toggle="tab"]', function () {
            $(this).removeClass('active');
        });
        $(document).on('shown.bs.tab', '.account-switch [data-toggle="tab"]', function (e) {
            var target = $(e.target).attr('href');
            $('.account-switch')
                .toggleClass('-login', target === '#checkout-login-form')
                .toggleClass('-register', target === '#checkout-guest-form');
        });

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

        if (typeof default_address_id != 'undefined') {
            $("article.address-item").each(function () {
                var isDefault = $(this).find("input").val() == default_address_id;
                $(this).toggleClass('-selected', isDefault);
                $(this).find("input").prop('checked', isDefault);
            });
        }

        $(document).on('change', '.js-address-selector input[type="radio"]', function () {
            var $selector = $(this).closest('.js-address-selector');
            $selector.find('.address-item').removeClass('-selected');
            $(this).closest('.address-item').addClass('-selected');
        });

    }

    // Gérer le déplacement du bloc stock en mobile sur la page checkout
    if ($('body').hasClass('page-order')) {
        function moveStockBlockForMobile() {
            var $stockBlock = $('#stock-summary-content');
            var $stockMobileContainer = $('.cart-grid-stock-mobile');
            
            if ($stockBlock.length && $stockMobileContainer.length) {
                var $existingClone = $stockMobileContainer.find('#stock-summary-content-mobile');
                
                if ($(window).width() <= 991) {
                    // Créer le clone seulement s'il n'existe pas
                    if ($existingClone.length === 0) {
                        var $stockClone = $stockBlock.clone(true, true);
                        $stockClone.attr('id', 'stock-summary-content-mobile');
                        $stockClone.attr('class', $stockBlock.attr('class'));
                        $stockMobileContainer.append($stockClone);
                    }
                    // Si le clone existe déjà, ne rien faire (optimisation)
                } else {
                    // En desktop, supprimer le clone mobile s'il existe
                    if ($existingClone.length > 0) {
                        $existingClone.remove();
                    }
                }
            }
        }
        
        setTimeout(function() {
            moveStockBlockForMobile();
        }, 100);
        
        var resizeTimeout;
        $(window).on('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                moveStockBlockForMobile();
            }, 150);
        });
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


        // Vérification à la soumission
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
}
