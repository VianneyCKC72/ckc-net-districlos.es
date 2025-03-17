$(document).ready(function () {
  var $element = $(".wrapper__swiper__product-img");

  if ($element.length) {
    function updateWidth() {
      var width = $element.width(); // Largeur réelle de l'élément
      $element.css("--product-img-width", width + "px");
    }

    // Observer les changements de taille avec ResizeObserver
    var resizeObserver = new ResizeObserver(updateWidth);
    resizeObserver.observe($element[0]);

    // Initialiser la valeur au chargement
    updateWidth();
  }

  // Délégation d'événements pour les boutons de quantité
  $("body").on("click", ".increase", function () {
    let $quantityInput = $("#quantity_wanted");
    $quantityInput[0].stepUp();
    $quantityInput.trigger("input");
  });
  $("body").on("click", ".decrease", function () {
    let $quantityInput = $("#quantity_wanted");
    if (
      $quantityInput.val() > parseInt($quantityInput.attr("min")) ||
      !$quantityInput.attr("min")
    ) {
      $quantityInput[0].stepDown();
      $quantityInput.trigger("input");
    }
  });

  // Récupère dynamiquement les valeurs des inputs
  function getFormData() {
    return {
      token: $("input[name=token]").val(),
      productId: $("input[name=id_product]").val(),
      customizationId: $("input[name=id_customization]").val(),
      quantity: $("#quantity_wanted").val(),
      groupParams: getGroupParams(),
    };
  }

  // si déclinaison on a des groups sur les produits
  function getGroupParams() {
    let groupParams = {};
    $('input[name^="group["], select[name^="group["]').each(function (index) {
      groupParams[`group[${index + 1}]`] = $(this).val();
    });
    return groupParams;
  }

  // Construire l'URL de requête avec les valeurs dynamiques
  function buildRequestURL(formData) {
    let domain = window.location.origin;
    // console.log(domain);debugger
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
      ...formData.groupParams, // Ajout des déclinaisons dynamiques
    };
    $.each(params, function (key, value) {
      url.searchParams.append(key, value);
    });
    return url.toString();
  }

  // Vérification du stock (AJAX)
  function checkStock() {
    let formData = getFormData();
    let requestURL = buildRequestURL(formData);
    $.ajax({
      url: requestURL,
      type: "POST",
      dataType: "json",
      cache: false,
      headers: {
        "Content-Type": "application/json",
        "Cache-Control": "no-store, no-cache, must-revalidate",
      },
      success: function (data) {
        console.log("Réponse du serveur :", data);
        if (data) updateViewProduct(data);
      },
      error: function (error) {
        console.error("Erreur lors de la requête :", error);
      },
    });
  }

  // Mise à jour des éléments ajax
  function updateViewProduct(data) {
    $.each(data, function (key, value) {
      let selectorMap = {
        product_add_to_cart: ".js-product-add-to-cart",
        product_prices: ".js-product-prices",
      };
      if (key in selectorMap) {
        $(selectorMap[key]).replaceWith(value);
      }
    });
  }

  // Écoute de l'événement input sur le champ de quantité
  $("body").on("input", "#quantity_wanted", function () {
    checkStock();
  });

  // Observer for product price visibility
  const productPriceElement = document.querySelector(".product-prices");
  const bannerQuickView = document.querySelector(".banner-quick-view");

  if (productPriceElement && bannerQuickView) {
    const options = {
      root: null, // use viewport
      threshold: 0, // trigger when any part becomes invisible
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          // Price is not visible, show the banner
          bannerQuickView.classList.add("visible");
        } else {
          // Price is visible, hide the banner
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
