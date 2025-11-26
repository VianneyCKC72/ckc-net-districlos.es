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
    {*    <div class="ybc-menu-toggle ybc-menu-btn closed">*}
    {*        <div class="mm_modal_filter"></div>*}
    {*        <span class="ybc-menu-button-toggle_icon">*}
    {*                            <svg class="Header__toggle-svg hamburger" viewbox="0 0 60 40">*}
    {*                                <g stroke="#000" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">*}
    {*                                    <path class="top-line" d="M10,10 L50,10 Z"></path>*}
    {*                                    <path class="middle-line" d="M10,20 L50,20 Z"></path>*}
    {*                                    <path class="bottom-line" d="M10,30 L50,30 Z"></path>*}
    {*                                </g>*}
    {*                            </svg>*}
    {*                            <svg class="Header__toggle-svg hamburger_sticky" viewbox="0 0 60 40">*}
    {*                                <g stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">*}
    {*                                    <path class="top-line" d="M10,10 L50,10 Z"></path>*}
    {*                                    <path class="middle-line" d="M10,20 L50,20 Z"></path>*}
    {*                                    <path class="bottom-line" d="M10,30 L50,30 Z"></path>*}
    {*                                </g>*}
    {*                            </svg>*}
    {*                        </span>*}
    {*        <span class="main_menu_label">Nos produits</span>*}
    {*    </div>*}

    <ul class="mm_menus_ul {if isset($mm_config.ETS_MM_CLICK_TEXT_SHOW_SUB) && $mm_config.ETS_MM_CLICK_TEXT_SHOW_SUB} clicktext_show_submenu{/if} {if isset($mm_config.ETS_MM_SHOW_ICON_VERTICAL)&& !$mm_config.ETS_MM_SHOW_ICON_VERTICAL} hide_icon_vertical{/if}">
        <li class="close_menu">
            <a class="pull-left" href="{$urls.base_url}">
                {if Context::getContext()->shop->id == 2}
                    <img class="logo img-fluid" src="/img/DISTRICLOS_pro_logo.png" alt="Districlos" width="178" height="48" loading="lazy">
                {else}
                    <img class="logo img-fluid" src="/img/logo-1739869573.svg" alt="Districlos" width="178" height="48" loading="lazy">
                {/if}
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
            <li class="mobile_noclic closed mm_menus_li{if $menu.enabled_vertical} mm_menus_li_tab{if $menu.menu_ver_hidden_border} mm_no_border{/if}{if $menu.menu_ver_alway_show} menu_ver_alway_show_sub{/if}{/if}{if $menu.custom_class} {$menu.custom_class|escape:'html':'UTF-8'}{/if}{if $menu.sub_menu_type} mm_sub_align_{strtolower($menu.sub_menu_type)|escape:'html':'UTF-8'}{/if}{if $menu.columns} mm_has_sub{/if}{if $menu.display_tabs_in_full_width && $menu.enabled_vertical} display_tabs_in_full_width{/if}"
                {if $menu.enabled_vertical}style="width: {if $menu.menu_item_width}{$menu.menu_item_width|escape:'html':'UTF-8'}{else}{*230px*}auto;{/if}"{/if}>
                <div class="d_flex item_first_layer mm_has_sub">
                    <a {if isset($menu.menu_open_new_tab) && $menu.menu_open_new_tab == 1} target="_blank"{/if}
                            href="{$menu.menu_link|escape:'html':'UTF-8'}"
                            style="{if $menu.enabled_vertical}{if isset($menu.menu_ver_text_color) && $menu.menu_ver_text_color}color:;{/if}{if isset($menu.menu_ver_background_color) && $menu.menu_ver_background_color}background-color:{$menu.menu_ver_background_color|escape:'html':'UTF-8'};{/if}{/if}{if Configuration::get('ETS_MM_HEADING_FONT_SIZE')}font-size:{Configuration::get('ETS_MM_HEADING_FONT_SIZE')|intval}px;{/if}">

                    <span class="mm_menu_content_title">
                        <div>
                            {if $menu.menu_img_link}
                                {if $menu.menu_link == "/4-portail-et-portillon"}
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
                                    <img src="/img/mobile-menu-arrow-left.svg" alt="Districlos" width="26px" height="12px">
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
                                                        {$tab.title|escape:'html':'UTF-8'}
                                                        {if $tab.bubble_text}<span class="mm_bubble_text"
                                                                                   style="background: {if $tab.bubble_background_color}{$tab.bubble_background_color|escape:'html':'UTF-8'}{else}#FC4444{/if}; color: {if $tab.bubble_text_color|escape:'html':'UTF-8'}{$tab.bubble_text_color|escape:'html':'UTF-8'}{else}#ffffff{/if};">{$tab.bubble_text|escape:'html':'UTF-8'}</span>{/if}
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
                                                        <img src="/img/mobile-menu-arrow-left-white.svg" alt="Districlos" width="26px" height="12px">
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
                                                        <img class="logo img-fluid" src="/img/logo-text-white.svg" alt="Districlos" width="178" height="48" loading="lazy">
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
                                    <img class="logo img-fluid" src="/img/logo-1739869573.svg" alt="Districlos" width="178" height="48" loading="lazy">
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
            <li class="info-menu-mobile-only mm_menus_li distri_megamenu mm_sub_align_full"><a href="/400-destockage">
                    <span class="mm_menu_content_title"><svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" fill="currentColor" viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg> DESTOCKAGE</span>
                </a></li>
        {else}
            <li class="info-menu-mobile-only mm_menus_li distri_megamenu mm_sub_align_full"><a href="/content/659-les-offres-au-volume">
                    <span class="mm_menu_content_title">{*<svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" fill="currentColor" viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>*}Les offres au volume</span>
                </a></li>
        {/if}
        <li class="menu-mobile-custom-container-links mm_menus_li distri_megamenu mm_sub_align_full info_menu_mobile">
            <div class="menu-mobile-custom-useful-links">
                {if $pro.env != 1}
                    <a class="menu-mobile-useful-link" href="{$pro.url_domain}">
                        <img src="/img/menu-mobile-espace-pro.svg" alt="espace pro" width="19px" height="18px">
                        <p>ESPACE<br>
                            PRO</p>
                    </a>
                {else}
                    <a class="menu-mobile-useful-link" href="{$urls.base_url}guides-et-fiches-techniques-produits">
                        <img src="/img/menu-mobile-espace-pro.svg" alt="documents" width="19px" height="18px">
                        <p>DOCUMENTS</p>
                    </a>
                {/if}
                <a class="menu-mobile-useful-link" href="/module/opartdevis/simplequotation">
                    <img src="/img/menu-mobile-devis.svg" alt="devis" width="20px" height="19px">
                    <p>DEVIS</p>
                </a>
                <a class="menu-mobile-useful-link" href="/content/11-nos-magasins-de-cloture">
                    <img src="/img/menu-mobile-magasins.svg" alt="magasins" width="19px" height="19px">
                    <p>MAGASINS</p>
                </a>
                <a class="menu-mobile-useful-link" href="{$urls.pages.authentication}" onclick="window.location.href='{$urls.pages.authentication}?back={$urls.current_url|urlencode}'">
                    <img src="/img/menu-mobile-compte.svg" alt="compte" width="16px" height="19px">
                    <p>COMPTE</p>
                </a>
            </div>
            {if Context::getContext()->shop->id == 2}
                <a href="/avantages-pro" class="btn btn-primary btn-avantages-mobile">
                    <div class="text-btn">
                        <span class="medium-text">Mes avantages</span>
                    </div>
                </a>
            {else}
                <a href="/configurateur" class="btn-configurateur-mobile">
                    <div class="text-btn">
                                <span class="small-text">
                                    Configurez<br>
                                </span>
                        <span class="medium-text">votre clôture</span>
                    </div>
                    <img width="63px" height="63px" alt="cloture" src="/img/menu-mobile-fence.svg">
                </a>
            {/if}
            <a href="tel:+33422531034" class="menu-mobile-phone-contact">
                <img loading="lazy" src="/img/menu-mobile-phone.svg" alt="contact téléphone" width="27px" height="25px">
                <span class="menu-mobile-phone-text">Des questions ?&nbsp;</span>
                <span class="menu-mobile-phone-number">04 22 53 10 34</span>
            </a>
            <img src="/img/menu-mobile-stars.svg" alt="note" width="72px" height="11px" class="menu-mobile-stars" loading="lazy">
            <p class="menu-mobile-note-text">Nos clients sont satisfaits :&nbsp;<span>9.5/10</span></p>
        </li>
        <li class="mm_menus_li distri_megamenu mm_sub_align_full info_menu_mobile">
            <div class="hide_pro image-fw-mm">
                {*                <a href="/363-les-essentiels-a-petit-prix">
                                    <img src="/img/cms/menu/slide-post-soldes-mobile.webp"
                                         alt="Bannière post soldes">
                                </a>*}
                {hook h='displayPromoMenuImage'}
            </div>
        </li>
    </ul>
    {hook h='displayCustomMenu'}
{/if}
