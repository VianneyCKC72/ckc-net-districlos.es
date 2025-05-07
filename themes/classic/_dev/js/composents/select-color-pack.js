function initCustomColorSelect() {
    const $colorGroup = $('#group_2');
    if (!$colorGroup.length) return;

    const $radioButtons = $('.options-list .option');
    if (!$radioButtons.length) return;

    // Supprimer le précédent select custom
    const $selectedOption = $('<div class="selected-option"></div>');
    const $optionsList = $('<div class="options-list"></div>');

    $selectedOption.on('click', function (e) {
        e.stopPropagation();
        $(".options-list").not($optionsList).removeClass("open");
        $optionsList.toggleClass("open");
    });

    $optionsList.on('click', '.option', function (e) {
        e.stopPropagation();
        const selectedValue = $(this).data('value');

        $selectedOption.html(`<span class="color-dot" style="background-color: ${selectedColor};"></span> ${selectedText}`);
        $optionsList.removeClass('open');

        const $relatedRadio = $(`input[type=radio][value="${selectedValue}"]`);
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
            // S'assurer qu'on relance SEULEMENT si #group_2 est nouveau
            if ($('#ap5-product-list').length) {
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
