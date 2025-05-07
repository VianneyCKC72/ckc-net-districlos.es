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

        if (isChecked) {
            $selectedOption.html(`<span class="color-dot" style="background-color: ${color};"></span> ${title}`);
        }

        $optionsList.append($option);
    });

    $customSelect.append($selectedOption).append($optionsList);
    $colorGroup.css('display', 'none').after($customSelect);

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

    $(document).on('click', function () {
        $(".options-list").removeClass("open");
    });
}

$(document).ready(() => {
    initCustomColorSelect();

    // On observe #product car il ne bouge jamais
    const target = document.querySelector('#product');

    if (target) {
        const observer = new MutationObserver(() => {
            const $newColorGroup = $('#group_2');
            // S'assurer qu'on relance SEULEMENT si #group_2 est nouveau
            if (($newColorGroup.length && $('.custom-color-select').length === 0) || $('#ap5-product-list').length) {
                setTimeout(() => {
                    initCustomColorSelect();
                }, 100);
            }
        });

        observer.observe(target, {
            childList: true,
            subtree: true
        });
    }
});
