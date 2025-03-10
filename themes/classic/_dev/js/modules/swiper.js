Fancybox.bind("[data-fancybox]", {
    // Your custom options
});

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

document
    .querySelectorAll(".wrapper__swiper__featured-products")
    .forEach((wrapper, index) => {
        new Swiper(
            wrapper.querySelector(".swiper__featured-products"),
            {
                direction: "horizontal",
                loop: true,
                slidesPerView: 4,
                spaceBetween: 8,
                watchSlidesProgress: true,
                navigation: {
                    nextEl: wrapper.querySelector(".swiper-button-next"),
                    prevEl: wrapper.querySelector(".swiper-button-prev"),
                },
                breakpoints: {
                    320: {
                        slidesPerView: 1,
                    },
                    480: {
                        slidesPerView: 2,
                    },
                    768: {
                        slidesPerView: 3,
                    },
                    992: {
                        slidesPerView: 4,
                    },
                },
            }
        );
    });
