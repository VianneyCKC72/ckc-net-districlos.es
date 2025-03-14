$(document).ready(function () {
    if ($('body').hasClass('page-identity')) {
        var $form = $('#customer-form');

        // Sélectionner les cases à cocher dans le bon ordre
        var $termsCheckbox = $form.find('input[name="psgdpr"]').closest('.form-group'); // Conditions générales
        var $privacyCheckbox = $form.find('input[name="customer_privacy"]').closest('.form-group'); // Confidentialité
        var $newsletterCheckbox = $form.find('input[name="newsletter"]').closest('.form-group'); // Newsletter
        var $smsCheckbox = $form.find('input[name="sms_optin"]').closest('.form-group'); // SMS
        var $submitButton = $form.find('.actions-container-register'); // Bouton "Enregistrer"

        // Vérifier si tous les éléments existent avant de les déplacer
        if ($smsCheckbox.length && $submitButton.length) {
            $smsCheckbox.insertBefore($submitButton);
        }
    }
});
