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
{extends file='page.tpl'}

{block name='page_content'}
  {block name='register_form_container'}
    {$hook_create_account_top nofilter}
    <section class="register-form" id="registration-form">
      {* <p>{l s='Already have an account?' d='Shop.Theme.Customeraccount'} <a
          href="{$urls.pages.authentication}">{l s='Log in instead!' d='Shop.Theme.Customeraccount'}</a></p> *}

      <div class="already-account">
        <div class="img">
          <img src="https://picsum.photos/{rand(300, 600)}/{rand(300, 600)}" alt="" />
        </div>
        <div class="inside">
          <h2 class="mb-4">{l s='Already have an account?' d='Shop.Theme.Customeraccount'}</h2>
          <a href="{$urls.pages.authentication}" class="btn btn-secondary" id="inscription">
            {l s='Log in instead!' d='Shop.Theme.Customeraccount'}
          </a>
        </div>
      </div>

      <div class="inside-register-form">
        {render file='customer/_partials/customer-form.tpl' ui=$register_form}
      </div>
    </section>
  {/block}
{/block}