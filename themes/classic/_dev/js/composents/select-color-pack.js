function initCustomColorSelect() {
    const $radioButtons = $('.options-list .option');
    if (!$radioButtons.length) return;

    // Supprimer le précédent select custom
    const $selectedOption = $('.selected-option');

    $selectedOption.on('click', function (e) {
        e.stopPropagation();
        $('.options-list').removeClass("open")
        $(this).next().toggleClass("open");
    });

    $('.options-list').on('click', '.option', function (e) {
        e.stopPropagation();
        const selectedValue = $(this).data('value');
        const selectedName = $(this).data('name');

        $selectedOption.html(`<span class="color-dot" style="background-color: ${selectedColor};"></span> ${selectedText}`);
        $('.options-list').removeClass('open');

        const $relatedRadio = $(`input[name="${selectedName}"][value="${selectedValue}"]`);
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
