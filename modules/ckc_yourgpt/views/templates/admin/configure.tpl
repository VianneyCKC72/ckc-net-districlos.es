{*
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
	<h3><i class="icon icon-credit-card"></i> {l s='Configure Your GPT' mod='ckc_yourgpt'}</h3>
	<p>
		{l s='You can configure it using the following configuration form.' mod='ckc_yourgpt'}
	</p>
	<br />
	<p>
		{l s='This module will boost your sales!' mod='ckc_yourgpt'}
	</p>
</div>
<div class="ckc_yourgpt_error_block" >
		{if isset($error_starting_tracking_hour) && $error_starting_tracking_hour}<div class="ckc_yourgpt_alert">{l s="Enter a valid starting hour" mod='ckc_yourgpt'}</div>{/if}
		{if isset($error_last_tracking_hour) && $error_last_tracking_hour}<div class="ckc_yourgpt_alert">{l s="Enter a valid last hour" mod='ckc_yourgpt'}</div>{/if}
		{if isset($error_diff_tracking_hours) && $error_last_tracking_hour}<div class="ckc_yourgpt_alert">{l s="Enter valid tracking hours: last hour time cannot be less or equal starting hour time." mod='ckc_yourgpt'}</div>{/if}
</div>

