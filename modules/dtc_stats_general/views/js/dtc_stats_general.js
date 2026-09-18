/**
 * Script pour le module DTC Stats General
 */
$(document).ready(function() {
    // Script pour le bouton expand
    $("#expandStats").click(function() {
        $(".sidebar").toggleClass("hide");
        $("#statsContainer").toggleClass("w-100");
        
        // Changer icône
        var icon = $(this).find("i");
        if (icon.hasClass("fa-expand")) {
            icon.removeClass("fa-expand").addClass("fa-compress");
        } else {
            icon.removeClass("fa-compress").addClass("fa-expand");
        }
    });
});
