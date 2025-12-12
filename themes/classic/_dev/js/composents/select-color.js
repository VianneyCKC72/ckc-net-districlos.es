function initCustomColorSelect() {
    const $colorGroup = $('#group_2');
    if (!$colorGroup.length) return;

    const $radioButtons = $colorGroup.find('input[type="radio"]');
    if (!$radioButtons.length) return;

    // Supprimer le précédent select custom
    $('.custom-color-select').remove();

    const $customSelect = $('<div class="custom-color-select"></div>');
    const $selectedOption = $('<div class="selected-option"></div>');
    const $optionsList = $('<div class="options-list"></div>');

    $radioButtons.each(function () {
        const $radio = $(this);
        const title = $radio.attr('title');
        const value = $radio.val();
        const color = $radio.siblings('.color').css('background-color');
        const isChecked = $radio.is(':checked');

        const $option = $(`
            <div class="option" data-value="${value}" style="display: flex; align-items: center;">
                <span class="color-dot" style="background-color: ${color};"></span>
                ${title}
            </div>
        `);

        if (isChecked && $selectedOption.children().length === 0) {
            $selectedOption.html(`<span class="color-dot" style="background-color: ${color};"></span> ${title}`);
        }

        $optionsList.append($option);
    });

    $customSelect.append($selectedOption).append($optionsList);
    $colorGroup.css('display', 'none').after($customSelect);

    // Si une seule option, désactiver le clic
    if ($radioButtons.length === 1) {
        $customSelect.css({
            'pointer-events': 'none',
            'cursor': 'default'
        });
        $selectedOption.css({
            'pointer-events': 'none',
            'cursor': 'default'
        });
        $optionsList.css('display', 'none');
    } else {
        $selectedOption.on('click', function (e) {
            e.stopPropagation();
            $(".options-list").not($optionsList).removeClass("open");
            $optionsList.toggleClass("open");
        });

        $optionsList.on('click', '.option', function (e) {
            e.stopPropagation();
            const selectedText = $(this).text();
            const selectedColor = $(this).find('.color-dot').css('background-color');
            const selectedValue = $(this).data('value');

            $selectedOption.html(`<span class="color-dot" style="background-color: ${selectedColor};"></span> ${selectedText}`);
            $optionsList.removeClass('open');

            const $relatedRadio = $radioButtons.filter(`[value="${selectedValue}"]`);
            if ($relatedRadio.length) {
                $relatedRadio.prop('checked', true).trigger('click').trigger('change');
            }
        });
    }

    $(document).on('click', function () {
        $(".options-list").removeClass("open");
    });
}

function initPackColorSelect() {
    return;
    // Nettoyage pour éviter doublon
    $('.custom-select-color').remove();

    $('.styled-color-select').each(function () {
        const $select = $(this);
        const $options = $select.find('option');

        // Appliquer la valeur forçée si elle existe
        const forcedValue = $select.data('force-value');
        if (forcedValue !== undefined && forcedValue !== '') {
            $options.prop('selected', false);
            const $forced = $options.filter(`[value="${forcedValue}"]`);
            if ($forced.length) {
                $forced.prop('selected', true);
                console.log('[initPackColorSelect] valeur forçée appliquée :', forcedValue);
            }
        }

        // Rechercher une option sélectionnée
        let $selectedOption = $select.find('option:selected');
        if (!$selectedOption.length) {
            console.warn('[initPackColorSelect] aucune option sélectionnée → on prend la première');
            $selectedOption = $options.first().prop('selected', true);
        }

        const selectedColor = $selectedOption.data('color');
        const selectedLabel = $selectedOption.text().trim();

        // Construction du select custom
        const $customSelect = $('<div class="custom-select-color"></div>');
        const $selectedDiv = $(`
      <div class="selected" style="cursor: pointer;">
        <span class="color-dot" style="background-color: ${selectedColor};"></span>
        <span class="label">${selectedLabel}</span>
      </div>
    `);
        const $optionsDiv = $('<div class="options"></div>');

        $options.each(function () {
            const $opt = $(this);
            const value = $opt.val();
            const label = $opt.text().trim();
            const color = $opt.data('color');

            const $optionDiv = $(`
        <div class="option" data-value="${value}" style="cursor: pointer;">
          <span class="color-dot" style="background-color: ${color};"></span>
          <span>${label}</span>
        </div>
      `);
            $optionsDiv.append($optionDiv);
        });

        $customSelect.append($selectedDiv).append($optionsDiv);
        $select.after($customSelect).hide();

        console.log('[initPackColorSelect] bloc injecté', $customSelect);

        // Clic sur l'élément sélectionné
        $selectedDiv.on('click', function (e) {
            e.stopPropagation();
            $('.custom-select-color').not($customSelect).removeClass('open');
            $customSelect.toggleClass('open');
        });

        // Clic sur une option
        $optionsDiv.on('click', '.option', function () {
            const value = $(this).data('value');
            const label = $(this).text().trim();
            const color = $(this).find('.color-dot').css('background-color');

            $select.val(value);
            $select.data('force-value', value);
            $select.trigger('change');

            $selectedDiv.html(`
        <span class="color-dot" style="background-color: ${color};"></span>
        <span class="label">${label}</span>
      `);

            $customSelect.removeClass('open');
        });

        // Fermer au clic extérieur
        $(document).on('click', function () {
            $customSelect.removeClass('open');
        });
    });
}


$(document).ready(() => {

    /*** Produit classique (radio boutons de couleur) ***/
    initCustomColorSelect();

    /*** Produit pack (select avec pastilles de couleur) ***/
    initPackColorSelect();

    if ($('body').hasClass('ap5-pack-page')) {
        setInterval(() => {
            if (!$('.custom-select-color').length && $('.styled-color-select').length) {
                console.log('[setInterval] relance de secours initPackColorSelect');
                initPackColorSelect();
            }
        }, 500);
    }

    /*** MutationObserver pour les pages pack ***/
    if ($('body').hasClass('ap5-pack-page')) {
        const packTarget = document.querySelector('.product-variants-item') || document.querySelector('.ap5-attribute-fieldset');

        if (packTarget) {
            const packObserver = new MutationObserver(() => {
                if (!$('.custom-select-color').length && $('.styled-color-select').length) {
                    console.log('[Observer] DOM modifié → relance initPackColorSelect');
                    initPackColorSelect();
                }
            });

            packObserver.observe(packTarget, {
                childList: true,
                subtree: true
            });
        }
    }

    /*** MutationObserver pour les pages produit classiques ***/
    const productTarget = document.querySelector('#product');

    if (productTarget) {
        const productObserver = new MutationObserver(() => {
            const $newColorGroup = $('#group_2');
            if (($newColorGroup.length && $('.custom-color-select').length === 0) || $('#ap5-product-list').length) {
                setTimeout(() => {
                    initCustomColorSelect();
                }, 100);
            }
        });

        productObserver.observe(productTarget, {
            childList: true,
            subtree: true
        });
    }
    $(document).on('updatedProductCombination', function () {
        if ($('body').hasClass('ap5-pack-page')) {
            console.log('[event] updatedProductCombination → relance initPackColorSelect');
            setTimeout(() => {
                initPackColorSelect();
            }, 150); // délai minimum pour que le DOM soit mis à jour
        }
    });

});

// Réagit à tout événement lié à la mise à jour produit PrestaShop
$(document).on('updatedProductCombination updatedProduct updateProductList', function () {
    if ($('body').hasClass('ap5-pack-page')) {
        console.log('[event] PrestaShop updated → initPackColorSelect');
        initPackColorSelect();
    }
});

$(document).on('updatedProductCombination', function () {
    if ($('body').hasClass('ap5-pack-page')) {
        console.log('[event] updatedProductCombination → relance initPackColorSelect');
        setTimeout(() => {
            initPackColorSelect();
        }, 100); // léger délai pour laisser Prestashop finir son DOM
    }
});






