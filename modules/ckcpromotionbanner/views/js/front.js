/**
* 2007-2024 PrestaShop
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
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/


$(document).on('click', '.close_once', function() {
    localStorage.setItem('hideAlert', 'true');
})

$(document).ready(function() {
    let hide = localStorage.getItem('hideAlert');
    if (hide !== 'true') {
        $('#ckcpromobarre').removeClass('hide_alert');
    }
})
$(document).on('mouseover', '.button_barrepromo', function() {
    $('.button_text_barrepromo').show();
})
$(document).on('mouseleave', '.button_barrepromo', function() {
    $('.button_text_barrepromo').hide();
})

var currentIndexPromoBarre = 0;
var $paragraphs = $('.ckcpromobarre-text');
var totalParagraphs = $paragraphs.length;

if(totalParagraphs > 1){
    $paragraphs.eq(currentIndexPromoBarre).show();
    if (totalParagraphs > 1) {
        interval = setInterval(function() {
            $paragraphs.eq(currentIndexPromoBarre).fadeOut(500, function() {
                currentIndexPromoBarre = (currentIndexPromoBarre + 1) % totalParagraphs;
                $paragraphs.eq(currentIndexPromoBarre).fadeIn(500)
            })
        }, 5000)
    }


    document.addEventListener('visibilitychange', function() {
        if(document.hidden) {
            // tab is now inactive
            clearInterval(interval)
        }
        else {
            if(totalParagraphs > 1){
                $paragraphs.eq(currentIndexPromoBarre).show();
                if (totalParagraphs > 1) {
                    interval = setInterval(function() {
                        $paragraphs.eq(currentIndexPromoBarre).fadeOut(500, function() {
                            currentIndexPromoBarre = (currentIndexPromoBarre + 1) % totalParagraphs;
                            $paragraphs.eq(currentIndexPromoBarre).fadeIn(500)
                        })
                    }, 5000)
                }
            }
        }
    });
}

