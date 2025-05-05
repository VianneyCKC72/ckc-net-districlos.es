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

<div id="ckcpromobarre" class="{$class} alert hide_alert alert-warning" role="alert" style="color:#ffffff; background-color: {if $env_pro == 1 OR Tools::getValue("ep") == 1}#ffa400{else}{$CKC_BARREPROMO_COLOR}{/if};">
    {if $CKC_BARREPROMO_DISPLAY == 1}
        <span>
            <div class="container">
                <div>
                    {if isset($ckcpromobarretexts) && !empty($ckcpromobarretexts)}
                        {if $ckcpromobarretexts|@count > 1}
                            <div class="ckcpromobarre-text-change-container">
                        {foreach $ckcpromobarretexts as $promotext}
                            <div class="ckcpromobarre-text" style="display: none;">
                                {$promotext nofilter}
                            </div>
                        {/foreach}
                        </div>
                        {elseif "" !== $ckcpromobarretexts}
                            {$ckcpromobarretexts[0] nofilter}
                        {/if}
                    {/if}
                    {if $CKC_BARREPROMO_TIMER_DISPLAY == 1 && isset($CKC_BARREPROMO_TIMER) && $CKC_BARREPROMO_TIMER !== ""}
                        -
                    {/if}
                    <strong><span id="ckctimer" style="color: {$CKC_BARREPROMO_TIMER_COLOR} !important;"></span></strong>
                </div>
                <div class="block-question"><img src="/img/cms/ico-question.png" width="22" height="20" class="ico-question" alt="des question?" />Des questions ?<a href="tel:0422531034"><strong>04 22 53 10 34</strong></a></div>
                <div class="clients">Nos clients sont satisfaits : <strong>9.5/10</strong><img class="ico-stars" src="/img/cms/ico-stars.png" alt="ico-stars.png" /></div>
            </div>
        </span>
        <button type="button" class="close close_once" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true" class="button_barrepromo"><span class="button_text_barrepromo">{l s='Hide' mod='ckcpromotionbanner'}</span> &times;</span>
        </button>
    {else}
        <span>
            <div class="container">
                <div class="ckcpromobarre-text-change-container">
                </div>
                <div class="block-question"><img src="/img/cms/ico-question.png" width="22" height="20" class="ico-question" alt="des question?" />Des questions ?<a href="tel:0422531034"><strong>04 22 53 10 34</strong></a></div>
                <div class="clients">Nos clients sont satisfaits : <strong>9.5/10</strong><img class="ico-stars" src="/img/cms/ico-stars.png" alt="ico-stars.png" /></div>
            </div>
        </span>
    {/if}
</div>

{*Push the timer *}
{if $CKC_BARREPROMO_TIMER_DISPLAY == 1 && isset($CKC_BARREPROMO_TIMER) && $CKC_BARREPROMO_TIMER !== ""}
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function () {
            // Récupère la date cible depuis la variable Smarty
            var countDownDate = new Date("{$CKC_BARREPROMO_TIMER}").getTime();

            // Met à jour le compte à rebours chaque seconde
            var x = setInterval(function () {
                // Vérifie si l'élément existe encore
                if (!document.getElementById("ckcpromobarre")) {
                    clearInterval(x);
                    return;
                }

                // Heure actuelle
                var now = new Date().getTime();

                // Calcul du temps restant
                var distance = countDownDate - now;

                // Conversion en jours, heures, minutes, secondes
                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // Affichage du compte à rebours
                var timerElement = document.getElementById("ckctimer");
                if (timerElement) {
                    timerElement.innerHTML = days + "d " + hours + "h " + minutes + "m " + seconds + "s ";
                }

                // Si le compte à rebours est terminé
                if (distance < 0) {
                    clearInterval(x);
                    var promoBar = document.getElementById("ckcpromobarre");
                    if (promoBar) {
                        var container = promoBar.querySelector(".ckcpromobarre-text-change-container");
                        if (container && container.parentElement) {
                            container.parentElement.style.display = "none";
                        }
                    }
                }
            }, 1000);
        });


    </script>
{/if}