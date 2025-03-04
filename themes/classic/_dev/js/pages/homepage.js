$(document).ready(function () {
    if ($("#index").length === 0) {
        console.log("🚫 Pas sur la home, script arrêté.");
        return;
    }

    var $swiperElement = $(".elementor-carousel-wrapper.swiper");
    if ($swiperElement.length === 0) {
        console.log("🚫 Aucun Swiper Elementor trouvé, script arrêté.");
        return;
    }

    var $prevButton = $(".elementor-swiper-button-prev");
    var $nextButton = $(".elementor-swiper-button-next");

    if ($prevButton.length && $nextButton.length) {

        $prevButton.removeClass("elementor-swiper-button").addClass("swiper-button-prev");
        $nextButton.removeClass("elementor-swiper-button").addClass("swiper-button-next");

        $prevButton.html(`
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-left">
        <path d="M6 8L2 12L6 16"></path>
        <path d="M2 12H22"></path>
    </svg>
`);

        $nextButton.html(`
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-right">
        <path d="M18 8L22 12L18 16"></path>
        <path d="M2 12H22"></path>
    </svg>
`);
    }

    $('.elementor-element-5089e70f .elementor-widget-call-to-action .elementor-cta-content').each(function () {
        // Vérifier si le bouton est déjà présent
        if ($(this).find('.btn-primary-arrow').length === 0) {
            // Ajouter le bouton à la fin de l'élément CTA
            $(this).append(`
                <span class="btn-primary-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-up-right"> 
                        <path d="M13 5H19V11"></path> 
                        <path d="M19 5L5 19"></path>  
                    </svg>
                </span>
            `);
        }
    });

});
