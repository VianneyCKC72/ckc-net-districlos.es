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
            $headerPlaceholder.show();
        } else {
            $header.removeClass("sticky");
            $headerPlaceholder.hide();
        }
    });




});

