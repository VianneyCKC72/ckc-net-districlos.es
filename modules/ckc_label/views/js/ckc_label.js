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
        toggleSelectionFields();
    }

    const translateBtn = document.getElementById('ckc-label-translate-en');
    if (translateBtn && window.ckcLabelBo) {
        translateBtn.addEventListener('click', function () {
            const src = document.getElementById('text_' + ckcLabelBo.idLangDefault)
                || document.querySelector('[name="text_' + ckcLabelBo.idLangDefault + '"]');
            const dest = document.getElementById('text_' + ckcLabelBo.idLangEn)
                || document.querySelector('[name="text_' + ckcLabelBo.idLangEn + '"]');
            if (!src || !dest) {
                return;
            }
            const sourceText = (src.value || '').trim();
            if (!sourceText) {
                alert(ckcLabelBo.errorEmpty);
                return;
            }
            if ((dest.value || '').trim() !== '' && !window.confirm(ckcLabelBo.confirmOverwrite)) {
                return;
            }
            translateBtn.disabled = true;
            const body = new URLSearchParams();
            body.append('text', sourceText);
            fetch(ckcLabelBo.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                credentials: 'same-origin',
                body: body.toString(),
            }).then(function (r) { return r.json(); }).then(function (json) {
                if (!json || !json.success) {
                    alert((json && json.message) ? json.message : ckcLabelBo.errorGeneric);
                    return;
                }
                dest.value = json.text;
            }).catch(function () {
                alert(ckcLabelBo.errorGeneric);
            }).finally(function () {
                translateBtn.disabled = false;
            });
        });
    }
});
