{**
 * Creative Elements - live Theme & Page Builder
 *
 * @author    WebshopWorks
 * @copyright 2019-2024 WebshopWorks.com
 * @license   One domain support license
 *}
{if file_exists("{$smarty.const._PS_THEME_DIR_}templates/_partials/helpers.tpl")}
	{include '[1]_partials/helpers.tpl'}
{elseif $smarty.const._PARENT_THEME_NAME_ && file_exists("{$smarty.const._PS_PARENT_THEME_DIR_}templates/_partials/helpers.tpl")}
	{include 'parent:_partials/helpers.tpl'}
{/if}
<!doctype html>
<html lang="{$language.iso_code}">
<head>
	{block name='head'}
		{include file='_partials/head.tpl'}
	{/block}
</head>
<body id="{$page.page_name}" class="{$page.body_classes|classnames}  {if ($page.page_name == 'authentication' && $pro.env) || $pro.env} espace_pro{/if}
             {if $page.page_name == 'registration' && $pro.env} registration-pro{/if}
             {$pro.suffix}">

<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T3HJWLD" height="0" width="0"
				  style="display:none;visibility:hidden"></iframe></noscript>
{block name='hook_after_body_opening_tag'}
	{hook h='displayAfterBodyOpeningTag'}
{/block}
<main>
	{block name='product_activation'}
		{include file='catalog/_partials/product-activation.tpl'}
	{/block}
	<header id="header">
		{block name='header'}
			{include file="_partials/header{$pro.suffix}.tpl"}
		{/block}
	</header>
	{block name='notifications'}
		{include file='_partials/notifications.tpl'}
	{/block}

	{if $page.page_name == 'index' && !empty($pro) && $pro.env && !$pro.user.is_logged}
		{include file='module:ckc_espacepro/views/templates/hook/pro-home-login.tpl'
		login_action=$link->getModuleLink('ckc_espacepro','login')
		back=$urls.current_url}
		{hook h='CETemplate' id=37}
	{elseif $page.page_name == 'index' && !empty($pro) && $pro.env && $pro.user.is_logged}
		{hook h='CETemplate' id=36}
	{/if}

	{block name='content_wrapper'}
		{block name='content'}{/block}
	{/block}
	<footer id="footer">
		{block name='footer'}
			{include file="_partials/footer.tpl"}
		{/block}
	</footer>
</main>
{block name='javascript_bottom'}
	{if $smarty.const._PS_VERSION_|intval >= 8 && ('registration' === $page.page_name || 'password' === $page.page_name)}
		{include file='_partials/password-policy-template.tpl'}
	{/if}
	<script async type="text/javascript" src="/themes/classic/assets/js/lib/crypto-js.min.js"></script>
	<script async type="text/javascript" src="/themes/classic/assets/js/lib/jquery.inview.min.js"></script>
	<script async src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.5/dist/js.cookie.min.js"></script>
	<script async type="text/javascript" src="/themes/classic/assets/js/tags.js"></script>
	<script async type="module" src="/themes/classic/assets/js/lib/lite-vimeo.min.js"></script>
	{include file='_partials/javascript.tpl' javascript=$javascript.bottom}
{/block}
{block name='hook_before_body_closing_tag'}
	{hook h='displayBeforeBodyClosingTag'}
{/block}
</body>
</html>