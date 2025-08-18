/*Fancybox.bind("[data-fancybox]", {
  // Your custom options
});*/

import Swiper from "swiper/bundle";

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

document.querySelectorAll(".wrapper__swiper__featured-products").forEach((wrapper) => {
    new Swiper(wrapper.querySelector(".swiper__featured-products"), {
        loop: wrapper.closest("#cart") ? false : true, // Désactive loop dans le panier
        spaceBetween: 20,
        slidesPerView: 4,
        breakpoints: {
            640: { slidesPerView: 2, spaceBetween: 24 },
            768: { slidesPerView: 3, spaceBetween: 24 },
            1024: { slidesPerView: 4, spaceBetween: 24 },
        },
        observer: true,
        observeParents: true,
        pagination: {
            el: wrapper.querySelector(".swiper-pagination"),
            clickable: true,
        },
        navigation: {
            nextEl: wrapper.querySelector(".swiper-button-next"),
            prevEl: wrapper.querySelector(".swiper-button-prev"),
        },
    });
});
