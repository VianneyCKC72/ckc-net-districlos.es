{*
* 2007-2021 ETS-Soft
*
* NOTICE OF LICENSE
*
* This file is not open source! Each license that you purchased is only available for 1 wesite only.
* If you want to use this file on more websites (or projects), you need to purchase additional licenses.
* You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs, please contact us for extra customization service at an affordable price
*
*  @author ETS-Soft <etssoft.jsc@gmail.com>
*  @copyright  2007-2021 ETS-Soft
*  @license    Valid for 1 website (or project) for each purchase of license
*  International Registered Trademark & Property of ETS-Soft
*}
{if isset($menus) && $menus}
{if isset($ckc_logo_src) && $ckc_logo_src}
    {assign var='ckc_mm_logo' value=$ckc_logo_src}
    {assign var='ckc_mm_logo_text' value=$ckc_logo_src}
    {assign var='ckc_mm_logo_white' value=$ckc_logo_src}
{else}
    {assign var='ckc_mm_logo' value='/img/logo-mobile.svg'}
    {assign var='ckc_mm_logo_text' value='/img/logo-1739869573.svg'}
    {assign var='ckc_mm_logo_white' value='/img/logo-text-white.svg'}
{/if}
 
    <ul class="mm_menus_ul {if isset($mm_config.ETS_MM_CLICK_TEXT_SHOW_SUB) && $mm_config.ETS_MM_CLICK_TEXT_SHOW_SUB} clicktext_show_submenu{/if} {if isset($mm_config.ETS_MM_SHOW_ICON_VERTICAL)&& !$mm_config.ETS_MM_SHOW_ICON_VERTICAL} hide_icon_vertical{/if}">
        <li class="mm-menu-section-top">
            <ul class="mm-menu-inner">
        <li class="close_menu">
            <a class="pull-left" href="{$urls.base_url}">
                {if Context::getContext()->shop->id == 2}
                    <img class="logo img-fluid" src="/img/logo-mobile-pro.svg" alt="Districlos" width="40" height="40" loading="lazy">
                {else}
                    <img class="logo img-fluid" src="{$ckc_mm_logo}" alt="Districlos" width="40" height="40" loading="lazy">
                {/if}
                <span class="txt-logo-mobile">Menu</span>
            </a>
            <div class="pull-right">
                <span class="mm_menus_back">
{*<svg xmlns="http://www.w3.org/2000/svg" width="16" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu"><line x1="4" x2="20"
                                                                                                      y1="12" y2="12"/><line
            x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>*}
                    <svg class="Header__toggle-svg hamburger add-favori" viewBox="0 0 60 40">
                        <g stroke="#000" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                            <path class="top-line" d="M10,10 L50,10 Z"></path>
                            <path class="middle-line" d="M10,20 L50,20 Z"></path>
                            <path class="bottom-line" d="M10,30 L50,30 Z"></path>
                        </g>
                    </svg>
                </span>
            </div>
        </li>
        {foreach from=$menus item='menu'}
            <li style="border-right: none !important;" class="mobile_noclic closed mm_menus_li{if $menu.enabled_vertical} mm_menus_li_tab{if $menu.menu_ver_hidden_border} mm_no_border{/if}{if $menu.menu_ver_alway_show} menu_ver_alway_show_sub{/if}{/if}{if $menu.custom_class} {$menu.custom_class|escape:'html':'UTF-8'}{/if}{if $menu.sub_menu_type} mm_sub_align_{strtolower($menu.sub_menu_type)|escape:'html':'UTF-8'}{/if}{if $menu.columns} mm_has_sub{/if}{if $menu.display_tabs_in_full_width && $menu.enabled_vertical} display_tabs_in_full_width{/if}"
                {if $menu.enabled_vertical}style="width: {if $menu.menu_item_width}{$menu.menu_item_width|escape:'html':'UTF-8'}{else}{*230px*}auto;{/if}"{/if}>
                <div class="d_flex item_first_layer mm_has_sub">
                    <a {if isset($menu.menu_open_new_tab) && $menu.menu_open_new_tab == 1} target="_blank"{/if}
                            href="{$menu.menu_link|escape:'html':'UTF-8'}"
                            style="{if $menu.enabled_vertical}{if isset($menu.menu_ver_text_color) && $menu.menu_ver_text_color}color:;{/if}{if isset($menu.menu_ver_background_color) && $menu.menu_ver_background_color}background-color:{$menu.menu_ver_background_color|escape:'html':'UTF-8'};{/if}{/if}{if Configuration::get('ETS_MM_HEADING_FONT_SIZE')}font-size:{Configuration::get('ETS_MM_HEADING_FONT_SIZE')|intval}px;{/if}">

                    <span class="mm_menu_content_title">
                        <div>
                            {if $menu.menu_img_link}
                                {if $menu.menu_link|strstr:'/4-'}
                                    <img src="{$menu.menu_img_link|escape:'html':'UTF-8'}" class="img-menu-desktop-custom" style="padding: 5px;" title="" alt="" width="20" loading="lazy"/>
                                {else}
                                    <img src="{$menu.menu_img_link|escape:'html':'UTF-8'}" class="img-menu-desktop-custom" title="" alt="" width="20" loading="lazy"/>
                                {/if}
    {elseif $menu.menu_icon}
                                    <i class="fa {$menu.menu_icon|escape:'html':'UTF-8'}"></i>

                            {/if}
                        </div>
                        <div>
                            {$menu.title|escape:'html':'UTF-8'}
                        </div>
                        {if $menu.columns}<span class="mm_arrow"></span>{/if}
                        {if $menu.bubble_text}<span class="mm_bubble_text"
                                                    style="background: {if $menu.bubble_background_color}{$menu.bubble_background_color|escape:'html':'UTF-8'}{else}#FC4444{/if}; color: {if $menu.bubble_text_color|escape:'html':'UTF-8'}{$menu.bubble_text_color|escape:'html':'UTF-8'}{else}#ffffff{/if};">{$menu.bubble_text|escape:'html':'UTF-8'}</span>{/if}
                    </span>
                    </a>
                    {if $menu.enabled_vertical}
                        {if $menu.tabs}
                            <span class="arrow closed YES niveau1"
                                  style="display: flex;align-items: center;justify-content: flex-end;padding-right: 2rem;">
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-right"><path
            d="M18 8L22 12L18 16"/><path d="M2 12H22"/></svg>
                    </span>
                        {/if}
                    {/if}
                </div>
                {if $menu.enabled_vertical}
                    {if $menu.tabs}
                        <ul class="mm_columns_ul mm_columns_ul_tab {if $menu.menu_ver_alway_show} mm_columns_ul_tab_content{/if}"
                            style="width:{$menu.sub_menu_max_width|escape:'html':'UTF-8'};{if Configuration::get('ETS_MM_TEXT_FONT_SIZE')} font-size:{Configuration::get('ETS_MM_TEXT_FONT_SIZE')|intval}px;{/if}">
                            <li class="close_menu close-menu-sub">
                                <div class="pull-left">
                                    <a href="{$urls.base_url}" class="logo-link-menu">
                                        {if Context::getContext()->shop->id == 2}
                                            <img class="logo img-fluid" src="/img/DISTRICLOS_pro_logo.png" alt="Districlos" width="178" height="48" loading="lazy">
                                        {else}
                                            <img class="logo img-fluid" src="{$ckc_mm_logo}" alt="Districlos" width="40" height="40" loading="lazy">
                                        {/if}
                                        <span class="txt-logo-mobile">Menu</span>
                                    </a>
                                </div>
                                <div class="pull-right">
                                    <span class="mm_menus_back">
                                        <svg class="Header__toggle-svg hamburger add-favori" viewBox="0 0 60 40">
                                            <g stroke="#000" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                                                <path class="top-line" d="M10,10 L50,10 Z"></path>
                                                <path class="middle-line" d="M10,20 L50,20 Z"></path>
                                                <path class="bottom-line" d="M10,30 L50,30 Z"></path>
                                            </g>
                                        </svg>
                                    </span>
                                </div>
                            </li>
                            <li class="menu-arrow-back-container">
                                <svg class="menu-arrow-back" width="26" height="12" viewBox="0 0 26 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M25 6L1 6M1 6L6 1M1 6L6 11" stroke="#0D0D0D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </li>
                            <li class="mm_tabs_li mm_tabs_has_content back_menu_parent_li back_first">
                                <span class="back_menu_parent">{$menu.title|escape:'html':'UTF-8'}</span>
                            </li>
                            {foreach from=$menu.tabs key='key' item='tab'}
                                <li class="mm_tabs_li{if $tab.columns} {if $key == 0 && isset($menu.menu_ver_alway_open_first) && $menu.menu_ver_alway_open_first}open {/if}mm_tabs_has_content {/if}{if !$tab.tab_sub_content_pos}mm_tab_content_hoz {/if}{if isset($menu.menu_ver_alway_open_first) && $menu.menu_ver_alway_open_first && $menu.menu_ver_alway_show}open_first {/if}{if !$menu.menu_ver_alway_show}ver_alway_hide{/if}">

                                    <div class="distri_vertical_menu-flex">

                                        <div class="mm_tab_li_content closed"
                                             style="width: {if $menu.tab_item_width}{$menu.tab_item_width|escape:'html':'UTF-8'}{else}230px{/if}">
                                        <span class="mm_tab_name mm_tab_toggle{if $tab.columns} mm_tab_has_child{/if}">
                                            <span class="mm_tab_toggle_title">
                                                {if $tab.url}
                                                    <a class="mm-link-menu-sub" href="{$tab.url|escape:'html':'UTF-8'}">
                                                {/if}
                                                        {if $tab.tab_img_link}
                                                            <img src="{$tab.tab_img_link|escape:'html':'UTF-8'}"
                                                                 title="" alt="" width="20"/>
                                                            {elseif $tab.tab_icon}
                                                            <i class="fa {$tab.tab_icon|escape:'html':'UTF-8'}"></i>
                                                        {/if}
                                                        <div class="mm_tab_title_wrapper" style="display: flex;flex-direction: column-reverse;align-items: center;">
                                                            {$tab.title|escape:'html':'UTF-8'}
                                                            {if $tab.bubble_text}<span class="mm_bubble_text"style="background: {if $tab.bubble_background_color}{$tab.bubble_background_color|escape:'html':'UTF-8'}{else}#FC4444{/if}; color: {if $tab.bubble_text_color|escape:'html':'UTF-8'}{$tab.bubble_text_color|escape:'html':'UTF-8'}{else}#ffffff{/if};">{$tab.bubble_text|escape:'html':'UTF-8'}</span>{/if}
                                                        </div>
                                                        {if $tab.url}
                                                    </a>
                                                     <span class="arrow YES closed niveau2">
                                                       <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                            viewBox="0 0 24 24" fill="none" stroke="white"
                                                            stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round" class="lucide lucide-move-right">
                                                          <path d="M18 8L22 12L18 16"/>
                                                          <path d="M2 12H22"/>
                                                        </svg>
                                                     </span>
                                                {/if}
                                            </span>
                                        </span>
                                        </div>
                                        {if $tab.columns}
                                            <ul class="mm_columns_contents_ul ">
                                                <li class="close_menu close-menu-sub">
                                                    <div class="pull-left">
                                                        <a href="{$urls.base_url}" class="logo-link-menu">
                                                            {if Context::getContext()->shop->id == 2}
                                                                <img class="logo img-fluid" src="/img/DISTRICLOS_pro_logo.png" alt="Districlos" width="178" height="48" loading="lazy">
                                                            {else}
                                                                <img class="logo img-fluid" src="{$ckc_mm_logo}" alt="Districlos" width="40" height="40" loading="lazy">
                                                            {/if}
                                                            <span class="txt-logo-mobile">Menu</span>
                                                        </a>
                                                    </div>
                                                    <div class="pull-right">
                                                        <span class="mm_menus_back">
                                                            <svg class="Header__toggle-svg hamburger add-favori" viewBox="0 0 60 40">
                                                                <g stroke="#000" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path class="top-line" d="M10,10 L50,10 Z"></path>
                                                                    <path class="middle-line" d="M10,20 L50,20 Z"></path>
                                                                    <path class="bottom-line" d="M10,30 L50,30 Z"></path>
                                                                </g>
                                                            </svg>
                                                        </span>
                                                    </div>
                                                </li>
                                                <li class="menu-arrow-back-container">
                                                    <svg class="menu-arrow-back" width="26" height="12" viewBox="0 0 26 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M25 6L1 6M1 6L6 1M1 6L6 11" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </li>
                                                <li class="mm_columns_li column_size_3 back_menu_parent_li">
                                                    <span class="back_menu_parent">{$tab.title|escape:'html':'UTF-8'}</span>
                                                </li>
                                                {foreach from=$tab.columns item='column'}
                                                    {if isset($column.blocks) && $column.blocks}
                                                        <li class="mm_columns_li column_size_{$column.column_size|intval} {if $column.is_breaker}mm_breaker{/if} {if $column.blocks}mm_has_sub{/if}">
                                                            <ul class="mm_blocks_ul">
                                                                {foreach from=$column.blocks item='block'}
                                                                    <li data-id-block="{$block.id_block|intval}"
                                                                        class="mm_blocks_li">
                                                                        {hook h='displayBlock' block=$block}
                                                                    </li>
                                                                {/foreach}
                                                            </ul>
                                                        </li>
                                                    {/if}
                                                {/foreach}
                                                <a class="pull-left logo-menu-mobile-sub-cat" href="{$urls.base_url}">
                                                    {if Context::getContext()->shop->id == 2}
                                                        <img class="logo img-fluid" src="/img/DISTRICLOS_pro_logo_mobile.png" alt="Districlos" width="178" height="48" loading="lazy">>
                                                    {else}
                                                        <img class="logo img-fluid" src="{$ckc_mm_logo_white}" alt="Districlos" width="178" height="48" loading="lazy">
                                                    {/if}
                                                </a>
                                            </ul>
                                        {/if}

                                    </div>

                                </li>
                            {/foreach}
                            <a class="pull-left logo-menu-mobile-sub-cat" href="{$urls.base_url}">
                                {if Context::getContext()->shop->id == 2}
                                    <img class="logo img-fluid" src="/img/DISTRICLOS_pro_logo.png" alt="Districlos" width="178" height="48" loading="lazy">
                                {else}
                                    <img class="logo img-fluid" src="{$ckc_mm_logo_text}" alt="Districlos" width="178" height="48" loading="lazy">
                                {/if}
                            </a>
                        </ul>
                    {/if}

                    {if $menu.columns}<span class="arrow closed niveau3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-left"><path d="M6 8L2 12L6 16"/><path d="M2 12H22"/></svg>
                    </span>{/if}
                    {if $menu.columns}
                        <ul class="mm_columns_ul"
                            style=" width:{$menu.sub_menu_max_width|escape:'html':'UTF-8'};{if Configuration::get('ETS_MM_TEXT_FONT_SIZE')} font-size:{Configuration::get('ETS_MM_TEXT_FONT_SIZE')|intval}px;{/if}{if !$menu.enabled_vertical && $menu.background_image} background-image:url('{$menu.background_image|escape:'html':'UTF-8'}');background-position:{$menu.position_background|escape:'html':'UTF-8'}{/if}">
                            <li>
                                <ul>
                                    {foreach from=$menu.columns item='column'}
                                        <li class="mm_columns_li column_size_{$column.column_size|intval} {if $column.is_breaker}mm_breaker{/if} {if $column.blocks}mm_has_sub{/if}">
                                            {if isset($column.blocks) && $column.blocks}
                                                <ul class="mm_blocks_ul">
                                                    {foreach from=$column.blocks item='block'}
                                                        <li data-id-block="{$block.id_block|intval}"
                                                            class="mm_blocks_li">
                                                            {hook h='displayBlock' block=$block}
                                                        </li>
                                                    {/foreach}
                                                </ul>
                                            {/if}
                                        </li>
                                    {/foreach}
                            </li>
                        </ul>
                    {/if}
                {/if}
            </li>
        {/foreach}
        {foreach from=$menus item='menu'}
            <li class="mobile_noclic closed mm_menus_li{if $menu.enabled_vertical} mm_menus_li_tab{if $menu.menu_ver_hidden_border} mm_no_border{/if}{if $menu.menu_ver_alway_show} menu_ver_alway_show_sub{/if}{/if}{if $menu.custom_class} {$menu.custom_class|escape:'html':'UTF-8'}{/if}{if $menu.sub_menu_type} mm_sub_align_{strtolower($menu.sub_menu_type)|escape:'html':'UTF-8'}{/if}{if $menu.columns} mm_has_sub{/if}{if $menu.display_tabs_in_full_width && $menu.enabled_vertical} display_tabs_in_full_width{/if} menu_item_mobile"
                {if $menu.enabled_vertical}style="width: {if $menu.menu_item_width}{$menu.menu_item_width|escape:'html':'UTF-8'}{else}{*230px*}auto;{/if}"{/if}>
                <div class="d_flex item_first_layer {if $menu.columns} mm_has_sub{/if}">
                    <a {if isset($menu.menu_open_new_tab) && $menu.menu_open_new_tab == 1} target="_blank"{/if}
                            href="{$menu.menu_link|escape:'html':'UTF-8'}"
                            style="{if $menu.enabled_vertical}{if isset($menu.menu_ver_text_color) && $menu.menu_ver_text_color}color:;{/if}{if isset($menu.menu_ver_background_color) && $menu.menu_ver_background_color}background-color:{$menu.menu_ver_background_color|escape:'html':'UTF-8'};{/if}{/if}{if Configuration::get('ETS_MM_HEADING_FONT_SIZE')}font-size:{Configuration::get('ETS_MM_HEADING_FONT_SIZE')|intval}px;{/if}">
                    <span class="mm_menu_content_title">
                        <div>
                            {if $menu.menu_img_link}
                                <img src="{$menu.menu_img_link|escape:'html':'UTF-8'}" class="img-menu-desktop-custom" title="" alt="" width="20" loading="lazy"/>


{elseif $menu.menu_icon}


                                <i class="fa {$menu.menu_icon|escape:'html':'UTF-8'}"></i>
                            {/if}
                        </div>
                        <div>
                            {$menu.title|escape:'html':'UTF-8'}
                        </div>
                    </span>
                    </a>
                </div>
            </li>
        {/foreach}
        {if $pro.env != 1}
            {* Campagne : remplace "Bons plans" en mobile *}
            <li class="info-menu-mobile-only mm_menus_li distri_megamenu mm_sub_align_full menu-mobile-promo-banner-li">
                <div class="menu-mobile-promo-banner">
                    <a href="{$link->getCMSLink(676)|escape:'html':'UTF-8'}" class="menu-mobile-promo-banner__link">
                        <span class="menu-mobile-promo-banner__text">
                            <span class="menu-mobile-promo-banner__title">{l s='PROJET EN FÊTE' d='Shop.Theme.Custommodules'}</span>
                            <span class="menu-mobile-promo-banner__subtitle">{l s='sur une sélection de produits' d='Shop.Theme.Custommodules'}</span>
                        </span>
                        <span class="menu-mobile-promo-banner__cta">{l s='Voir nos bons plans' d='Shop.Theme.Custommodules'}</span>
                    </a>
                </div>
            </li>
            {* Ancien lien "Bons plans" mobile — à réactiver après la campagne
            <li class="info-menu-mobile-only mm_menus_li distri_megamenu mm_sub_align_full"><a href="{$link->getCategoryLink(400)|escape:'html':'UTF-8'}">
                    <span class="mm_menu_content_title"><svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" fill="currentColor" viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>{l s='Bons plans' d='Shop.Theme.Customglobal'}</span>
                </a></li>
            *}
        {else}
            <li class="info-menu-mobile-only mm_menus_li distri_megamenu mm_sub_align_full"><a href="{$link->getCMSLink(660)|escape:'html':'UTF-8'}">
                    <span class="mm_menu_content_title">{*<svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" fill="currentColor" viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>*}{l s='Les offres au volume' d='Shop.Theme.Customglobal'}</span>
                </a></li>
        {/if}
        <li class="menu-mobile-custom-container-links mm_menus_li distri_megamenu mm_sub_align_full info_menu_mobile">

            {if Context::getContext()->shop->id == 2}
                <a href="{$link->getModuleLink('ckc_espacepro', 'benefitsespacepro')|escape:'html':'UTF-8'}" class="btn btn-primary btn-avantages-mobile">
                    <div class="text-btn">
                        <span class="medium-text">{l s='Mes avantages' d='Shop.Theme.Customglobal'}</span>
                    </div>
                </a>
            {else}
                <a href="{$link->getModuleLink('ckcconfigurateurdistriclos', 'configurator')|escape:'htmlall':'UTF-8'}" class="btn-configurateur-mobile">
                <img width="45px" height="45px" alt="{l s='Configurez votre clôture' d='Shop.Theme.Customglobal'}" src="themes/classic/assets/img/configurer-icon.svg">
                <div class="text-btn">
                    <span class="medium-text">{l s='Configurez votre clôture' d='Shop.Theme.Customglobal'}</span>
                </div>
                </a>
            {/if}
        </li>
            </ul>
        </li>
        <li class="mm-menu-section-bottom">
            <ul class="mm-menu-inner">
        <li class="menu-mobile-custom-container-links mm_menus_li distri_megamenu mm_sub_align_full info_menu_mobile">
            <div style="display: flex; flex-direction: column; gap: 30px;">
                <div class="menu-mobile-custom-useful-links">
                    {if isset($pro.env) && $pro.env == 1}
                        <a class="menu-mobile-useful-link menu-mobile-particuliers" href="{Context::getContext()->link->getBaseLink(1)}" rel="nofollow">
                            <svg width="22" height="20" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3.83347 0C2.59109 0 1.49585 0.840662 1.17564 2.04102L0.0748041 6.16992C-0.233113 7.32435 0.442452 8.4839 1.49798 8.86719V15.75C1.49798 16.9838 2.51446 18 3.74849 18H8.74962C8.94857 18 9.13937 17.921 9.28005 17.7803C9.42073 17.6397 9.49977 17.4489 9.49979 17.25V12.9648C9.49979 12.4027 9.93775 11.9648 10.5 11.9648C11.0623 11.9648 11.5002 12.4027 11.5002 12.9648V17.25C11.5003 17.4489 11.5793 17.6397 11.72 17.7803C11.8607 17.921 12.0515 18 12.2504 18H17.2515C18.4856 18 19.5021 16.9838 19.5021 15.75V8.86621C20.5573 8.48279 21.2331 7.32422 20.9252 6.16992L19.8244 2.04102C19.5042 0.840662 18.4089 0 17.1666 0H3.83347ZM3.83347 1.5H17.1666C17.7366 1.5 18.228 1.87709 18.3748 2.42773L19.4757 6.55664C19.6076 7.0512 19.2619 7.5 18.7509 7.5H14.7744C14.3719 7.5 13.9961 7.30743 13.7605 6.98145L11.6946 4.12207C11.119 3.32649 9.88058 3.32578 9.30541 4.12207L7.24049 6.98145C7.00513 7.30718 6.62841 7.5 6.22659 7.5H2.24912C1.73738 7.5 1.39244 7.0512 1.52435 6.55664L2.62519 2.42773C2.77208 1.87709 3.26343 1.5 3.83347 1.5ZM10.5 5.03125L12.5444 7.86035C13.0611 8.57536 13.8916 9 14.7744 9H18.0017V15.75C18.0017 16.1733 17.6749 16.5 17.2515 16.5H13.0006V12.9648C13.0006 11.593 11.8722 10.4648 10.5 10.4648C9.12784 10.4648 7.99945 11.593 7.99945 12.9648V16.5H3.74849C3.32514 16.5 2.99832 16.1733 2.99832 15.75V9H6.22659C7.10905 9 7.93977 8.57562 8.45658 7.86035L10.5 5.03125ZM5.49888 11C4.94626 11 4.49866 11.4475 4.49866 12V13C4.49866 13.5525 4.94626 14 5.49888 14C6.05151 14 6.49911 13.5525 6.49911 13V12C6.49911 11.4475 6.05151 11 5.49888 11ZM15.5011 11C14.9485 11 14.5009 11.4475 14.5009 12V13C14.5009 13.5525 14.9485 14 15.5011 14C16.0538 14 16.5014 13.5525 16.5014 13V12C16.5014 11.4475 16.0538 11 15.5011 11Z" fill="currentColor"/>
                            </svg>
                            <p>{l s='Particuliers' d='Shop.Theme.Custommodules'}</p>
                        </a>
                    {elseif $pro.env != 1}
                        <a class="menu-mobile-useful-link" href="{$pro.url_domain}">
                            <img src="/img/menu-mobile-espace-pro.svg" alt="{l s='Espace Pro' d='Shop.Theme.Custommodules'}" width="19px" height="18px">
                            <p>{l s='Espace Pro' d='Shop.Theme.Custommodules'}</p>
                        </a>
                    {/if}
                    <a class="menu-mobile-useful-link" href="{$link->getModuleLink('opartdevis', 'simplequotation')|escape:'html':'UTF-8'}">
                        <img src="/img/menu-mobile-devis.svg" alt="{l s='Devis' d='Shop.Theme.Custommodules'}" width="20px" height="19px">
                        <p>{l s='Devis' d='Shop.Theme.Custommodules'}</p>
                    </a>
                    <a class="menu-mobile-useful-link" href="{$link->getCMSLink(11)|escape:'html':'UTF-8'}">
                        <img src="/img/menu-mobile-magasins.svg" alt="{l s='Magasins' d='Shop.Theme.Custommodules'}" width="19px" height="19px">
                        <p>{l s='Magasins' d='Shop.Theme.Custommodules'}</p>
                    </a>
                    <a class="menu-mobile-useful-link" href="{$link->getCMSLink(657)|escape:'html':'UTF-8'}">
                        <img src="themes/classic/assets/img/service-de-pose.svg" alt="{l s='Service de pose' d='Shop.Theme.Customglobal'}" width="19px" height="19px">
                        <p style="color: #026473;">{l s='Service de pose' d='Shop.Theme.Customglobal'}</p>
                    </a>
                    <a class="menu-mobile-useful-link" href="{$urls.pages.authentication}" onclick="window.location.href='{$urls.pages.authentication}?back={$urls.current_url|urlencode}'">
                        <img src="/img/menu-mobile-compte.svg" alt="{l s='Mon compte' d='Shop.Theme.Custommodules'}" width="16px" height="19px">
                        <p>{l s='Mon compte' d='Shop.Theme.Custommodules'}</p>
                    </a>
                </div>

                <a href="tel:+33422531034" class="menu-mobile-phone-contact">
                    <img loading="lazy" src="/img/menu-mobile-phone.svg" alt="{l s='Des questions ?' d='Shop.Theme.Customglobal'}" width="27px" height="25px">
                    <span class="menu-mobile-phone-text">{l s='Des questions ?' d='Shop.Theme.Customglobal'}&nbsp;</span>
                    <span class="menu-mobile-phone-number">04 22 53 10 34</span>
                </a>
                <div class="menu-mobile-stars-container">
                    {hook h='displaySkeepersRating' mod='dtc_skeepers'}
                </div>
            </div>
        </li>
        {* Suppression de l'élément "Soldes" qui apparaît lors du scroll sur desktop *}
        {*
        {if $pro.env != 1}
            <li class="info-menu-mobile-only info-menu-link-on-scroll mm_menus_li distri_megamenu mm_sub_align_full"><a href="/400-bons-plans">
                    <span class="mm_menu_content_title"><svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>Bon plans</span>
                </a></li>
        {/if}
        *}
        <li class="mm_menus_li distri_megamenu mm_sub_align_full info_menu_mobile menu-mobile-promo-banner-li menu-mobile-promo-banner-li--desktop">
            <div class="menu-mobile-promo-banner">
                <a href="{$link->getCMSLink(676)|escape:'html':'UTF-8'}" class="menu-mobile-promo-banner__link">
                    <span class="menu-mobile-promo-banner__text">
                        <span class="menu-mobile-promo-banner__title">{l s='PROJET EN FÊTE' d='Shop.Theme.Custommodules'}</span>
                        <span class="menu-mobile-promo-banner__subtitle">{l s='sur une sélection de produits' d='Shop.Theme.Custommodules'}</span>
                    </span>
                    <span class="menu-mobile-promo-banner__cta">{l s='Voir nos bons plans' d='Shop.Theme.Custommodules'}</span>
                </a>
            </div>
        </li>
        {* Image promo campagne (hook displayPromoMenuImage) — à réactiver après la campagne
        <li class="mm_menus_li distri_megamenu mm_sub_align_full info_menu_mobile">
            <div class="hide_pro image-fw-mm">
                {hook h='displayPromoMenuImage'}
            </div>
        </li>
        *}
            </ul>
        </li>
    </ul>
    {hook h='displayCustomMenu'}
{/if}
