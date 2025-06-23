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


{if !$logged || ($logged && $env_pro)}
    <a href="{$urls.pages.authentication}?back=my-account&amp;ep=1" class="ico btn-espace-pro">
        <img src="/themes/classic/assets/img/ico-espace-pro.svg" alt="Connexion à Espace pro" width="20" height="19">
        <span class="text">Espace pro</span>
    </a>
{/if}
<a href="/module/opartdevis/simplequotation" class="ico btn-devis">
    <img src="/themes/classic/assets/img/ico-devis.svg" alt="Page de de demande de Devis" width="21" height="21">
    <span class="text">Devis</span>
</a>
<a href="/content/11-nos-magasins-de-cloture" class="ico btn-magasin">
    <img src="/themes/classic/assets/img/ico-magasins.svg" alt="Consultez nos magasins" width="20" height="20">
    <span class="text">Magasins</span>
</a>

<div id="_desktop_user_info">
    <div class="user-info">
        {if $logged}
            {if $env_pro == false}
                <a
                        class="ico"
                        href="{$urls.pages.my_account}"
                        title="{l s='View my customer account' d='Shop.Theme.Customeraccount'}"
                        rel="nofollow"
                >
                    <img src="/themes/classic/assets/img/ico-compte.svg" alt="Connexion">
                    <span class="hidden-sm-down">Mon compte</span>
                </a>
            {/if}
        {elseif $page.page_name == 'registration' && Tools::getValue("ep") == 1}
            <a
                    href="{$urls.pages.authentication}?back=my-account&amp;ep=1"
                    title="{l s='Log in to your customer account' d='Shop.Theme.Customeraccount'}"
                    class="ico"
                    rel="nofollow"
            >
                <img src="/themes/classic/assets/img/ico-compte.svg" alt="Connexion">
                <span class="hidden-sm-down">{l s='Sign in' d='Shop.Theme.Actions'}</span>
            </a>
        {else}
            <a
                    href="{$urls.pages.authentication}?back={$urls.current_url|urlencode}"
                    title="{l s='Log in to your customer account' d='Shop.Theme.Customeraccount'}"
                    class="ico"
                    rel="nofollow"
            >
                <img src="/themes/classic/assets/img/ico-compte.svg" alt="Connexion">
                <span class="hidden-sm-down">{l s='Sign in' d='Shop.Theme.Actions'}</span>
            </a>
        {/if}
    </div>
</div>
