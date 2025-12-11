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
$(window).ready(function() {
    if(typeof $("input#CART_TYPE_on") !== 'undefined'){
        if($("input#CART_TYPE_on").is(':checked')){
            $("input#CART_RANGED_DISCOUNT").parent().parent().hide()
            $("input#CART_RANGED_DISCOUNT_MAX").parent().parent().hide()
            $("input#CART_RANGED_PRICE_MIN").parent().parent().hide()
            $("input#CART_RANGED_PRICE_MAX").parent().parent().hide()
        } else if ($("input#CART_TYPE_off").is(':checked')) {
            $("input#CART_FIXED_DISCOUNT").parent().parent().hide()
            $("input#CART_FIXED_PRICE").parent().parent().hide()
        }


        $("input#CART_TYPE_on").change(function(){
            if($(this).is(':checked')){

                $("input#CART_FIXED_DISCOUNT").parent().parent().show()
                $("input#CART_FIXED_PRICE").parent().parent().show()

                $("input#CART_RANGED_DISCOUNT").parent().parent().hide()
                $("input#CART_RANGED_DISCOUNT_MAX").parent().parent().hide()
                $("input#CART_RANGED_PRICE_MIN").parent().parent().hide()
                $("input#CART_RANGED_PRICE_MAX").parent().parent().hide()
            }
        })

        $("input#CART_TYPE_off").change(function(){
            if($(this).is(':checked')){
                $("input#CART_RANGED_DISCOUNT").parent().parent().show()
                $("input#CART_RANGED_DISCOUNT_MAX").parent().parent().show()
                $("input#CART_RANGED_PRICE_MIN").parent().parent().show()
                $("input#CART_RANGED_PRICE_MAX").parent().parent().show()

                $("input#CART_FIXED_DISCOUNT").parent().parent().hide()
                $("input#CART_FIXED_PRICE").parent().parent().hide()
            }
        })
    }

    if(typeof $("select#CART_TYPE") !== 'undefined'){


        let selected = $("select#CART_TYPE").find('option:selected')

        if (selected.val() == 1){
            $("input#CART_FIXED_DISCOUNT").parent().parent().show()
            $("input#CART_FIXED_PRICE").parent().parent().show()

            $("input#CART_RANGED_DISCOUNT").parent().parent().hide()
            $("input#CART_RANGED_DISCOUNT_SECOND").parent().parent().hide()
            $("input#CART_RANGED_DISCOUNT_THIRD").parent().parent().hide()
            $("input#CART_RANGED_DISCOUNT_MAX").parent().parent().hide()
            $("input#CART_RANGED_PRICE_MIN").parent().parent().hide()
            $("input#CART_RANGED_PRICE_SECOND").parent().parent().hide()
            $("input#CART_RANGED_PRICE_MAX").parent().parent().hide()
        } else if (selected.val() == 0){
            $("input#CART_RANGED_DISCOUNT").parent().parent().show()
            $("input#CART_RANGED_DISCOUNT_MAX").parent().parent().show()
            $("input#CART_RANGED_PRICE_MIN").parent().parent().show()

            $("input#CART_FIXED_DISCOUNT").parent().parent().hide()
            $("input#CART_FIXED_PRICE").parent().parent().hide()
            $("input#CART_RANGED_DISCOUNT_SECOND").parent().parent().hide()
            $("input#CART_RANGED_PRICE_SECOND").parent().parent().hide()
            $("input#CART_RANGED_PRICE_MAX").parent().parent().hide()
            $("input#CART_RANGED_DISCOUNT_THIRD").parent().parent().hide()
        } else if (selected.val() == 2){
            $("input#CART_RANGED_DISCOUNT").parent().parent().show()
            $("input#CART_RANGED_DISCOUNT_MAX").parent().parent().show()
            $("input#CART_RANGED_PRICE_MIN").parent().parent().show()
            $("input#CART_RANGED_PRICE_MAX").parent().parent().show()
            $("input#CART_RANGED_DISCOUNT_SECOND").parent().parent().show()


            $("input#CART_FIXED_DISCOUNT").parent().parent().hide()
            $("input#CART_FIXED_PRICE").parent().parent().hide()
            $("input#CART_RANGED_PRICE_SECOND").parent().parent().hide()
            $("input#CART_RANGED_DISCOUNT_THIRD").parent().parent().hide()

        } else if (selected.val() == 3){
            $("input#CART_RANGED_DISCOUNT").parent().parent().show()
            $("input#CART_RANGED_DISCOUNT_MAX").parent().parent().show()
            $("input#CART_RANGED_PRICE_MIN").parent().parent().show()
            $("input#CART_RANGED_PRICE_MAX").parent().parent().show()
            $("input#CART_RANGED_DISCOUNT_SECOND").parent().parent().show()
            $("input#CART_RANGED_DISCOUNT_THIRD").parent().parent().show()
            $("input#CART_RANGED_PRICE_SECOND").parent().parent().show()

            $("input#CART_FIXED_DISCOUNT").parent().parent().hide()
            $("input#CART_FIXED_PRICE").parent().parent().hide()

        }


        $("select#CART_TYPE").on('change', function(){
            let selectedOption = $(this).find('option:selected')

            if (selectedOption.val() == 1){
                $("input#CART_FIXED_DISCOUNT").parent().parent().show()
                $("input#CART_FIXED_PRICE").parent().parent().show()

                $("input#CART_RANGED_DISCOUNT").parent().parent().hide()
                $("input#CART_RANGED_DISCOUNT_SECOND").parent().parent().hide()
                $("input#CART_RANGED_DISCOUNT_THIRD").parent().parent().hide()
                $("input#CART_RANGED_DISCOUNT_MAX").parent().parent().hide()
                $("input#CART_RANGED_PRICE_MIN").parent().parent().hide()
                $("input#CART_RANGED_PRICE_SECOND").parent().parent().hide()
                $("input#CART_RANGED_PRICE_MAX").parent().parent().hide()
            } else if (selectedOption.val() == 0){
                $("input#CART_RANGED_DISCOUNT").parent().parent().show()
                $("input#CART_RANGED_DISCOUNT_MAX").parent().parent().show()
                $("input#CART_RANGED_PRICE_MIN").parent().parent().show()

                $("input#CART_FIXED_DISCOUNT").parent().parent().hide()
                $("input#CART_FIXED_PRICE").parent().parent().hide()
                $("input#CART_RANGED_DISCOUNT_SECOND").parent().parent().hide()
                $("input#CART_RANGED_PRICE_SECOND").parent().parent().hide()
                $("input#CART_RANGED_PRICE_MAX").parent().parent().hide()
                $("input#CART_RANGED_DISCOUNT_THIRD").parent().parent().hide()
            } else if (selectedOption.val() == 2){
                $("input#CART_RANGED_DISCOUNT").parent().parent().show()
                $("input#CART_RANGED_DISCOUNT_MAX").parent().parent().show()
                $("input#CART_RANGED_PRICE_MIN").parent().parent().show()
                $("input#CART_RANGED_PRICE_MAX").parent().parent().show()
                $("input#CART_RANGED_DISCOUNT_SECOND").parent().parent().show()


                $("input#CART_FIXED_DISCOUNT").parent().parent().hide()
                $("input#CART_FIXED_PRICE").parent().parent().hide()
                $("input#CART_RANGED_PRICE_SECOND").parent().parent().hide()
                $("input#CART_RANGED_DISCOUNT_THIRD").parent().parent().hide()

            } else if (selectedOption.val() == 3){
                $("input#CART_RANGED_DISCOUNT").parent().parent().show()
                $("input#CART_RANGED_DISCOUNT_MAX").parent().parent().show()
                $("input#CART_RANGED_PRICE_MIN").parent().parent().show()
                $("input#CART_RANGED_PRICE_MAX").parent().parent().show()
                $("input#CART_RANGED_DISCOUNT_SECOND").parent().parent().show()
                $("input#CART_RANGED_DISCOUNT_THIRD").parent().parent().show()
                $("input#CART_RANGED_PRICE_SECOND").parent().parent().show()

                $("input#CART_FIXED_DISCOUNT").parent().parent().hide()
                $("input#CART_FIXED_PRICE").parent().parent().hide()

            }

        })

        // $("select#CART_TYPE").val(selected)


    }
});