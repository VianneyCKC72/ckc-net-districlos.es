Fancybox.bind("[data-fancybox]", {
  // Your custom options
});

import Swiper from "swiper/bundle";

document
  .querySelectorAll(".wrapper__swiper__featured-products")
  .forEach((wrapper, index) => {
    const swiperFeaturedProducts = new Swiper(
      wrapper.querySelector(".swiper__featured-products"),
      {
        loop: true,
        slidesPerView: 1.8,
        spaceBetween: 20,
        breakpoints: {
          640: {
            slidesPerView: 2.4,
            spaceBetween: 24,
          },
          768: {
            slidesPerView: 3,
            spaceBetween: 24,
          },
          1024: {
            slidesPerView: 4,
            spaceBetween: 24,
          },
        },
        pagination: {
          el: wrapper.querySelector(".swiper-pagination"),
          clickable: true,
        },
        navigation: {
          nextEl: wrapper.querySelector(".swiper-button-next"),
          prevEl: wrapper.querySelector(".swiper-button-prev"),
        },
      }
    );
  });

const swiperHeroHome = new Swiper(".swiper__home-banner", {
  loop: true,
  autoplay: true,
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
});

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
        spaceBetween: 8,
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

document.addEventListener("DOMContentLoaded", () => {
  const element = document.querySelector(".wrapper__swiper__product-img");

  if (!element) return;

  const updateWidth = () => {
    const width = element.clientWidth; // Largeur réelle de l'élément
    element.style.setProperty("--product-img-width", `${width}px`);
  };

  // Observer les changements de taille
  const resizeObserver = new ResizeObserver(updateWidth);
  resizeObserver.observe(element);

  // Initialiser la valeur au chargement
  updateWidth();

    const postalCodeInput = document.getElementById("postalCodeInput");
    if (postalCodeInput) {
        postalCodeInput.addEventListener("keyup", function (event) {
            if (event.key === "Enter") {
                search();
            }
        });
    }

    document.getElementById("showDelivery").addEventListener("click", function (e) {
        e.preventDefault();
        $('#modalShowDelivery').modal('show');
    });

    const submitZipCode = document.getElementById("submitZipCode");
    if (submitZipCode) {
        submitZipCode.addEventListener("click", function () {
            search();
        });
    }

    const submitZipCodeProduct = document.getElementById("submitZipCodeProduct");
    if (submitZipCodeProduct) {
        submitZipCodeProduct.addEventListener("click", function () {
            search(true, this.dataset.idProduct, this.dataset.idProductAttribute);
        });
    }
});

async function search(product = false, id_product = null, id_product_attribute = null) {
    let url = product ? prestashop.urls.pages.product : prestashop.urls.pages.cart;
    let quantity = product ? document.getElementById("quantity_wanted").value : null;
    let data = {
        ajax: "1",
        action: "get_deliveries",
        postal_code: document.getElementById("postalCodeInput").value,
        ...(product && { id_product, id_product_attribute, quantity })
    };

    await searchProduct(url, data);
}

async function searchProduct(url, data) {
    try {
        const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });

        const resp = await response.json();
        const deliveriesTable = document.querySelector(".deliveriesTable tbody");
        const deliveryCostError = document.querySelector(".deliveryCostError");

        if (resp.success) {
            deliveriesTable.innerHTML = "";
            resp.deliveries_options.forEach(({ name, price, reduc, delay }) => {
                let formattedPrice = price === "gratuit" ? "Gratuit" : `<span class="text-line-through color-red mr-10">${reduc}</span>${price}`;

                let position = name.indexOf("£");
                if (position !== -1) {
                    let avant = name.substring(0, position);
                    let apres = name.substring(position + 1);
                    name = `${avant} <strong>${apres}</strong>`;
                }

                deliveriesTable.innerHTML += `<tr>
                    <td style="width: 35%" class="text-center">${name}</td>
                    <td style="width: 20%">${formattedPrice}</td>
                    <td style="width: 45%">${delay}</td>
                </tr>`;
            });
            document.querySelector(".deliveriesTable").style.display = "block";
            deliveryCostError.style.display = "none";
        } else {
            document.querySelector(".deliveriesTable").style.display = "none";
            deliveryCostError.style.display = "block";
        }
    } catch (error) {
        console.error("Erreur lors de la récupération des livraisons :", error);
    }
}
