$(document).ready(function () {
    if (new Date() > new Date(2024, 9, 31, 11, 0, 0) && new Date() < new Date(2024, 10, 1, 6, 0, 0)) {
        $('.ephemeral-promo').hide()
    }

    /* Défilement vers le haut de la page. */
    $("#bloclist").click(function () {
        $("html,body").animate(
            {scrollTop: $("#ancre-item").offset().top - 100},
            "slow"
        );
    });

    /* Sélectionner l'index du bouton cliqué, puis utiliser cet index pour sélectionner la div
      correspondante et la fondre. */
    $(".button-apparition").click(function () {
        var myIndex = $(this).index();
        myIndex = myIndex + 1;

        $("#ancre-item>div:nth-child(" + myIndex + ")").fadeIn(500);
        $("#ancre-item>div:not(:nth-child(" + myIndex + "))").fadeOut(0);
    });
});