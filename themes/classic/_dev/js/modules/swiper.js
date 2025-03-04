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