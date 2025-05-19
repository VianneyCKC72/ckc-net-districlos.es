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
{foreach $javascript.external as $js}
  <script type="text/javascript" src="{$js.uri}" {$js.attribute}></script>
{/foreach}

{foreach $javascript.inline as $js}
  <script type="text/javascript">
    {$js.content nofilter}
  </script>
{/foreach}

{if isset($vars) && $vars|@count}
  <script type="text/javascript">
    {foreach from=$vars key=var_name item=var_value}
      var {$var_name} = {$var_value|json_encode nofilter};
    {/foreach}
  </script>
{/if}

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script type="module" src="/themes/classic/assets/js/lib/lite-vimeo.js"></script>


{*<div style="position: relative; width: 100%; padding-top: 56.25%;"><iframe width="300" height="150" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;" src="https://player.vimeo.com/video/1079777003?h=55cf8a4d10&amp;title=0&amp;byline=0&amp;portrait=0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen="allowfullscreen" title="Vidéo de présentation">      </iframe></div><div class="home-texte-customs-magasins"></div>*}

{*<div style="position: relative; width: 100%; padding-top: 56.25%;"><lite-vimeo videoid="1079777003" style="background-image: url('https://i.vimeocdn.com/video/1079777003.webp?mw=300&amp;mh=150&amp;q=70');"><div class="ltv-playbtn"></div></lite-vimeo></div><div class="home-texte-customs-magasins"></div>*}
