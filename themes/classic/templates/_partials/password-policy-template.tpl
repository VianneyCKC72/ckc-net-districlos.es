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

<template id="password-feedback">
  <div class="password-strength-feedback mt-2" style="display: none;">
    <div class="progress-container">
      <div class="progress mb-1">
        <div class="progress-bar" role="progressbar" value="50" aria-valuemin="0" aria-valuemax="100"></div>
      </div>
    </div>
    <script type="text/javascript" class="js-hint-password">
      {if !empty($page['password-policy']['feedbacks'])}
        {$page['password-policy']['feedbacks']|@json_encode nofilter}
      {/if}
    </script>

    <div class="password-strength-text"></div>
    <div class="password-requirements">
      <p class="password-requirements-length mb-0 small"
        data-translation="{l s='Enter a password between %s and %s characters' d='Shop.Theme.Customeraccount'}">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
          class="lucide lucide-check">
          <path d="M20 6 9 17l-5-5" />
        </svg>
        <span></span>
      </p>
      <p class="password-requirements-score mb-0 small"
        data-translation="{l s='The minimum score must be: %s' d='Shop.Theme.Customeraccount'}">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
          class="lucide lucide-check">
          <path d="M20 6 9 17l-5-5" />
        </svg>
        <span></span>
      </p>
    </div>
  </div>
</template>