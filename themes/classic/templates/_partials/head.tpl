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
{block name='head_charset'}
  <meta charset="utf-8">
{/block}
{block name='head_ie_compatibility'}
  <meta http-equiv="x-ua-compatible" content="ie=edge">
{/block}

{block name='head_seo'}
  <title>{block name='head_seo_title'}{$page.meta.title}{/block}</title>

  {if isset($abtest_active) && $abtest_active && isset($abtest_script_url) && $abtest_script_url}
    <script src="{$abtest_script_url}"></script>
  {/if}

  {block name='hook_after_title_tag'}
    {hook h='displayAfterTitleTag'}
  {/block}

  {if $pro.env == 1}
    <meta name="robots" content="noindex, nofollow">

  {if $smarty.server.HTTP_HOST|lower == 'pro.cloture-discount.fr'}
    <link rel="canonical" href="https://www.cloture-discount.fr{$smarty.server.REQUEST_URI|escape:'html':'UTF-8'}" />
  {/if}

  {/if}
  <meta name="description" content="{block name='head_seo_description'}{$page.meta.description}{/block}">
  <meta name="keywords" content="{block name='head_seo_keywords'}{$page.meta.keywords}{/block}">
  <link rel="preconnect" href="https://f.vimeocdn.com">
  <link rel="preconnect" href="https://arclight.vimeo.com">
  <link rel="preconnect" href="https://i.vimeocdn.com">
  <link rel="preconnect" href="https://cdn.connectif.cloud">
  <link rel="preconnect" href="https://cdn.cloture-discount.fr">
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">
  <link rel="preconnect" href="https://static.axept.io">
  <link rel="preconnect" href="https://www.gstatic.com">
  <link rel="preconnect" href="https://bat.bing.com">
  <link rel="preconnect" href="https://fonts.gstatic.com">
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://www.googletagmanager.com">
  <link rel="preconnect" href="https://assets.motive.co">
  <link rel="preload" href="https://nouveau.cloture-discount.fr/img/cms/MOBILE%20MAI.webp" as="image">
  {* noindex EN tant que les traductions ne sont pas prêtes *}
  {if isset($page.meta.robots) && $page.meta.robots && $page.meta.robots != 'index'}
    <meta name="robots" content="{$page.meta.robots|escape:'html':'UTF-8'}">
  {/if}
  {if $page.canonical}
    <link rel="canonical" href="{$page.canonical}">
  {/if}
  {block name='head_hreflang'}
    {* Pas de hreflang EN pour l'instant (traductions en cours) *}
    {foreach from=$urls.alternative_langs item=pageUrl key=code}
      {if $code|substr:0:2 != 'en'}
        <link rel="alternate" href="{$pageUrl}" hreflang="{$code}">
      {/if}
    {/foreach}
  {/block}

  {block name='head_microdata'}
    {include file="_partials/microdata/head-jsonld.tpl"}
  {/block}

  {block name='head_microdata_special'}{/block}

  {block name='head_pagination_seo'}
    {include file="_partials/pagination-seo.tpl"}
  {/block}

  {block name='head_open_graph'}
    <meta property="og:title" content="{$page.meta.title}" />
    <meta property="og:description" content="{$page.meta.description}" />
    <meta property="og:url" content="{$urls.current_url}" />
    <meta property="og:site_name" content="{$shop.name}" />
    {if !isset($product) && $page.page_name != 'product'}
    <meta property="og:type" content="website" />{/if}
  {/block}
{/block}

{if $page.page_name == 'module-responsivegallery-galleryitems'}
  <meta name="robots" content="noindex, nofollow">
{/if}

{block name='head_viewport'}
  <meta name="viewport" content="width=device-width, initial-scale=1">
{/block}

<meta name="theme-color" content="#008397">
<meta name="msapplication-navbutton-color" content="#008397">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

{block name='head_icons'}
  <link rel="icon" type="image/vnd.microsoft.icon" href="{$shop.favicon}?{$shop.favicon_update_time}">
  <link rel="shortcut icon" type="image/x-icon" href="{$shop.favicon}?{$shop.favicon_update_time}">
{/block}

{block name='stylesheets'}
  {include file="_partials/stylesheets.tpl" stylesheets=$stylesheets}
{/block}

{block name='javascript_head'}
  {include file="_partials/javascript.tpl" javascript=$javascript.head vars=$js_custom_vars}
  <script defer charset="utf-8" src="https://widgets.rr.skeepers.io/product/084cf537-ed41-0a34-15e7-e45715e81af8/99f3523c-7bf8-4a88-8162-4b28805677e7.js"></script>

{if isset($show_emptycart) && $show_emptycart}
  <script>
    window.isAdminConnected = true;
  </script>
{else}
  <script>
    window.isAdminConnected = false;
  </script>
{/if}


{literal}
  <!-- Google Tag Manager -->
  <script>(function (w, d, s, l, i){
      w[l]=w[l]||[];w[l].push({
        'gtm.start':new Date().getTime(),event:'gtm.js'
      });
      var f=d.getElementsByTagName(s)[0], j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
      j.async=true;
      j.src= 'https://cdn.cloture-discount.fr/lj59djkyo5o1ip3.js?aw='+i.replace(/^GTM-/, '')+dl;
      f.parentNode.insertBefore(j,f);
    })(window, document, 'script', 'dataLayer', 'GTM-T3HJWLD');
  </script>

  <!-- End Google Tag Manager -->{/literal}

{/block}

{block name='hook_header'}
  {$HOOK_HEADER nofilter}
  {* CKC Vianney — Motive n'injecte le layer qu'en FR ; fallback EN si ckc_motive_i18n pas encore installé *}
  {if $language.iso_code == 'en' && !Module::isEnabled('ckc_motive_i18n')}
    {include file='_partials/motive-en.tpl'}
  {/if}
  {if Module::isEnabled('dealt')}
    {include file='_partials/dealt-zip-i18n.tpl'}
  {/if}
{/block}

{block name='hook_extra'}{/block}
