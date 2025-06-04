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
        const productDescriptions = $('.product_descr');

        if (isListView) {
            setCookie("show_list", "true", 365);

            productList.each(function () {
                // Supprime toutes les classes commençant par col-
                const el = $(this);
                el.removeClass(function (index, className) {
                    return (className.match(/col-\S+/g) || []).join(' ');
                });
                el.addClass('list_view col-12');
            });

            productDescriptions.show();
            showListBtn.addClass('active');
            showGridBtn.removeClass('active');
            productsContainer
                .removeClass('grid-mode')
                .addClass('list-mode list_view');

        } else {
            setCookie("show_list", "", -1);

            productList.each(function () {
                const el = $(this);
                el.removeClass(function (index, className) {
                    return (className.match(/col-\S+/g) || []).join(' ');
                });
                el.removeClass('list_view')
                    .addClass('col-xs-6 col-sm-6 col-md-4 col-lg-3 col-xl-3');
            });

            productDescriptions.hide();
            showGridBtn.addClass('active');
            showListBtn.removeClass('active');
            productsContainer
                .removeClass('list-mode list_view')
                .addClass('grid-mode');
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


    var content = $('#description-content');
    var button = $('#toggle-btn');

    if (content.length && content[0].scrollHeight <= 100) {
        button.hide(); // Cache le bouton si le texte est déjà court
    }

    button.on('click', function() {
        content.toggleClass('expanded');
        button.text(content.hasClass('expanded') ? "Voir moins" : "En savoir plus");
    });

    const redirects = {
    };

    function redirectTo(url) {
        window.location.href = window.location.origin + url;
    }

    function checkAndRedirect(url) {
        if (redirects[url]) {
            redirectTo(redirects[url]);
        }
    }

    // Observe URL changes
    let lastUrl = location.pathname + location.search;
    const observer = new MutationObserver(() => {
        const currentUrl = location.pathname + location.search;
        if (currentUrl !== lastUrl) {
            lastUrl = currentUrl;
            checkAndRedirect(currentUrl);
        }
    });

    observer.observe(document, {subtree: true, childList: true});

    checkAndRedirect(lastUrl);

    function redirectToBase64Url(base64Url) {
        const decodedUrl = atob(base64Url);
        window.location.href = decodedUrl;
    }
    $('.elementor-element-0e4f281 .elementor-widget-call-to-action .elementor-cta-content').each(function () {
        // Vérifier si le bouton est déjà présent
        if ($(this).find('.btn-primary-arrow').length === 0) {
            // Ajouter le bouton à la fin de l'élément CTA
            $(this).append(`
                <span class="btn-primary-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-up-right"> 
                        <path d="M13 5H19V11"></path> 
                        <path d="M19 5L5 19"></path>  
                    </svg>
                </span>
            `);
        }
    });
    if ($('body#category').length || $('body#index').length || $('body#product').length) {
        $('.discount_type_flag').each(function () {
            let elementFlagParent = $(this).parent()
            if (elementFlagParent.find(".pastille_fr").length !== 0){
                let top = parseInt($(this).css("top"), 10)
                let topNew = top + 45;
                $(this).css("top", topNew + "px")
            }
        });
    }

    prestashop.on('updateProductList', (event) => {
        if ($('body#category').length) {
            $('.discount_type_flag').each(function () {
                let elementFlagParent = $(this).parent()
                if (elementFlagParent.find(".pastille_fr").length !== 0){
                    let top = parseInt($(this).css("top"), 10)
                    let topNew = top + 45;
                    $(this).css("top", topNew + "px")
                }
            });
        }
    });
});
