import Swiper from 'swiper/bundle';

document.querySelectorAll('.wrapper__swiper__featured-products').forEach((wrapper, index) => {
    const swiperFeaturedProducts = new Swiper(wrapper.querySelector('.swiper__featured-products'), {
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
            el: wrapper.querySelector('.swiper-pagination'),
            clickable: true,
        },
        navigation: {
            nextEl: wrapper.querySelector('.swiper-button-next'),
            prevEl: wrapper.querySelector('.swiper-button-prev'),
        },
    });
});


const swiperHeroHome = new Swiper('.swiper__home-banner', {
    loop: true,
    autoplay: true,
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },
});