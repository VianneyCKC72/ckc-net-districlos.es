document.addEventListener('DOMContentLoaded', function () {
    const resetBtn = document.getElementById('reset-search');
    const form = document.getElementById('job-search-form');

    resetBtn.addEventListener('click', function (e) {
        e.preventDefault();
        form.querySelectorAll('input[type="text"]').forEach(input => input.value = '');
        window.location.href = form.getAttribute('action'); // recharge la page sans paramètres
    });
});
