$(document).ready(function () {
    if ($('body').hasClass('page-order')) {

        // Supprimer le doublon des boutons sociaux
        $('#checkout-guest-form .ets_solo_social_wrapper:not(:first)').remove();

        // Déplacer "Recevoir les bons plans et promotions par SMS"
        if ($('input[name="sms_optin"]').length && $('input[name="customer_privacy"]').length) {
            $('input[name="sms_optin"]').closest('.form-group.row').insertAfter($('input[name="customer_privacy"]').closest('.form-group'));
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

        // ---- Mise à jour du texte "Connectez-vous" ou "Créez un compte" ----

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

            // Réattacher les événements après mise à jour du DOM
            $('a[data-toggle="tab"]').off("click").on("click", function () {
                setTimeout(updateAccountSwitch, 300);
            });
        }

        // Écoute les changements d'onglet
        $('a[data-toggle="tab"]').on("shown.bs.tab", function () {
            updateAccountSwitch();
        });

        // Mise à jour au chargement de la page
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
        $(".payment-options .payment-option label span").each(function(){
            if ($(this).text() == 'Payer par Carte de crédit') {
                $(this).after('<img style="width: 40px;" src="/themes/classic/assets/img/payment/mastercard.png" alt="Mastercard">')
                $(this).after('<img style="width: 40px;" src="/themes/classic/assets/img/payment/visa.png" alt="Visa">')
                $(this).after('<img style="width: 40px; margin-left: 15px;" src="/themes/classic/assets/img/payment/cb.png" alt="Carte Bleue">')
            }
        })

    }

    if ($('body').hasClass('page-cart')) {

        var $cartSummary = $('.cart-summary');
        if ($cartSummary.length === 0) return;

        var isSticky = false;
        var stickyOffsetTop = null;

        // On mémorise les éléments détachables
        var $moyenPaiement = null;
        var $blockPromo = null;
        var $h3 = null;

        function isDesktop() {
            return $(window).width() >= 992;
        }

        // On récupère et stocke la position initiale du bloc
        function updateStickyOffset() {
            $cartSummary.removeClass('sticky-summary');
            stickyOffsetTop = $cartSummary.offset().top;
        }

        updateStickyOffset(); // init au chargement

        $(window).on('resize', function () {
            if (!isDesktop()) {
                removeSticky();
            } else {
                updateStickyOffset();
            }
        });

        $(window).on('scroll', function () {
            if (!isDesktop()) return;

            var currentScroll = $(this).scrollTop();

            if (currentScroll > stickyOffsetTop + 100 && !isSticky) {
                applySticky();
            } else if (currentScroll <= stickyOffsetTop + 100 && isSticky) {
                removeSticky();
            }
        });

        function applySticky() {
            isSticky = true;
            $cartSummary.addClass('sticky-summary');

            // Détacher les éléments et les stocker
            $moyenPaiement = $cartSummary.find('.moyen_de_paiement').detach();
            $blockPromo = $cartSummary.find('.block-promo').detach();
            $h3 = $cartSummary.find('h3').detach();
        }

        function removeSticky() {
            isSticky = false;
            $cartSummary.removeClass('sticky-summary');

            var $inside = $cartSummary.find('.cart-summary-inside');

            if ($h3) {
                $inside.prepend($h3);
                $h3 = null;
            }
            if ($blockPromo) {
                $inside.append($blockPromo);
                $blockPromo = null;
            }
            if ($moyenPaiement) {
                $inside.append($moyenPaiement);
                $moyenPaiement = null;
            }
        }
    }

    const $chevron = $('.chevron-toggle svg');
    const $collapse = $('#promoCollapse');

    // Clic manuel sur le chevron
    $('.chevron-toggle').on('click', function (e) {
        e.preventDefault();
        $collapse.collapse('toggle');
    });

    // Rotation du chevron
    $collapse.on('show.bs.collapse', function () {
        $chevron.css('transform', 'rotate(180deg)');
    });

    $collapse.on('hide.bs.collapse', function () {
        $chevron.css('transform', 'rotate(0deg)');
    });
});
