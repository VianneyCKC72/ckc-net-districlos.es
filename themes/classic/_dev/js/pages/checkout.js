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

    }
    if ($('body').hasClass('page-order') || $('body').hasClass('page-cart')) {

        var $cartSummary = $('.cart-summary');

        if ($cartSummary.length === 0) return;

        var lastScrollTop = $(window).scrollTop();

        function isDesktop() {
            return $(window).width() >= 992;
        }

        function getOriginalOffsetTop() {
            // On retire temporairement la classe sticky pour avoir la vraie position
            $cartSummary.removeClass('sticky-summary');
            var top = $cartSummary.offset().top;
            return top;
        }

        $(window).on('scroll', function () {
            if (!isDesktop()) {
                $cartSummary.removeClass('sticky-summary');
                return;
            }

            var currentScroll = $(this).scrollTop();
            var offsetTop = getOriginalOffsetTop();

            if (currentScroll > offsetTop && currentScroll > lastScrollTop) {
                $cartSummary.addClass('sticky-summary');
            } else if (currentScroll < lastScrollTop && currentScroll < offsetTop - 100) {
                $cartSummary.removeClass('sticky-summary');
            }

            lastScrollTop = currentScroll;
        });

        $(window).on('resize', function () {
            if (!isDesktop()) {
                $cartSummary.removeClass('sticky-summary');
            }
        });

    }
});
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

    }
    if ($('body').hasClass('page-order') || $('body').hasClass('page-cart')) {

        var $cartSummary = $('.cart-summary');

        if ($cartSummary.length === 0) return;

        var lastScrollTop = $(window).scrollTop();

        function isDesktop() {
            return $(window).width() >= 992;
        }

        function getOriginalOffsetTop() {
            // On retire temporairement la classe sticky pour avoir la vraie position
            $cartSummary.removeClass('sticky-summary');
            var top = $cartSummary.offset().top;
            return top;
        }

        $(window).on('scroll', function () {
            if (!isDesktop()) {
                $cartSummary.removeClass('sticky-summary');
                return;
            }

            var currentScroll = $(this).scrollTop();
            var offsetTop = getOriginalOffsetTop();

            if (currentScroll > offsetTop && currentScroll > lastScrollTop) {
                $cartSummary.addClass('sticky-summary');
            } else if (currentScroll < lastScrollTop && currentScroll < offsetTop - 100) {
                $cartSummary.removeClass('sticky-summary');
            }

            lastScrollTop = currentScroll;
        });

        $(window).on('resize', function () {
            if (!isDesktop()) {
                $cartSummary.removeClass('sticky-summary');
            }
        });

    }
});
