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
        /*        if ($smsCheckbox.length && $submitButton.length) {
                    $smsCheckbox.insertBefore($submitButton);
                }*/
        if ($smsCheckbox.length && $privacyCheckbox.length && $newsletterCheckbox.length) {
            $newsletterCheckbox.insertAfter($privacyCheckbox)
            $smsCheckbox.insertAfter($newsletterCheckbox)
            $("#customer-form > div > .form-group:last-child .form-control-comment").css("display", "none")
        } else {
            if ($smsCheckbox.length && $submitButton.length) {
                $smsCheckbox.insertBefore($submitButton);
            }
        }

        if(!$("input[name=newsletter]").is(":checked")){
            $("input[name=newsletter]").val("")
        }

        if($("input[name=newsletter]").val() == ""){
            $("input[name=newsletter]").prop("checked", false)
        }

        if(!$("input[name=sms_optin]").is(":checked")){
            $("input[name=sms_optin]").val("")
        }

        if($("input[name=sms_optin]").val() == ""){
            $("input[name=sms_optin]").prop("checked", false)
        }

        $("input[name=newsletter]").on('change', function(){
            if ($(this).is(':checked')){
                $(this).val("1")
            } else {
                $(this).val("")
            }
        })

        $("input[name=sms_optin]").on('change', function(){
            if ($(this).is(':checked')){
                $(this).val("1")
            } else {
                $(this).val("")
            }
        })
    }
});
