/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/
if(!$('#module-pm_advancedsearch4-searchresults #content-wrapper').hasClass('col-lg-9')){
    $('#module-pm_advancedsearch4-searchresults #content-wrapper').addClass('col-lg-9')
}
if(!$('#module-pm_advancedsearch4-searchresults #content-wrapper').hasClass('col-md-8')) {
    $('#module-pm_advancedsearch4-searchresults #content-wrapper').addClass('col-md-8')
}

$(".customfilterselector-option-container").on('click', function(){
    if($(this).hasClass('customfilterselector-selected')){
        $("#customfilterselector-validate-option-first").attr('disabled', 'disabled')
        $(this).removeClass('customfilterselector-selected')
    } else {
        $("#customfilterselector-validate-option-first").removeAttr('disabled')
        $(".customfilterselector-option-container").each(function(){
            $(this).removeClass('customfilterselector-selected')
        })
        $(this).addClass('customfilterselector-selected')
    }

})
$(".customfilterselector-option-container").on('click', function(){
    if(!$(this).hasClass("customfilterselector-option-selected")){
        $(this).addClass("customfilterselector-option-selected")
    } else {
        $(this).removeClass("customfilterselector-option-selected")
    }

})
$(document).on('click', '#customfilterselector-validate-option-first', function(){
    if($('#customfilterselector-validate-option-first').attr('disabled') != 'disabled'){
/*
        console.log(heightOptions3)
        console.log(heightOptions2)
        console.log(heightOptions128)
*/
        $selected = false

        $("#customfilterselector:not(.customfilterselector-hauteur) .customfilterselector-option-container").each(function(){
            if($(this).hasClass('customfilterselector-selected')){
                $selected = $(this).find(".customfilterselector-option-text").text()
            }
        })

        $("#customfilterselector.customfilterselector-hauteur .customfilterselector-options-container").empty()

        if($selected) {
            if ($selected == "Clôture rigide") {
                var heights = heightOptions3
            } else if ($selected == "Clôture souple") {
                var heights = heightOptions2
            } else if ($selected == "Clôture pour animaux") {
                var heights = heightOptions128
            }
        } else {
            var heights = heightOptions
        }

        for (height of heights){
            let html = '<div class="customfilterselector-option-container"><div class="customfilterselector-option-text">'+height.name+'</div></div>'
            $("#customfilterselector.customfilterselector-hauteur .customfilterselector-options-container").append(html)
        }

        $("#customfilterselector:not(.customfilterselector-hauteur)").css('display', 'none')
        $("#customfilterselector.customfilterselector-hauteur").css('display', 'flex')

    }
})

$(document).on('click', '#customfilterselector-skip-option', function(){

    $("#customfilterselector.customfilterselector-hauteur .customfilterselector-options-container").empty()

    var heights = heightOptions

    for (height of heights){
        let html = '<div class="customfilterselector-option-container"><div class="customfilterselector-option-text">'+height.name+'</div></div>'
        $("#customfilterselector.customfilterselector-hauteur .customfilterselector-options-container").append(html)
    }

    $("#customfilterselector:not(.customfilterselector-hauteur) .customfilterselector-option-container").each(function(){
        $(this).removeClass('customfilterselector-selected')
    })

    $("#customfilterselector:not(.customfilterselector-hauteur)").css('display', 'none')
    $("#customfilterselector.customfilterselector-hauteur").css('display', 'flex')
})
$(document).on('click', '#customfilterselector-back-option', function(){
    $("#customfilterselector.customfilterselector-hauteur").css('display', 'none')
    $("#customfilterselector:not(.customfilterselector-hauteur)").css('display', 'flex')

})

$(document).on('click', '.customfilterselector-hauteur .customfilterselector-option-container', function(){

    if(!$(this).hasClass('customfilterselector-option-selected')){
        $(".customfilterselector-hauteur .customfilterselector-option-container").each(function(){
            $(this).removeClass('customfilterselector-option-selected')
        })
        $(this).addClass('customfilterselector-option-selected')
    } else {
        $(this).removeClass('customfilterselector-option-selected')
    }

})

$(document).on('click', '.customfilterselector-hauteur #customfilterselector-validate-option-all', function(){

    let $selectedCategory = false
    let $selectedHeight = false


    $selectedCategory = $("#customfilterselector:not(.customfilterselector-hauteur) .customfilterselector-option-container.customfilterselector-selected").find(".customfilterselector-option-text").text()

    $selectedHeight = $(this).parent().prev().find('.customfilterselector-option-container.customfilterselector-option-selected .customfilterselector-option-text').text()

    $selectedHeightRange = $selectedHeight.replace("m", ".")

    let url = base_url + '/3-cloture-rigide/s-'+id3_customfilter+'/hauteur_du_grillage_en_m-'+$selectedHeightRange+':'+$selectedHeightRange

    if ($selectedCategory == "Clôture rigide") {

        let id = $('.PM_ASBlockOutput.PM_ASBlockOutputVertical').data('id-search')
        url = base_url + '/3-cloture-rigide/s-'+id3_customfilter+'/hauteur_du_grillage_en_m-'+$selectedHeightRange+':'+$selectedHeightRange

    } else if ($selectedCategory == "Clôture souple") {
        url = base_url + '/2-grillage-souple/s-'+id2_customfilter+'/hauteur-'+$selectedHeight

    } else if ($selectedCategory == "Clôture pour animaux") {
        url = base_url + '/128-cloture-agricole/s-'+id128_customfilter+'/hauteur-'+$selectedHeight
    }

    window.location = url;

})