document.addEventListener('click', function (e) {
    const url = e.target.closest('.js-variant-link')?.getAttribute('data-url');
    if (url) {
        window.location.href = url;
    }
});

document.addEventListener('keydown', function(e){
    const t = e.target;
    if (!t || t.tagName === 'TEXTAREA') return;
    if ((t.tagName === 'INPUT' || t.tagName === 'SELECT') && e.key === 'Enter') {
        const form = t.form;
        if (!form) return;
        if (form.checkValidity()) {
            e.preventDefault();
            if (form.requestSubmit) form.requestSubmit();
            else form.submit();
        } else {
            form.reportValidity();
        }
    }
});
