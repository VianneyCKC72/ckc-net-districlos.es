{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

<div id="ckcpromobarre" class="{$class} alert hide_alert alert-warning" role="alert" style="color:#ffffff; background-color: {if $env_pro == 1}#ffa400{else}{$CKC_BARREPROMO_COLOR}{/if};">
    {if $CKC_BARREPROMO_TEXTE AND $CKC_BARREPROMO_DISPLAY == 1}
        <span>{$CKC_BARREPROMO_TEXTE nofilter}</span>
    {if $CKC_BARREPROMO_TIMER_DISPLAY == 1}
        -
        {/if}
        <strong><span id="ckctimer" style="color: {$CKC_BARREPROMO_TIMER_COLOR} !important;"></span></strong>
        <button type="button" class="close close_once" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true" class="button_barrepromo"><span class="button_text_barrepromo">{l s='Hide' mod='ckcpromotionbanner'}</span> &times;</span>
        </button>
    {/if}
</div>

{*Push the timer *}
{if $CKC_BARREPROMO_TIMER_DISPLAY == 1}
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>

    <script type="text/javascript">
        jQuery( document ).ready(function($) {
            // Set the date we're counting down to
            var countDownDate = new Date("{$CKC_BARREPROMO_TIMER}").getTime();

            // Update the count down every 1 second
            var x = setInterval(function () {

                if ($('#ckcpromobarre').length == 0) {
                    clearInterval(x);
                    return;
                }

                // Get today's date and time
                var now = new Date().getTime();

                // Find the distance between now and the count down date
                var distance = countDownDate - now;

                // Time calculations for days, hours, minutes and seconds
                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // Output the result in an element with id="demo"
                document.getElementById("ckctimer").innerHTML = days + "d " + hours + "h "
                    + minutes + "m " + seconds + "s ";

                // If the count down is over, write some text
                if (distance < 0) {
                    clearInterval(x);
                    // document.getElementById("ckctimer").innerHTML = "la date limite est dépassée.";
                    $('#ckcpromobarre').hide();
                }
            }, 1000);
        });

    </script>
{/if}