/**
* @copyright Studio Kiwik 2014 2019
* @see http://licences.studio-kiwik.fr/responsivegallery
*/

$(document).ready(function() {

    //caché le input qu'on clone
    if($('.form-group').find('.toClone').length > 1){
        $('.toClone').parents('.form-group').first().parents('.form-group').first().hide();
    }
    else{
        $('.toClone').parents('.form-group').first().hide();
    }

    $('#multiple_image').on('change',function(event){
        $('#all_photos').remove();
        $('#multiple_image').parents('.form-group').first().parents('.form-group').first().append('<div id="all_photos"></div>');

        var files = event.target.files;
        for(var i=0; i < files.length; i++){
            var file_object = files[i];
            addSeparation();
            addImage(file_object,i);
            cloneInput(file_object,i,'alt');
            cloneInput(file_object,i,'link');
            cloneInput(file_object,i,'legend');
        }
        addSeparation();
    });

    function addImage(file_object,number)
    {
        $('#all_photos').append(' ' +
            '<div class="form-group">' +
            '   <label class="control-label col-lg-3"> Image '+(parseInt(number)+1)+'</label>' +
            '   <div class="col-lg-9">' +
            '        <div class="form-group">' +
            '            <div class="col-lg-12"' +
            '                <div><img class="img-responsive" id="image_show_'+number+'" height="300px" width="300px"></div>' +
            '            </div>' +
            '        </div>' +
            '        <div class="form-group">' +
            '           <div class="col-sm-6">' +
            '               <input type="file" name="image_'+number+'" class="hide">' +
            '           </div>' +
            '       </div>' +
            '   </div>' +
            '</div>');

        var fileReader = new FileReader();
        var data = fileReader.onload = function (){
            $('#image_show_'+number).attr('src',fileReader.result);
        };
        fileReader.readAsDataURL(file_object);
    }

    function cloneInput(file_object,number,type)
    {
        var input_clone = getInput();
        input_clone = input_clone.replace(/toCloneLabel/g,'Image '+(parseInt(number)+1) +' '+type);
        input_clone = input_clone.replace(/toClone/g,type+'_'+number);
        $('#all_photos').append(input_clone);
    }

    function getInput()
    {
        if($('.form-group').find('.toClone').length > 1){
            return $('.toClone').parents('.form-group').first().parents('.form-group').first().html();
        }
        else{
            return $('.toClone').parents('.form-group').first().html();
        }
    }

    function addSeparation()
    {
        $('#all_photos').append('<hr/>');
    }
});