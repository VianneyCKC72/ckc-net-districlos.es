document.addEventListener('DOMContentLoaded', function () {
    const smsOptinBadge = document.getElementById('sms-optin-badge');
    const targetBlock = document.querySelector('.customer-personal-informations-card .card-body');

    if (smsOptinBadge && targetBlock) {
        targetBlock.appendChild(smsOptinBadge);
    }
});
