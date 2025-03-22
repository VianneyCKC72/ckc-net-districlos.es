$(document).ready(function () {
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

  // ✅ Correction : Supprime le problème de double appel en bloquant le blur immédiat
  $('body').on('click', '.increase', function () {
    let parent = $(this).closest('.custom-quantity-selector');
    let $quantityInput = $(parent).find('input');

    let currentValue = parseInt($quantityInput.val()) || 0;
    let newValue = currentValue + 1;
    let cart = $('body').hasClass('cart');
    $quantityInput.val(newValue).trigger('change').attr('data-valueqty', newValue);
    checkStock(cart, $quantityInput, 'up', 1);
  });

  $('body').on('click', '.decrease', function () {
    let parent = $(this).closest('.custom-quantity-selector');
    let $quantityInput = $(parent).find('input');

    let currentValue = parseInt($quantityInput.val()) || 0;
    let minValue = parseInt($quantityInput.attr('min')) || 1;
    let newValue = Math.max(minValue, currentValue - 1);

    $quantityInput.val(newValue).trigger('change').attr('data-valueqty', newValue);
    let cart = $('body').hasClass('cart');

    checkStock(cart, $quantityInput, 'down', 1);
  });

  function getFormData(cart = false, object = null, direction = null, differential = null) {
    if (!cart) {
      let jsProduct = $(object).closest('.js-product-actions');

      return {
        token: $(jsProduct).find('input[name=token]').val(),
        productId: $(jsProduct).find('input[name=id_product]').val(),
        customizationId: $(jsProduct).find('input[name=id_customization]').val(),
        quantity: $(jsProduct).find("#quantity_wanted").val(),
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
    let elements = $('input[name^="group["], select[name^="group["]');

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
      formData = getFormData(cart, object);
      requestURL = buildRequestURL(formData);
    } else {
      formDataCart = getFormData(cart, object, direction, differential);
      requestURL = $(object).attr('data-update-url');
    }

    $.ajax({
      url: requestURL,
      type: "POST",
      dataType: "json",
      data: formDataCart,
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
      }
    });
  }

  $('body').on('blur', '.js-cart-line-product-quantity', function (event, direction) {
    let input = $(this);
    let qtyBefore = parseInt(input.attr('data-valueqty')) || 1;
    let qty = parseInt(input.val()) || 1;

    // Bloque la requête AJAX si la valeur n'a pas changé
    if (qty === qtyBefore) {
      return false;
    }

    let differential = Math.abs(qty - qtyBefore);
    direction = qty > qtyBefore ? 'up' : 'down';

    input.attr('data-valueqty', qty);
    checkStock(true, input, direction, differential);
  });

  function updateViewProductCart(data) {
    $.each(data, function (key, value) {
      let selectorMap = {
        'cart_detailed': '.js-cart',
        'cart_detailed_totals': '.js-cart-detailed-totals',
        'cart_summary_products': '.js-cart-summary-products',
        'cart_summary_subtotals_container': '.js-cart-summary-subtotals-container',
        'cart_summary_top': '.js-cart-summary-top',
        'cart_summary_totals': '.cart-summary-totals'
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

  const productPriceElement = document.querySelector(".product-prices");
  const bannerQuickView = document.querySelector(".banner-quick-view");

  if (productPriceElement && bannerQuickView) {
    const options = {
      root: null,
      threshold: 0
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          bannerQuickView.classList.add("visible");
        } else {
          bannerQuickView.classList.remove("visible");
        }
      });
    }, options);

    observer.observe(productPriceElement);
  }
  prestashop.on('updatedProduct', (event) => {
    let dataImg = $(event.product_images_modal).filter('.js-product-images-modal').html()
    let imgSrc = $(dataImg).find('img')
    $('.img-cover.rounded-4').prop('src', imgSrc.attr('src'));
    $('.wrapper__swiper__product-img').replaceWith(event.product_cover_thumbnails);

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
});

