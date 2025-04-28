$(document).ready(function () {
    if($("form.store-search-form").length > 0) {
        var modulePath = $("form.store-search-form").attr('action');
        var moduleAjax = modulePath.replace(/&amp;/g, "&");

        function rechercherMagasins() {
            let query = $.trim($(".store-search-input").val());

            if (query !== '') {
                $.ajax({
                    type: "POST",
                    url: moduleAjax,
                    data: {
                        "query": query,
                        "ajax": true
                    },
                    dataType: "html",
                    success: function (responseData) {
                        $('.nosmagasins').hide().html(responseData).fadeIn(300); // Animation de l'apparition
                        console.log("Résultats mis à jour.");
                    },
                    error: function (xhr, status, error) {
                        console.error("Erreur AJAX :", status, error);
                    }
                });
            }

            $("svg").on("click", function() {
                $(this).toggleClass("add-favori");
            });
        }

        // Événement sur l'entrée (auto-search après 3 caractères)
        $(document).on("input", ".store-search-input", function () {
            rechercherMagasins();
        });

        // Événement sur le bouton loupe
        $(document).on("click", ".recherche", function (e) {
            e.preventDefault(); // Empêche la soumission du formulaire
            rechercherMagasins(); // Lancer la recherche AJAX
        });
    }


    // Récupérer l'ID de la section
    let sectionID = $("#section-lien-cross").attr("id");

    // Définition du bouton SVG
    let svgIcon = `
<span class="btn-primary-arrow">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-up-right">
        <path d="M13 5H19V11"></path>
        <path d="M19 5L5 19"></path>
    </svg>
</span>`;

    $("#" + sectionID + " h3 > a").each(function() {
        let linkText = $(this).text(); // Récupère le texte du lien
        $(this).html(`<span class="link">${linkText}</span>`).append(svgIcon);
    });




});
