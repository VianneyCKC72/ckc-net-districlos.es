$(document).ready(function () {
    if ($('body').hasClass('page-registration')) {
        var $form = $('#customer-form');

        // Sélectionner les éléments du formulaire
        var $formFields = $form.find('.form-group').not(':has(input[type="checkbox"])'); // Champs de formulaire hors checkboxes
        var $registerButton = $form.find('.actions-container-register'); // Bouton "Enregistrer"
        var $socialButtons = $form.find('.ets_solo_social_wrapper'); // Boutons sociaux (Facebook, Google)

        // Sélectionner les cases à cocher dans le bon ordre
        var $termsCheckbox = $form.find('input[name="psgdpr"]').closest('.form-group'); // Conditions générales
        var $privacyCheckbox = $form.find('input[name="customer_privacy"]').closest('.form-group'); // Confidentialité
        var $newsletterCheckbox = $form.find('input[name="newsletter"]').closest('.form-group'); // Newsletter
        var $smsCheckbox = $form.find('input[name="sms_optin"]').closest('.form-group'); // SMS

        // Réorganiser les éléments dans le bon ordre
        if ($formFields.length) {
            $formFields.each(function () {
                $form.append($(this));
            });
        }

        if ($registerButton.length) {
            $form.append($registerButton);

            // Ajouter un texte avant les boutons sociaux
            var orLabel = $form.attr('data-ou-bien') || 'Ou bien...';
            var $orConnectText = $('<p class="txt_ou_bien text-center mt-4"><strong></strong></p>');
            $orConnectText.find('strong').text(orLabel);
            $registerButton.after($orConnectText);
        }

        if ($socialButtons.length) {
            $form.append($socialButtons);
        }

        // Ajouter les checkboxes dans l'ordre souhaité
        if ($termsCheckbox.length) $form.append($termsCheckbox);
        if ($privacyCheckbox.length) $form.append($privacyCheckbox);
        if ($newsletterCheckbox.length) $form.append($newsletterCheckbox);
        if ($smsCheckbox.length) $form.append($smsCheckbox);


        $("input[name=psgdpr]").val("")
        $("input[name=customer_privacy]").val("")
        $("input[name=newsletter]").val("")
        $("input[name=sms_optin]").val("")

        $("input[name=psgdpr]").prop("checked", false)
        $("input[name=customer_privacy]").prop("checked", false)
        $("input[name=newsletter]").prop("checked", false)
        $("input[name=sms_optin]").prop("checked", false)
        $(".radio-inline.id_gender input[value=1]").prop("checked", true)
        $(".radio-inline.id_gender input[value=2]").prop("checked", false)

        $("input[name=psgdpr]").on('change', function(){
            if ($(this).is(':checked')){
                $(this).val("1")
            } else {
                $(this).val("")
            }
        })
        $("input[name=customer_privacy]").on('change', function(){
            if ($(this).is(':checked')){
                $(this).val("1")
            } else {
                $(this).val("")
            }
        })

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

        if($(".js-input-column .help-block ul li").text() == "Cette adresse e-mail est déjà utilisée, choisissez-en une autre ou connectez-vous."){

            localStorage.removeItem("psgdpr");
            localStorage.removeItem("customer_privacy");
            localStorage.removeItem("newsletter");
            localStorage.removeItem("sms_optin");

            $("input[name=psgdpr]").prop("checked", false)
            $("input[name=customer_privacy]").prop("checked", false)
            $("input[name=newsletter]").prop("checked", false)
            $("input[name=sms_optin]").prop("checked", false)
        } else {
            localStorage.removeItem("id_gender");
        }
    }
});
