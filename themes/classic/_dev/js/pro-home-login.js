document.addEventListener('DOMContentLoaded', function () {
    var eye = document.querySelector('.pro-eye');
    var input = document.getElementById('pro-login-pass');
    if (!eye || !input) return;
    eye.addEventListener('click', function () {
        const on = input.type === 'password';
        input.type = on ? 'text' : 'password';
        eye.classList.toggle('is-on', on);
        input.focus();
    });
});
