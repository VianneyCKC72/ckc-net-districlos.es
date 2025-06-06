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

    $('.video-player-presentation-container').click(function (){
        $('.video-player-presentation-img').css("display", "none")
        $('.home-play-icon').css("display", "none")
        let containerHeight = $('.video-player-presentation-img').height()

        let videoWidth = 0;
        let leftPosition = 10 + "px";
        let docWidth = $(document).width();
        if (docWidth > 1399 ){
            videoWidth = $('.elementor-element-65813dcb').width() / 1.5;
            leftPosition = 85 + "%" ;
        } else {
            videoWidth = $('.elementor-element-c1c3695').width();
            leftPosition = 10 + "px";
        }

        let videoElement = "<div style='position: absolute; width: "+videoWidth+"px; height: 100%; top: -10px; left: -"+leftPosition+"; z-index: 999;background-color: #fff;' class='video-player-presentation'><div class='close-video-button'>X</div><lite-vimeo videoid='1079777003' hash='55cf8a4d10' autoload autoplay videotitle='Vidéo de présentation'><div class='ltv-playbtn'></div></lite-vimeo></div>"
        $(this).append(videoElement)
        $(this).css("height", containerHeight)
    })

    $(document).on("click", ".close-video-button", function(){
        $(".video-player-presentation").remove()
        $('.video-player-presentation-img').css("display", "inline")
        $('.home-play-icon').css("display", "block")
    })

});
