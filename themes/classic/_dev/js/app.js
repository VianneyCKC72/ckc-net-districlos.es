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
});
