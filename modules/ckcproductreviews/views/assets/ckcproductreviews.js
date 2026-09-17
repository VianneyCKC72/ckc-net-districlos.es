document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ckc-review-carousel').forEach(function (el) {
        var swiper = new Swiper(el, {
            loop: false,
            spaceBetween: 20,
            slidesPerView: 1,
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
            watchOverflow: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: el.querySelector('.swiper-pagination'),
                clickable: true,
            },
            navigation: {
                nextEl: el.querySelector('.swiper-button-next'),
                prevEl: el.querySelector('.swiper-button-prev'),
            },
        });

        var refresh = function () {
            swiper.update();
        };
        window.addEventListener('resize', refresh);
        window.addEventListener('load', refresh);
        setTimeout(refresh, 0);
        setTimeout(refresh, 300);
    });
});
