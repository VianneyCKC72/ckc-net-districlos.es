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

<div id="ckcpromobarre" class="{$class} alert alert-warning" role="alert" style="color:#ffffff; background-color: {if $env_pro == 1 OR Tools::getValue("ep") == 1}#ffa400{else}{$CKC_BARREPROMO_COLOR}{/if};">
    {if $CKC_BARREPROMO_DISPLAY == 1}
        <span>
            <div class="container">
                <div>
                    {* START BANDEAU SOLDES - bouton Bons plans temporairement masqué
                    {if Context::getContext()->shop->id == 1}
                        <div class="displayMenu">
                        <a class="link-danger soldes" href="/400-destockage">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bell-fill" viewBox="0 0 16 16">
                                <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2m.995-14.901a1 1 0 1 0-1.99 0A5 5 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901"></path>
                            </svg>
                            {l s='Top deals' mod='ckcpromotionbanner'}
                        </a>
                        </div>
                    {/if}
                    END BANDEAU SOLDES - bouton Bons plans *}
                    {if $env_pro == 1 OR Tools::getValue("ep") == 1}
                        {if isset($ckcpromobarretextsPro) && !empty($ckcpromobarretextsPro)}
                            {if $ckcpromobarretextsPro|@count > 1}
                                <div class="ckcpromobarre-text-change-container">
                            {foreach $ckcpromobarretextsPro as $promotextPro}
                                <div class="ckcpromobarre-text" style="display: none;">
                                    {$promotextPro nofilter}
                                </div>
                            {/foreach}
                            </div>
                            {elseif "" !== $ckcpromobarretextsPro}
                                {$ckcpromobarretextsPro[0] nofilter}
                            {/if}
                        {/if}
                    {else}
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
                    {/if}
                    {if $CKC_BARREPROMO_TIMER_DISPLAY == 1 && isset($CKC_BARREPROMO_TIMER) && $CKC_BARREPROMO_TIMER !== ""}
                        -
                    {/if}
                    <strong><span id="ckctimer" style="color: {$CKC_BARREPROMO_TIMER_COLOR} !important;"></span></strong>
                </div>
                <div class="block-question"><img src="/img/cms/ico-question.png" width="22" height="20" class="ico-question" alt="{l s='Questions?' mod='ckcpromotionbanner'}" />{l s='Questions?' mod='ckcpromotionbanner'}<a href="tel:0422531034"><strong>04 22 53 10 34</strong></a></div>
                <div class="clients">
                    {hook h='displaySkeepersRating' mod='dtc_skeepers'}
                </div>
            </div>
        </span>
        {*        <button type="button" class="close close_once" data-dismiss="alert" aria-label="Close">*}
        {*            <span aria-hidden="true" class="button_barrepromo"><span class="button_text_barrepromo">{l s='Hide' mod='ckcpromotionbanner'}</span> &times;</span>*}
        {*        </button>*}
    {else}
        <span>
            <div class="container">
                <div class="ckcpromobarre-text-change-container">
                </div>
                <div class="block-question"><img src="/img/cms/ico-question.png" width="22" height="20" class="ico-question" alt="{l s='Questions?' mod='ckcpromotionbanner'}" />{l s='Questions?' mod='ckcpromotionbanner'}<a href="tel:0422531034"><strong>04 22 53 10 34</strong></a></div>
                <div class="clients">
                    {hook h='displaySkeepersRating' mod='dtc_skeepers'}
                </div>
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

            // Libellés localisés des unités du compte à rebours
            var ckcpromobarreLabels = {
                days:    "{l s='d' mod='ckcpromotionbanner' js=1}",
                hours:   "{l s='h' mod='ckcpromotionbanner' js=1}",
                minutes: "{l s='m' mod='ckcpromotionbanner' js=1}",
                seconds: "{l s='s' mod='ckcpromotionbanner' js=1}"
            };

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
                    timerElement.innerHTML = days + ckcpromobarreLabels.days + " "
                        + hours + ckcpromobarreLabels.hours + " "
                        + minutes + ckcpromobarreLabels.minutes + " "
                        + seconds + ckcpromobarreLabels.seconds + " ";
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
