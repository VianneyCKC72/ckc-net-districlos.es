document.addEventListener('DOMContentLoaded', function () {
    const applyToSelect = document.querySelector('[name="apply_to"]');
    let categoryField = document.querySelector('.tree-panel-heading-controls');
    let productField = document.querySelector('.ckc-label-products');

    // get parent
    productField = productField?.parentNode.parentNode;
    categoryField = categoryField?.parentNode.parentNode.parentNode;
    function toggleSelectionFields() {
        const selectedValue = applyToSelect?.value;

        if (selectedValue === 'categories') {
            categoryField?.classList.remove('hidden');
            productField?.classList.add('hidden');
        } else if (selectedValue === 'products') {
            productField?.classList.remove('hidden');
            categoryField?.classList.add('hidden');
        } else {
            categoryField?.classList.add('hidden');
            productField?.classList.add('hidden');
        }
    }

    if (applyToSelect) {
        applyToSelect.addEventListener('change', toggleSelectionFields);
        toggleSelectionFields(); // Initial call
    }
});
