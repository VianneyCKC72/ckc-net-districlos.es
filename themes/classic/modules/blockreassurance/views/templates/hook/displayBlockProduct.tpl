{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 *}
{if $page.page_name != 'checkout' && $page.page_name != 'product'}
    <div class="banner-services stack-row__cards my-section">
        {foreach from=$blocks item=$block key=$key}
            <div class="service" {if $block['type_link'] !==$LINK_TYPE_NONE && !empty($block['link'])} style="cursor:pointer;"
                onclick="window.open('{$block['link']}')" {/if}>
                {if $block['icon'] != 'undefined'}
                    {if $block['custom_icon']}
                        <img src="{$block['custom_icon']}" loading="lazy" alt="{$block['title']}">
                    {elseif $block['icon']}
                        <img src="{$block['icon']}" loading="lazy" alt="{$block['title']}">
                    {/if}
                {/if}
                <div>
                    {if empty($block['description'])}
                        <p>{$block['title']|replace:'__':'<br>' nofilter}</p>
                    {else}
                        <p>{$block['title']}
                            {*                    <p>{$block['description'] nofilter}</p>*}
                            {if $block['description']== '__picto3x4x10x12x'}
                                <br>
                                <img class="paiement_plusieurs_fois" height="21px" width="102px" src="/themes/classic/assets/img/services/paiement_plusieurs_fois.png" loading="lazy" alt="Payer en plusieurs fois">
                            {/if}
                        </p>
                    {/if}

                </div>
            </div>
        {/foreach}
    </div>
{elseif $page.page_name == 'product'}
    <div class="banner-services stack-row__cards my-section">
        {foreach from=$blocks item=$block key=$key}
            {if $block['title'] == 'Fabrication__européenne'}
                {if $from_europe}
                    <div class="service" {if $block['type_link'] !==$LINK_TYPE_NONE && !empty($block['link'])} style="cursor:pointer;"
                        onclick="window.open('{$block['link']}')" {/if}>
                        {if $block['icon'] != 'undefined'}
                            {if $block['custom_icon']}
                                <img src="{$block['custom_icon']}" loading="lazy" alt="{$block['title']}">
                            {elseif $block['icon']}
                                <img src="{$block['icon']}" loading="lazy" alt="{$block['title']}">
                            {/if}
                        {/if}
                        <div>
                            {if empty($block['description'])}
                                <p>{$block['title']|replace:'__':'<br>' nofilter}</p>
                            {else}
                                <p>{$block['title']}
                                    {*                    <p>{$block['description'] nofilter}</p>*}
                                    {if $block['description']== '__picto3x4x10x12x'}
                                        <br>
                                        <img class="paiement_plusieurs_fois" height="21px" width="102px" src="/themes/classic/assets/img/services/paiement_plusieurs_fois.png" loading="lazy" alt="Payer en plusieurs fois">
                                    {/if}
                                </p>
                            {/if}

                        </div>
                    </div>
                {else}
                    <div class="service" {if $block['type_link'] !==$LINK_TYPE_NONE && !empty($block['link'])} style="cursor:pointer;"
                        onclick="window.open('{$block['link']}')" {/if}>
                        {if $block['icon'] != 'undefined'}
                            {if $block['custom_icon']}
                                <img src="{$block['custom_icon']}" loading="lazy" alt="{$block['title']}">
                            {elseif $block['icon']}
                                <img src="{$block['icon']}" loading="lazy" alt="{$block['title']}">
                            {/if}
                        {/if}
                        <div>
                            <p>Fabrication<br>française</p>
                        </div>
                    </div>
                {/if}
            {else}
                <div class="service" {if $block['type_link'] !==$LINK_TYPE_NONE && !empty($block['link'])} style="cursor:pointer;"
                    onclick="window.open('{$block['link']}')" {/if}>
                    {if $block['icon'] != 'undefined'}
                        {if $block['custom_icon']}
                            <img src="{$block['custom_icon']}" loading="lazy" alt="{$block['title']}">
                        {elseif $block['icon']}
                            <img src="{$block['icon']}" loading="lazy" alt="{$block['title']}">
                        {/if}
                    {/if}
                    <div>
                        {if empty($block['description'])}
                            <p>{$block['title']|replace:'__':'<br>' nofilter}</p>
                        {else}
                            <p>{$block['title']}
                                {*                    <p>{$block['description'] nofilter}</p>*}
                                {if $block['description']== '__picto3x4x10x12x'}
                                    <br>
                                    <img class="paiement_plusieurs_fois" height="21px" width="102px" src="/themes/classic/assets/img/services/paiement_plusieurs_fois.png" loading="lazy" alt="Payer en plusieurs fois">
                                {/if}
                            </p>
                        {/if}

                    </div>
                </div>
            {/if}
        {/foreach}
    </div>
{/if}