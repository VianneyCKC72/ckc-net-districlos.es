document.addEventListener('DOMContentLoaded', function () {
    new Swiper('.ckc-review-carousel', {
        loop: false,
        spaceBetween: 20,
        slidesPerView: 3, // par défaut sur mobile
        breakpoints: {
            0: {
                slidesPerView: 1,
                spaceBetween: 20
            },
            461: {
                slidesPerView: 2,
                spaceBetween: 24
            },
            768: {
                slidesPerView: 3,
                spaceBetween: 24
            },
        },
        observer: true,
        observeParents: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
    });
});
