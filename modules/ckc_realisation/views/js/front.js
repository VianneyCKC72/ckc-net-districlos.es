$('.image-gallery-accordeon').click(function(){
    $(this).prev().find('.image-single-invisible').each(function(){
        $(this).css('display', 'block')
    })
    $(this).css('display', 'none')
    $(this).next().css('display', 'block')
})

$('.image-gallery-accordeon-hide').click(function(){
    $(this).prev().prev().find('.image-single-invisible').each(function(){
        $(this).css('display', 'none')
    })
    $(this).css('display', 'none')
    $(this).prev().css('display', 'block')
})
