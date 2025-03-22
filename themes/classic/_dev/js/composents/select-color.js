$(document).ready(() => {

    /* CHAMP COULEUR CUSTOM SUR LA PAGE PRODUIT */
    const $colorGroup = $('#group_2');
    const $radioButtons = $colorGroup.find('input[type="radio"]');

    // Création du menu déroulant personnalisé avec une classe spécifique
    let $customSelect = $('<div class="custom-color-select"></div>');
    let $selectedOption = $('<div class="selected-option"></div>');
    let $optionsList = $('<div class="options-list"></div>');

    $radioButtons.each(function () {
        const $radio = $(this);
        const title = $radio.attr('title');
        const value = $radio.val();
        const isChecked = $radio.is(':checked');
        const color = $radio.siblings('.color').css('background-color');

        let $option = $(`
            <div class="option" data-value="${value}" style="display: flex; align-items: center;">
                <span class="color-dot" style="background-color: ${color};"></span>
                ${title}
            </div>
        `);

        // Sélection par défaut
        if (isChecked) {
            $selectedOption.html(`<span class="color-dot" style="background-color: ${color};"></span> ${title}`);
        }

        $optionsList.append($option);
    });

    // Ajouter au DOM
    $customSelect.append($selectedOption).append($optionsList);
    $colorGroup.replaceWith($customSelect);

    // Afficher/Masquer les options
    $selectedOption.on('click', function (e) {
        e.stopPropagation();
        $(".options-list").not($optionsList).removeClass("open"); // Ferme les autres sélects ouverts
        $optionsList.toggleClass("open");
    });

    // Gestion du clic sur une option
    $optionsList.on('click', '.option', function (e) {
        e.stopPropagation();

        let selectedText = $(this).text();
        let selectedColor = $(this).find('.color-dot').css('background-color');
        let selectedValue = $(this).data('value');

        // Mettre à jour l'affichage du select
        $selectedOption.html(`<span class="color-dot" style="background-color: ${selectedColor};"></span> ${selectedText}`);

        // Fermer la liste
        $optionsList.removeClass('open');

        // Synchroniser les boutons radio
        let $relatedRadio = $radioButtons.filter(`[value="${selectedValue}"]`);
        if ($relatedRadio.length) {
            $relatedRadio.prop('checked', true).trigger('change'); // Déclencher l'événement pour la mise à jour des autres attributs
        }
    });

    // Cacher la liste si on clique en dehors
    $(document).on('click', function () {
        $(".options-list").removeClass("open");
    });
});
