/**
 * @copyright Studio Kiwik 2014 2019
 * @see http://licences.studio-kiwik.fr/responsivegallery
 */
 
$(document).ready(function() {
    var selector = 'select[name="responsive_gallery_itemFilter_a!id_responsive_gallery_gallery"]';

    var doPositionEnabler = function(){
        var value = $(selector).val();
        if(value == ""){
            $('.dragHandle').find('div.dragGroup').removeClass('dragGroup');
        }
        else{
            $('.dragHandle').show()
            $('.blank_td_to_keep_it_good').remove();
            $('.dragHandle').each(function (i, object) {
                var position = parseInt($(object).html());
                $(object).html('').append('<div class="dragGroup"><div class="positions">' + position + '</div>');
            });
        }
    }

    //events
    $(selector).change(function(){
        doPositionEnabler();
    });
    doPositionEnabler();
});