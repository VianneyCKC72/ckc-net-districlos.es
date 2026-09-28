/**
 * @copyright Studio Kiwik 2014 2019
 * @see http://licences.studio-kiwik.fr/responsivegallery
 */
 
$(document).ready(function() {
    $('.form-wrapper').append('' +
        '<div class="advanced-option-title">' +
            'Configuration avancée' +
            '<span id="advanced_config"> cliquez ici</span>' +
        '</div>' +
        '<div id="advanced_show">' +
        '</div>');
    $('.option-advanced').each(function(){
        $(this).parents('.form-group').detach().appendTo('#advanced_show');
    });
    $('#advanced_show').hide();

    $('#advanced_config').on('click',function(){
        $('#advanced_show').toggle('slow');
    })
});