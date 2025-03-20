$(document).ready(function() {
    if ($('body').hasClass('page-order')) {

        // Supprimer le doublon des boutons sociaux
        $('.ets_solo_social_wrapper:not(:first)').remove();

        // Vérifier si la case "Recevoir les bons plans et promotions par SMS" et "Message concernant la confidentialité des données clients" existent avant de les déplacer
        if ($('input[name="sms_optin"]').length && $('input[name="customer_privacy"]').length) {
            $('input[name="sms_optin"]').closest('.form-group.row').insertAfter($('input[name="customer_privacy"]').closest('.form-group'));
        }

        // Vérifier si les boutons Facebook et Google existent et ne pas les déplacer s'ils sont déjà au bon endroit
        if ($('.ets_solo_social_wrapper').length && $('.nav.nav-inline').length) {
            if (!$('.nav.nav-inline').next().hasClass('ets_solo_social_wrapper')) {
                $('.ets_solo_social_wrapper').insertAfter('.nav.nav-inline');
            }
        }

        // Masquer le titre "Civilité"
        if ($('label[for="field-id_gender"]').length) {
            $('label[for="field-id_gender"]').hide();
        }

        // Masquer le texte "Optionnel"
        $('.form-control-comment').each(function() {
            if ($(this).text().trim() === "Optionnel") {
                $(this).hide();
            }
        });

        if(typeof default_address_id != 'undefined'){
            $("article.address-item").each(function(){
                if($(this).find("input").val() == default_address_id){
                    $(this).addClass('selected')
                    $(this).find("input").prop('checked', true)
                } else {
                    $(this).removeClass('selected')
                    $(this).find("input").prop('checked', false)
                }
            })
        }

    }
});
