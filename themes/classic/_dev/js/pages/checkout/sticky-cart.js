/**
 * Panier — sticky summary + nettoyage AJAX promo
 */
export default function initCheckoutStickyCart() {
    if ($('body').hasClass('page-cart')) {

        var $cartSummary = $('.cart-summary');
        if ($cartSummary.length === 0) return;

        var $noItems = $('.cart-overview .no-items');
        if ($noItems.length > 0 && $noItems.is(':visible')) return;

        var isSticky = false;
        var stickyOffsetTop = null;
        var $stickyPlaceholder = null; // Placeholder pour éviter le saut visuel lors du passage en sticky
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
                return $(this).find('.cart-total').length > 0 && !$(this).hasClass('js-cart-detailed-totals');
            });
            
            // Il doit y avoir exactement 1 js-cart-detailed-totals et 1 avec cart-total (sans js-)
            if ($jsTotals.length > 1) {
                $jsTotals.slice(1).remove();
            }
            if ($totalTTC.length > 1) {
                // Garder seulement le premier bloc avec cart-total
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
            // Supprimer le placeholder si présent lors du recalcul
            if ($stickyPlaceholder && $stickyPlaceholder.length) {
                $stickyPlaceholder.remove();
                $stickyPlaceholder = null;
            }
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
            
            // Créer un placeholder invisible pour maintenir l'espace et éviter le saut visuel
            var originalHeight = $cartSummary.outerHeight();
            var originalWidth = $cartSummary.outerWidth();
            
            // Créer le placeholder avant de rendre sticky pour éviter le freeze
            $stickyPlaceholder = $('<div class="cart-summary-placeholder"></div>')
                .css({
                    'height': originalHeight + 'px',
                    'width': originalWidth + 'px',
                    'visibility': 'hidden',
                    'pointer-events': 'none',
                    'flex-shrink': '0'
                });
            
            // Insérer le placeholder juste après le cart-summary
            $cartSummary.after($stickyPlaceholder);
            
            // Ajouter la classe sticky-summary
            $cartSummary.addClass('sticky-summary');
            
            // Ajouter une classe au body pour masquer moyen_de_paiement via CSS
            $('body').addClass('cart-summary-sticky');

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
            
            // Supprimer le placeholder avant de retirer le sticky
            if ($stickyPlaceholder && $stickyPlaceholder.length) {
                $stickyPlaceholder.remove();
                $stickyPlaceholder = null;
            }
            
            $cartSummary.removeClass('sticky-summary');
            
            // Retirer la classe du body
            $('body').removeClass('cart-summary-sticky');

            var $inside = $cartSummary.find('.cart-summary-inside');
            var $blockCheckout = $inside.find('.block-checkout');

            // Nettoyer les doublons avant de réinsérer
            cleanDuplicateBlockPromo();

            if ($h3 && $h3.length) {
                $inside.prepend($h3);
                $h3 = null;
            }
            // Réinsérer le bloc promo dans block-checkout, AVANT le total TTC, seulement s'il n'existe pas déjà
            if ($blockPromo && $blockPromo.length) {
                var $existingBlockPromo = $blockCheckout.find('.block-promo');
                if ($existingBlockPromo.length === 0) {
                    // Trouver le bloc total TTC (celui avec cart-total mais sans js-cart-detailed-totals)
                    var $totalTTC = $blockCheckout.find('.cart-detailed-totals').filter(function() {
                        return $(this).find('.cart-total').length > 0 && !$(this).hasClass('js-cart-detailed-totals');
                    });
                    if ($totalTTC.length) {
                        // Insérer le bloc promo AVANT le total TTC (ordre correct selon le template)
                        $totalTTC.first().before($blockPromo);
                    } else {
                        // Si pas de total TTC, chercher le dernier cart-detailed-totals (js-cart-detailed-totals)
                        var $lastCartTotals = $blockCheckout.find(".js-cart-detailed-totals").last();
                        if ($lastCartTotals.length) {
                            // Chercher le bloc cart-promos pour insérer après
                            var $cartPromos = $blockCheckout.find('.cart-promos').last();
                            if ($cartPromos.length) {
                                $cartPromos.after($blockPromo);
                            } else {
                                $lastCartTotals.after($blockPromo);
                            }
                        } else {
                            $blockCheckout.append($blockPromo);
                        }
                    }
                } else {
                    // Si un bloc promo existe déjà, supprimer celui qu'on a détaché pour éviter les doublons
                    $blockPromo.remove();
                }
                $blockPromo = null;
            }
            
            // Nettoyer à nouveau après réinsertion pour être sûr
            cleanDuplicateBlockPromo();
            cleanDuplicateCartDetailedTotals();
        }

        // Fonction pour s'assurer que le bloc total TTC existe toujours
        function ensureTotalTTCExists() {
            var $blockCheckout = $cartSummary.find('.block-checkout');
            if (!$blockCheckout.length) return;
            
            var $totalTTC = $blockCheckout.find('.cart-detailed-totals').filter(function() {
                return $(this).find('.cart-total').length > 0 && !$(this).hasClass('js-cart-detailed-totals');
            });
            
            if ($totalTTC.length === 0) {
                // Le bloc total TTC n'existe pas, il faut le restaurer
                // Faire une requête AJAX pour récupérer le cart_summary_totals
                var refreshURL = $('.blockcart').data('refresh-url') || window.location.href;
                if (refreshURL) {
                    $.ajax({
                        url: refreshURL,
                        type: 'POST',
                        data: {
                            ajax: 1,
                            action: 'refresh'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response && response.cart_summary_totals) {
                                // Créer le wrapper et insérer le contenu
                                var $newWrapper = $('<div class="cart-detailed-totals"></div>').html(response.cart_summary_totals);
                                var $blockPromo = $blockCheckout.find('.block-promo').first();
                                if ($blockPromo.length) {
                                    $blockPromo.after($newWrapper);
                                } else {
                                    var $lastCartTotals = $blockCheckout.find('.js-cart-detailed-totals').last();
                                    if ($lastCartTotals.length) {
                                        $lastCartTotals.after($newWrapper);
                                    } else {
                                        $blockCheckout.append($newWrapper);
                                    }
                                }
                            }
                        }
                    });
                } else {
                    // Essayer d'utiliser l'URL de la page actuelle avec le paramètre ajax
                    var currentURL = window.location.href.split('?')[0];
                    $.ajax({
                        url: currentURL,
                        type: 'POST',
                        data: {
                            ajax: 1,
                            action: 'refresh',
                            token: $('input[name="token"]').val() || ''
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response && response.cart_summary_totals) {
                                // Créer le wrapper et insérer le contenu
                                var $newWrapper = $('<div class="cart-detailed-totals"></div>').html(response.cart_summary_totals);
                                var $blockPromo = $blockCheckout.find('.block-promo').first();
                                if ($blockPromo.length) {
                                    $blockPromo.after($newWrapper);
                                } else {
                                    var $lastCartTotals = $blockCheckout.find('.js-cart-detailed-totals').last();
                                    if ($lastCartTotals.length) {
                                        $lastCartTotals.after($newWrapper);
                                    } else {
                                        $blockCheckout.append($newWrapper);
                                    }
                                }
                            }
                        }
                    });
                }
            }
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

        // Fonction centralisée pour nettoyer et réinitialiser après mise à jour AJAX
        function cleanupAfterCartUpdate() {
            if ($('body').hasClass('page-cart')) {
                setTimeout(function() {
                    cleanDuplicateCartDetailedTotals();
                    cleanDuplicateBlockPromo();
                    reinitializePromoForm();
                    ensureTotalTTCExists();
                }, 150);
            }
        }

        // Nettoyer les doublons après les mises à jour AJAX du panier (pour les requêtes qui ne déclenchent pas updatedCart)
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
                    cleanupAfterCartUpdate();
                }
            }
        });

        // Écouter les événements PrestaShop (méthode principale pour les mises à jour du panier)
        if (typeof prestashop !== 'undefined') {
            prestashop.on('updatedCart', function(event) {
                cleanupAfterCartUpdate();
            });
        }
    }
}
