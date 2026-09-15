$(document).ready(function () {
    if ($("form.store-search-form").length > 0) {
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
                        if ($.trim(responseData) === '' || $(responseData).filter('.no-results').length || $(responseData).find('.no-results').length) {
                            console.log("Aucun résultat textuel, tentative géolocalisation de la ville...");
                            geocodeVille(query); // tentative géolocalisation
                        } else {
                            $('.nosmagasins').hide().html(responseData).fadeIn(300);
                            console.log("Résultats textuels mis à jour.");
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Erreur AJAX :", status, error);
                    }
                });
            }

            $("svg").on("click", function () {
                $(this).toggleClass("add-favori");
            });
        }

        function debounce(func, wait) {
            let timeout;
            return function () {
                const context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        }

        // Applique le debounce à la recherche automatique
        $(document).on("input", ".store-search-input", debounce(function () {
            if ($(this).val().length >= 3) {
                rechercherMagasins();
            }
        }, 500));

        // Événement sur le bouton loupe
        $(document).on("click", ".recherche", function (e) {
            e.preventDefault(); // Empêche la soumission du formulaire
            rechercherMagasins(); // Lancer la recherche AJAX
        });

        function geocodeVille(ville) {
            $.ajax({
                url: 'https://nominatim.openstreetmap.org/search',
                data: {
                    q: ville,
                    format: 'json',
                    countrycodes: 'fr',
                    limit: 1
                },
                success: function (data) {
                    if (data && data.length > 0) {
                        let lat = data[0].lat;
                        let lon = data[0].lon;

                        console.log("Coordonnées trouvées pour " + ville + " :", lat, lon);

                        $.ajax({
                            type: "POST",
                            url: moduleAjax,
                            data: {
                                latitude: lat,
                                longitude: lon,
                                ajax: true
                            },
                            dataType: "html",
                            success: function (geoResponse) {
                                $('.nosmagasins').hide().html(geoResponse).fadeIn(300);
                                console.log("Résultats proches géolocalisés affichés.");
                            },
                            error: function () {
                                console.error("Erreur lors de la requête AJAX par coordonnées.");
                            }
                        });
                    } else {
                        console.warn("Ville non trouvée : " + ville);
                    }
                },
                error: function () {
                    console.error("Erreur lors de la requête Nominatim.");
                }
            });
        }

        $(document).on("click", ".geolocalisation", function (e) {
            e.preventDefault();
            utiliserMaPosition();
        });

        function utiliserMaPosition() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    $.ajax({
                        type: "POST",
                        url: moduleAjax,
                        data: {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            ajax: true
                        },
                        success: function (response) {
                            $('.nosmagasins').hide().html(response).fadeIn(300);
                            console.log("Résultats géolocalisés mis à jour.");
                        },
                        error: function (xhr, status, error) {
                            console.error("Erreur géolocalisation :", status, error);
                        }
                    });
                }, function (error) {
                    alert("La géolocalisation a échoué : " + error.message);
                });
            } else {
                alert("La géolocalisation n'est pas supportée par ce navigateur.");
            }
        }
    }

    // Section croisée
    let sectionID = $("#section-lien-cross").attr("id");

    let svgIcon = `
<span class="btn-primary-arrow">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-up-right">
        <path d="M13 5H19V11"></path>
        <path d="M19 5L5 19"></path>
    </svg>
</span>`;

    $("#" + sectionID + " h3 > a").each(function () {
        let linkText = $(this).text();
        $(this).html(`<span class="link">${linkText}</span>`).append(svgIcon);
    });
});
