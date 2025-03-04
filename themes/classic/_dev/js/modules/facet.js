function updateFacets() {
    $(".PM_ASCriterionStepEnable").each(function () {
        if ($(this).find("select.PM_ASCriterionGroupSelect").length > 0) {
            $(this).addClass("has-select");
        }
    });

    $(".PM_ASCriterionGroupLinkColor .PM_ASCriterionLink").each(function () {
        var $link = $(this);
        var $input = $link.next("input[type='hidden']");

        if ($link.parent().find("input[type='checkbox']").length > 0) {
            return;
        }

        var id = "color_" + $input.val();
        var color = $link.find(".PM_ASCriterionColorSquare").css("background-color");
        var label = $link.find(".PM_ASCriterionValue").text().trim();

        var isChecked = !$input.prop("disabled"); // Vérifier si l'option est active

        var checkboxHtml = `
            <input type="checkbox" id="${id}" class="PM_ASCriterionCheckbox" value="${$input.val()}" name="${$input.attr('name')}" ${isChecked ? "checked" : ""}>
            <label for="${id}" class="PM_ASLabelCheckbox">
                <span class="PM_ASCriterionColorSquare" style="background:${color};"></span>
                ${label}
            </label>
        `;

        $link.replaceWith(checkboxHtml);
    });

    $(".PM_ASCriterionCheckbox").off("change").on("change", function () {
        var $checkbox = $(this);
        var $hiddenInput = $checkbox.closest("li").find("input[type='hidden']");

        if ($checkbox.is(":checked")) {
            $hiddenInput.prop("disabled", false);
        } else {
            $hiddenInput.prop("disabled", true);
        }

        // Déclencher la mise à jour AJAX de PrestaShop
        $("#PM_ASForm_1").trigger("submit");
    });
}

// Appliquer au chargement de la page
$(document).ready(function () {
    updateFacets();


    if ($(window).width() < 768) {
        // Vérifier que le toggle n'existe pas déjà (éviter les doublons)
        if ($('.filter-toggle').length === 0) {
            let filterTitle = $('<div class="filter-toggle"><span>Filtres</span> <span class="chevron"></span></div>');

            // Insérer le titre avant la card-header
            $('#PM_ASBlock_1 .card-header').before(filterTitle);

            // Cacher les filtres par défaut
            $('#PM_ASBlock_1 .card-block').hide();
        }

        // Afficher le toggle et gérer le clic
        $('.filter-toggle').show().click(function () {
            $('#PM_ASBlock_1 .card-block').slideToggle();
            $('.chevron').toggleClass('open');
        });
    }

});

// Re-appliquer après chaque mise à jour AJAX
$(document).ajaxComplete(function () {
    updateFacets();
});
