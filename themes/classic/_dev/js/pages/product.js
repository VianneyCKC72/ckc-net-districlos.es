$(document).ready(function () {

    $(document).ready(function () {
        if ($('body#product').length) {
            $('.product-container.js-product-container').each(function () {
                const $container = $(this);
                const $swiper = $container.find('.swiper.swiper__product-img__cover.swiper-initialized.swiper-horizontal.swiper-watch-progress.swiper-backface-hidden');
                const $discount = $container.find('.row > .col-lg-7 > .discount_type_flag');

                if ($swiper.length && $discount.length) {
                    $discount.appendTo($swiper);
                }
            });
        }
    });

    $(document).on('ajaxComplete', function (event, xhr, settings) {
        if (settings.url.includes('calculator')) {
            let price = $('.product-price.price').html()
            price = price.replace('&nbsp;€', '');
            price = price.replace(',', '.');
            price = price.replace(/\s/g, '');
            let price_integer = price.split('.')[0];
            let price_decimal = price.split('.')[1];
            $('.product-price.price').html('<span class="price-integer">' + price_integer + '</span><span class="price-decimal">,' + price_decimal + '</span></span>');
        }
    });


    var $element = $(".wrapper__swiper__product-img");

    if ($element.length) {
        function updateWidth() {
            var width = $element.width();
            $element.css("--product-img-width", width + "px");
        }

        var resizeObserver = new ResizeObserver(updateWidth);
        resizeObserver.observe($element[0]);

        updateWidth();
    }

    var quantityTimeout;

    $('body').on('click', '.increase', function () {
        let parent = $(this).closest('.custom-quantity-selector');
        let $quantityInput = $(parent).find('input#quantity_wanted');

        let currentValue = parseInt($quantityInput.val()) || 0;
        let newValue = currentValue + 1;

        let cart = $('body').prop('id') === 'cart';
        if (cart) {
            $quantityInput.val(newValue).trigger('change').attr('data-valueqty', newValue);
            checkStock(cart, $quantityInput, 'up', 1);
        } else {
            $quantityInput.val(newValue).trigger('change').attr('data-valueqty', newValue);
        }
    });

    $('body').on('click', '.decrease', function () {
        let parent = $(this).closest('.custom-quantity-selector');
        let $quantityInput = $(parent).find('input#quantity_wanted');

        let currentValue = parseInt($quantityInput.val()) || 0;
        let minValue = parseInt($quantityInput.attr('min')) || 1;
        let newValue = Math.max(minValue, currentValue - 1);

        let cart = $('body').prop('id') === 'cart';

        if (cart) {
            $quantityInput.val(newValue).trigger('change').attr('data-valueqty', newValue);
            checkStock(cart, $quantityInput, 'down', 1);
        } else {
            $quantityInput.val(newValue).trigger('change').attr('data-valueqty', newValue);
        }
    });


    $('body').on('change', '.js-product-quantity', function (event) {
        let input = $(this);
        let qtyBefore = parseInt(input.attr('data-valueqty')) || 1;
        let qty = parseInt(input.val()) || 1;

        // Bloque la requête AJAX si la valeur n'a pas changé
        if (qty === qtyBefore) {
            return false;
        }

        let differential = Math.abs(qty - qtyBefore);
        let direction = qty > qtyBefore ? 'up' : 'down';
        input.attr('data-valueqty', qty);

        checkStock(false, input, direction, differential);
    });

    $('body').on('keyup', '.js-product-quantity', function (event) {
        $('.js-product-quantity').trigger('change');
    });


    function getFormData(cart = false, object = null, direction = null, differential = null) {
        if (!cart) {
            let jsProduct = $(object).closest('.js-product-actions');

            return {
                token: $(jsProduct).find('input[name=token]').val(),
                productId: $(jsProduct).find('input[name=id_product]').val(),
                customizationId: $(jsProduct).find('input[name=id_customization]').val(),
                quantity: object.val(),
                groupParams: getGroupParams(jsProduct)
            };
        } else {
            return {
                ajax: 1,
                qty: differential,
                action: 'update',
                op: direction
            };
        }
    }

    function getGroupParams() {
        let groupParams = {};
        let elements = $('input[name^="group["]:checked, select[name^="group["]');

        if (elements.length > 0) {
            elements.each(function (index) {
                groupParams[`group[${index + 1}]`] = $(this).val();
            });
        }

        return groupParams;
    }

    function buildRequestURL(formData) {
        let domain = window.location.origin;
        let url = new URL(domain + "/index.php");
        let params = {
            controller: "product",
            token: formData.token,
            id_product: formData.productId,
            id_customization: formData.customizationId,
            qty: formData.quantity,
            quickview: 0,
            ajax: 1,
            action: "refresh",
            quantity_wanted: formData.quantity,
            ...formData.groupParams
        };

        $.each(params, function (key, value) {
            if (value !== null && value !== undefined) {
                url.searchParams.append(key, value);
            }
        });

        return url.toString();
    }

    function refreshCart() {
        $.ajax({
            url: window.location.origin + "/panier?ajax=1&action=refresh",
            type: "GET",
            dataType: "json",
            cache: false,
            headers: {
                "Cache-Control": "no-store, no-cache, must-revalidate, post-check=0, pre-check=0",
                "X-Content-Type-Options": "nosniff",
                "X-Robots-Tag": "noindex, nofollow"
            },
            success: function (data) {
                updateViewProductCart(data);
            },
            error: function (error) {
                console.error("Erreur lors de la modif du panier :", error);
            }
        });
    }

    function checkStock(cart = false, object = null, direction = null, differential = null) {
        let formData = null;
        let formDataCart = null;
        let requestURL = '';

        if (!cart) {
            formData = getFormData(cart, object, direction, differential);
            requestURL = buildRequestURL(formData);
        } else {
            formDataCart = getFormData(cart, object, direction, differential);
            requestURL = $(object).attr('data-update-url');
        }

        $.ajax({
            url: requestURL,
            type: "POST",
            dataType: "json",
            data: cart ? formDataCart : formData,
            cache: false,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "Cache-Control": "no-store, no-cache, must-revalidate"
            },
            success: function (data) {
                if (data) {
                    if (cart) {
                        refreshCart();
                    } else {
                        updateViewProduct(data, formData.quantity);
                    }
                }
            },
            error: function (error) {
                console.error("Erreur lors de la requête :", error);
            }
        });
    }

    function updateViewProduct(data) {
        $.each(data, function (key, value) {
            let selectorMap = {
                product_add_to_cart: ".js-product-add-to-cart",
                product_prices: ".js-product-prices"
            };
            if (key in selectorMap && value) {
                $(selectorMap[key]).replaceWith(value);
                if (key === 'product_prices') {
                    refreshPrice();
                }
            }
            $('.js-product-quantity').attr('data-valueqty', $('.js-product-quantity').val());
        });
    }

    function refreshPrice() {
        const $productPriceElement = $('.product-prices');
        const $bannerQuickView = $('.banner-quick-view');
        let hasInitializedBanner = false;

        if ($productPriceElement.length && $bannerQuickView.length) {
            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        $bannerQuickView.addClass('visible');

                        // Exécuter une seule fois quand la bannière apparaît
                        if (!hasInitializedBanner) {
                            initializeBannerQuickView();
                            hasInitializedBanner = true;
                        }
                    } else {
                        $bannerQuickView.removeClass('visible');
                    }
                });
            }, {
                root: null,
                threshold: 0
            });

            observer.observe($productPriceElement.get(0));
        }
    }

    function initializeBannerQuickView() {
        const $row = $('.banner-quick-view-row');

        if ($row.length) {
            $row.hide().fadeIn(400);

            const $titleCol = $row.find('.col-title');
            const $priceCol = $row.find('.col-price');

            // Sélection de la promo (dans colonne 1)
            const $promoBlock = $titleCol.find('.product-discount.has-discount');

            // Cible le bloc prix actuel + barré dans colonne 2
            const $priceContainer = $priceCol.find('.d-flex.align-items-center');

            // Déplace la promo juste AVANT le bloc prix
            if ($promoBlock.length && $priceContainer.length) {
                $priceContainer.before($promoBlock);
            }

            // Hauteurs alignées (facultatif)
            // const maxHeight = Math.max(
            //     $row.find('.col-title').outerHeight(),
            //     $row.find('.col-price').outerHeight(),
            //     $row.find('.col-cart').outerHeight()
            // );
            //
            //
            // $row.find('> div').css('min-height', maxHeight + 'px');
        }
    }


    function updateViewProductCart(data) {
        $.each(data, function (key, value) {
            let selectorMap = {
                'cart_detailed': '.js-cart',
                'cart_detailed_totals': '.js-cart-detailed-totals',
                'cart_summary_products': '.js-cart-summary-products',
                'cart_summary_subtotals_container': '.js-cart-summary-subtotals-container',
                'cart_summary_top': '.js-cart-summary-top',
                'cart_summary_totals': '.cart-summary-totals',
                'cart_promos': '.cart-promos'
            };
            if (key in selectorMap && value) {
                $(selectorMap[key]).replaceWith(value);
            }
        });

        let total = 0;
        $("[data-valueqty]").each(function () {
            total += parseInt($(this).attr("data-valueqty")) || 0;
        });
        $(".cart-products-count").text(total);
    }

    refreshPrice();

    prestashop.on('updatedProduct', (event) => {
        let dataImg = $(event.product_images_modal).filter('.js-product-images-modal').html()
        let imgSrc = $(dataImg).find('img')
        $('.img-cover.rounded-4').prop('src', imgSrc.attr('src'));
        $('.wrapper__swiper__product-img').replaceWith(event.product_cover_thumbnails);

        refreshPrice();

        if (event && event.product_reference) {
            const refEl = document.getElementById('product_reference');
            if (refEl) {
                refEl.textContent = event.product_reference;
            }
        }

        document
            .querySelectorAll(".wrapper__swiper__product-img")
            .forEach((wrapper, index) => {
                const swiperProductImgThumbnails = new Swiper(
                    wrapper.querySelector(
                        ".wrapper__swiper__product-img__thumbnails .swiper__product-img__thumbnails"
                    ),
                    {
                        direction: "vertical",
                        loop: true,
                        slidesPerView: "auto",
                        spaceBetween: 24,
                        watchSlidesProgress: true,
                        navigation: {
                            nextEl: wrapper.querySelector(".swiper-button-next"),
                            prevEl: wrapper.querySelector(".swiper-button-prev"),
                        },
                    }
                );
                const swiperProductImgCover = new Swiper(
                    wrapper.querySelector(".swiper__product-img__cover"),
                    {
                        loop: true,
                        slidesPerView: 1,
                        spaceBetween: 24,
                        watchSlidesProgress: true,
                        thumbs: {
                            swiper: swiperProductImgThumbnails,
                        },
                    }
                );
            });
    });

    prestashop.on('updatedCart', (event) => {
        if (typeof $(".cart-promos") !== "undefined") {
            $(".cart-promos").replaceWith(event.resp.cart_promos)
        }
    });

    // Page produit uniquement

    // Touchspin + et -
    if ($('#product').length) {

        // Remplacement des boutons Touchspin
        $('#product .bootstrap-touchspin-up .touchspin-up').replaceWith(`
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
        class="lucide lucide-chevron-up">
        <path d="m18 15-6-6-6 6"/>
      </svg>
    `);

        $('#product .bootstrap-touchspin-down .touchspin-down').replaceWith(`
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
        class="lucide lucide-chevron-down">
        <path d="m6 9 6 6 6-6"/>
      </svg>
    `);

        // Fonction qui injecte le SVG dans l'élément icon
        function injectChevronIcon(iconElement) {
            // Ici, on garde l'état (open ou non) pour la classe si besoin
            const isOpen = iconElement.classList.contains('open');
            iconElement.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
          class="lucide toggle-accessories">
          <path d="m6 9 6 6 6-6"/>
        </svg>
      `;
            // Optionnel : si tu souhaites conserver la classe "open" sur le SVG, elle doit rester sur l'élément parent (<i>)
        }

        // Cible le <h4> qui contient l'icône
        const target = document.querySelector('#product h4');
        if (target) {
            // Utilise MutationObserver pour détecter les remplacements de l'icône
            const observer = new MutationObserver(() => {
                const icon = target.querySelector('i.toggle-icon, i.material-icons.remove');
                // Si l'icône existe et que son contenu ne contient pas de <svg>
                if (icon && icon.innerHTML.indexOf('<svg') === -1) {
                    icon.classList.remove('material-icons', 'remove');
                    icon.classList.add('toggle-icon');
                    injectChevronIcon(icon);
                }
            });

            observer.observe(target, {
                childList: true,
                subtree: true
            });

            // Injection initiale dès le chargement
            const icon = target.querySelector('i.toggle-icon, i.material-icons.remove');
            if (icon && icon.innerHTML.indexOf('<svg') === -1) {
                icon.classList.remove('material-icons', 'remove');
                icon.classList.add('toggle-icon');
                injectChevronIcon(icon);
            }
        }
    }

    $('a[href="#block-description-long"]').on('click', function (e) {
        e.preventDefault();
        var target = $('#block-description-long');

        if (target.length) {
            var offset = target.offset().top - 100; // scroll 150px plus haut
            $('html, body').animate({
                scrollTop: offset
            }, 200); // durée de l'animation en ms
        }
    });


    // 1. Ajoute le loader à droite du texte (si pas déjà présent)
    $(document).on('mousedown', 'button[data-button-action^="add"]', function () {

        const $button = $(this);

        // Évite de l'ajouter plusieurs fois
        if ($button.find('.inline-loader').length === 0) {
            const $loader = $(`
        <span class="inline-loader" style="display:inline-block; margin-left:10px; vertical-align:middle;">
          <svg width="20" height="20" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
            <circle cx="50" cy="50" r="35" stroke-width="10" stroke="currentColor" stroke-dasharray="164"
                    stroke-linecap="round" fill="none">
              <animateTransform attributeName="transform" type="rotate" dur="1s"
                                repeatCount="indefinite" from="0 50 50" to="360 50 50" />
            </circle>
          </svg>
        </span>
      `);
            $button.append($loader);
        }
    });

    // 2. Cache le loader
    function hideLoader() {
        $('.inline-loader').remove(); // supprime tous les loaders ajoutés
    }

    // 3. Observer zone panier
    let cartElement = document.querySelector('#_desktop_cart')
        || document.querySelector('.blockcart')
        || document.querySelector('.cart-preview')
        || document.querySelector('[data-refresh-url*="cart"]')
        || document.querySelector('[data-block="cart"]');

    if (cartElement) {
        const observerForLoader = new MutationObserver(function () {
            hideLoader();
        });
        observerForLoader.observe(cartElement, { childList: true, subtree: true });
    } else {
        const observerFallback = new MutationObserver(function () {
            hideLoader();
        });
        observerFallback.observe(document.body, { childList: true, subtree: true });
    }

    // 4. Fallback PrestaShop
    if (typeof prestashop !== 'undefined' && typeof prestashop.on === 'function') {
        prestashop.on('updateCart', function () {
            hideLoader();
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    const modalParam = urlParams.get('modal');
    if (modalParam === '1') {
        $('#tmcmsbannerblock').remove()
        $('#ckcpromobarre').remove()
        $('#header').remove()
        $('.promo_banner').remove()
        $('.buttonProduct').remove()
        $('.prix_juste').remove()
        $('#footer').remove()
        $('.desc_container').remove()
        $('.prix_juste_product').remove()
        $('#yourgpt_root').remove()
        $('#ever_fancy_mark').remove()
        $('.banner-quick-view').remove()
        $('.breadcrumb').remove()
        $('.banner__newsletter').remove()
        $('.featured-products').remove()
        $('#multiAccessoriesTab').remove()
        $('body#product').css('overflow-x','hidden')

        $(document).on('ajaxComplete', function (event, xhr, settings) {
            if (settings.url.includes('panier')) {
                window.parent.document.querySelector('.layer_accessories_overlay').style.display = 'none'
                window.parent.document.getElementById('layer_accessory_customization').style.display = 'none'
                var randomid = urlParams.get('randomid')
                window.parent.document
                    .querySelector('.accessory_price[data-randomid="' + randomid + '"]')
                    ?.insertAdjacentHTML('afterbegin', '<span style="color: #79c046;font-weight: normal;font-size: 15px;"><i class="material-icons"></i> Accessoire ajouté au panier</span>');
            }
        });
    }

    // Barre de navigation mobile fixe pour les onglets produit
    const mobileNav = document.getElementById('mobile-product-nav');
    const productTabs = document.querySelector('.product-tabs');

    if (mobileNav && productTabs) {
        // Fonction pour gérer l'affichage de la barre lors du scroll
        let ticking = false;
        function handleScroll() {
            if (!ticking) {
                requestAnimationFrame(function() {
                    const tabsRect = productTabs.getBoundingClientRect();
                    const headerHeight = 200; // Hauteur du header + marge pour apparaître plus tôt

                    // Afficher la barre quand on arrive à la section des onglets
                    if (tabsRect.top <= headerHeight) {
                        mobileNav.classList.add('show');
                    } else {
                        mobileNav.classList.remove('show');
                    }
                    ticking = false;
                });
                ticking = true;
            }
        }

        // Écouter le scroll avec throttling
        window.addEventListener('scroll', handleScroll, { passive: true });

        // Vérifier au chargement
        handleScroll();

        // Synchroniser les onglets mobiles avec les onglets originaux
        const mobileLinks = mobileNav.querySelectorAll('.mobile-nav-link');
        const originalLinks = productTabs.querySelectorAll('.nav-link');

        mobileLinks.forEach(function(mobileLink) {
            mobileLink.addEventListener('click', function(e) {
                e.preventDefault();

                // Retirer la classe active de tous les onglets mobiles
                mobileLinks.forEach(link => link.classList.remove('active'));

                // Ajouter la classe active à l'onglet cliqué
                this.classList.add('active');

                // Déclencher le clic sur l'onglet original correspondant
                const targetId = this.getAttribute('href');
                const originalLink = productTabs.querySelector('a[href="' + targetId + '"]');
                if (originalLink) {
                    originalLink.click();
                }
            });
        });

        // Synchroniser les onglets originaux avec les onglets mobiles
        originalLinks.forEach(function(originalLink) {
            originalLink.addEventListener('click', function() {
                const targetId = this.getAttribute('href');
                const mobileLink = mobileNav.querySelector('a[href="' + targetId + '"]');
                if (mobileLink) {
                    // Retirer la classe active de tous les onglets mobiles
                    mobileLinks.forEach(link => link.classList.remove('active'));
                    // Ajouter la classe active à l'onglet mobile correspondant
                    mobileLink.classList.add('active');
                }
            });
        });
    }

});
