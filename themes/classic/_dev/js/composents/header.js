$(document).ready(function () {
    const $searchWidget = $("#search_widget");
    const $searchIcon = $("#search_icon");
    const $closeSearch = $("#close_search");
    const $overlay = $("#overlay");

    function isMobile() {
        return $(window).width() < 768; // Vérifie si l'écran est < 767px
    }

    // Ouvrir la barre de recherche seulement sur mobile
    $searchIcon.on("click", function () {
        if (isMobile()) {
            $searchWidget.addClass("active");
            $overlay.removeClass("d-none");
            $("body").addClass("no-scroll"); // Désactive le scroll sur mobile
        }
    });

    // Fermer la barre de recherche sur mobile
    $closeSearch.on("click", function () {
        if (isMobile()) {
            $searchWidget.removeClass("active");
            $overlay.addClass("d-none");
            $("body").removeClass("no-scroll");
        }
    });

    // Fermer la recherche en cliquant sur l’overlay (mobile uniquement)
    $overlay.on("click", function () {
        if (isMobile()) {
            $searchWidget.removeClass("active");
            $overlay.addClass("d-none");
            $("body").removeClass("no-scroll");
        }
    });

    // Vérifier lors du redimensionnement si on doit désactiver la recherche mobile
    $(window).on("resize", function () {
        if (!isMobile()) {
            $searchWidget.removeClass("active");
            $overlay.addClass("d-none");
            $("body").removeClass("no-scroll");
        }
    });


    $("#nav_account > div").on("click", function (e) {
        e.preventDefault();
        $(this).toggleClass('active')
        $('.row.links').toggleClass('hide_mobile')
    });



    if (!$("body").is("#cart, .page-order")) {
        var $header = $("#header");
        var $promoBar = $("#ckcpromobarre");
        var stickyOffset = $header.offset().top + ($promoBar.length ? $promoBar.outerHeight() : 0);
        var headerHeight = $header.outerHeight();

        // Crée un espace réservé pour éviter le saut de contenu
        var $headerPlaceholder = $("<div id='header-placeholder'></div>").insertBefore($header);
        $headerPlaceholder.height(headerHeight);

        $(window).on("scroll", function () {
            if ($(window).scrollTop() > stickyOffset) {
                $header.addClass("sticky");
                $promoBar.addClass("sticky");
                $headerPlaceholder.show();
            } else {
                $header.removeClass("sticky");
                $promoBar.removeClass("sticky");
                $headerPlaceholder.hide();
            }
        });
    }

    // START BANDEAU SOLDES
    function updateSoldesMenuTop() {
        if (!$(".bandeau-soldes").length || $(window).width() < 991) {
            document.documentElement.style.removeProperty("--soldes-menu-top");
            $(".mm_menus_ul, .mm_modal_filter").each(function () {
                this.style.removeProperty("top");
            });
            return;
        }

        var headerTop = document.querySelector(".header-top");
        if (!headerTop) {
            return;
        }

        var menuTop = Math.ceil(headerTop.getBoundingClientRect().bottom);
        document.documentElement.style.setProperty("--soldes-menu-top", menuTop + "px");

        $(".mm_menus_ul, .mm_modal_filter").each(function () {
            this.style.setProperty("top", menuTop + "px", "important");
        });
    }

    function scheduleSoldesMenuTopUpdate() {
        window.requestAnimationFrame(updateSoldesMenuTop);
    }

    if ($(".bandeau-soldes").length) {
        updateSoldesMenuTop();

        $(window).on("resize scroll", scheduleSoldesMenuTopUpdate);
        $(document).on("click", ".ybc-menu-toggle", function () {
            scheduleSoldesMenuTopUpdate();
            setTimeout(scheduleSoldesMenuTopUpdate, 100);
        });

        if (window.MutationObserver) {
            var menuTopObserver = new MutationObserver(scheduleSoldesMenuTopUpdate);
            menuTopObserver.observe(document.body, {
                attributes: true,
                attributeFilter: ["class"],
            });

            var $headerEl = $("#header");
            if ($headerEl.length) {
                menuTopObserver.observe($headerEl[0], {
                    attributes: true,
                    attributeFilter: ["class"],
                });
            }

            var $promoBarEl = $("#ckcpromobarre");
            if($promoBarEl.length) {
                menuTopObserver.observe($promoBarEl[0], {
                    attributes: true,
                    attributeFilter: ["class"],
                });
            }

            var $megamenuEl = $(".ets_mm_megamenu.sticky_enabled");
            if ($megamenuEl.length) {
                menuTopObserver.observe($megamenuEl[0], {
                    attributes: true,
                    attributeFilter: ["class"],
                });
            }
        }
    }
    // END BANDEAU SOLDES

    // Contrôle affichage popup après la bannière des cookies
    function watchAxeptio() {
        var host = document.querySelector("#axeptio_overlay .needsclick");
        if (!host || !host.shadowRoot) return false;
        var sync = function () {
            var open = !!host.shadowRoot.querySelector(".axeptio_widget");
            document.body.classList.toggle("axeptio-banner-open", open);
        };
        sync();
        new MutationObserver(sync).observe(host.shadowRoot, { childList: true, subtree: true });
        return true;
    }
    if (!watchAxeptio()) {
        var boot = new MutationObserver(function () {
            if (watchAxeptio()) boot.disconnect();
        });
        boot.observe(document.documentElement, { childList: true, subtree: true });
    }
    new MutationObserver(watchAxeptio).observe(document.body, { childList: true, subtree: true });

    // END Contrôle affichage popup après la bannière des cookies
});

