document.addEventListener('DOMContentLoaded', function () {
    $("a[title=Dupliquer]").each(function(){
        let actionText = $(this).attr('onclick')
        actionText = actionText.replace("Cela copiera également les images. ", "");
        $(this).attr('onclick', actionText);
    })
});
