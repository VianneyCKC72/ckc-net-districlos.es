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
