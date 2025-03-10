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
{block name='login_form'}

    {block name='login_form_errors'}
        {include file='_partials/form-errors.tpl' errors=$errors['']}
    {/block}
    <form id="login-form" action="{block name='login_form_actionurl'}{$action}{/block}" method="post">
        {block name='page_title'}
            {assign var="currentUrl" value="https://"|cat:$smarty.server.HTTP_HOST|cat:$smarty.server.REQUEST_URI}
        {/block}
        {if $espace_pro != 1 && !isset($show_title) || $show_title === false}
            <div class="not-account-yet">
                <div class="img">
                    <img src="https://picsum.photos/{rand(300, 600)}/{rand(300, 600)}" alt=""/>
                </div>
                <div class="inside">
                    <h2 class="mb-4">Pas encore de compte ?</h2>
                    <a href="{$urls.pages.register}" class="btn btn-secondary" id="inscription">
                        Créer un compte
                    </a>
                </div>
            </div>
        {/if}
        <div class="inside-login-form my-4">
            {if $espace_pro != 1 && !isset($show_title) || $show_title === false}
                {block name='page_title'}
                    <h1 class="h1 mb-4">Se connecter</h1>
                {/block}
            {/if}
            {block name='login_form_fields'}
                {foreach from=$formFields item="field"}
                    {block name='form_field'}
                        {form_field field=$field}
                    {/block}
                {/foreach}
            {/block}

            {if $espace_pro != 1}
            <div class="actions-container-login">
                <a href="{$urls.pages.my_account}" class="btn btn-primary" data-link-action="display-register-form">
                    {l s='Se connecter' d='Shop.Theme.Global'}
                </a>
                <div class="forgot-password">
                    <a href="{$urls.pages.password}" rel="nofollow">
                        {l s='Forgot your password?' d='Shop.Theme.Customeraccount'}
                    </a>
                </div>
                <span class="text-important"><strong>Ou bien...</strong></span>
            </div>
        </div>
        {/if}
        {if $espace_pro == 1}
            {include file="customer/_partials/_espacepro/login-form-espacepro.tpl"}
        {/if}
    </form>
{/block}