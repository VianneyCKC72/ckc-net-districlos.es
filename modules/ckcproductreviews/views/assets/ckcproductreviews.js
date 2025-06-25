document.addEventListener('DOMContentLoaded', function () {
    new Swiper('.ckc-review-carousel', {
        loop: false,
        spaceBetween: 20,
        slidesPerView: 1, // par défaut sur mobile
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
        breakpoints: {
            996: {
                slidesPerView: 3, // Desktop : 3 slides visibles
            }
        }
    });
});
