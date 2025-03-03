$(document).ready(function () {
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            let date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + value + expires + "; path=/";
    }

    function getCookie(name) {
        let match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    function toggleView(isListView) {
        const productList = $('#js-product-list .js-product.product');
        const showListBtn = $('.show_list');
        const showGridBtn = $('.show_grid');
        const productsContainer = $('.products');
        const productDescriptions = $('.product_descr'); // Sélection de la description

        var classgrid = "col-xs-6 col-md-6 col-lg-4 col-sm-6 col-xl-3";
        if (isListView) {
            setCookie("show_list", "true", 365);
            productList.removeClass(classgrid)
                .addClass('list_view col-12');
            productDescriptions.show(); // Affiche la description des produits en mode liste
            showListBtn.addClass('active');
            showGridBtn.removeClass('active');
            productsContainer.removeClass('grid-mode').addClass('list-mode');
        } else {
            setCookie("show_list", "", -1);
            productList.removeClass('list_view col-12')
                .addClass(classgrid);
            productDescriptions.hide(); // Cache la description des produits en mode grille
            showGridBtn.addClass('active');
            showListBtn.removeClass('active');
            productsContainer.removeClass('list-mode').addClass('grid-mode');
        }
    }


    // Gestion des clics
    $('.show_list').on('click', function () {
        toggleView(true);
    });

    $('.show_grid').on('click', function () {
        toggleView(false);
    });

    // Appliquer l'affichage au chargement de la page en fonction du cookie
    if (getCookie("show_list") === "true") {
        toggleView(true);
    } else {
        toggleView(false);
    }
});
