{**
 * Prestashop module : Everpsproductstoreinvoice
 *
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link https://www.team-ever.com
 *}

{$style_tab nofilter}
<table width="100%" id="body" border="0" cellpadding="0" cellspacing="0" style="margin:0;">
    <table style="width: 100%">
        <tr>
            <td style="width: 100%">
                {if !empty($delivery_address)}
                    <table style="width: 100%">
                        <tr>
                            <td style="width: 33%">
                                <h4 style="color: #9E9F9E">{l s='Delivery Address' mod='everpsadvancepayment'}</h4>
                                <p>{$delivery_address nofilter}</p>
                            </td>
                            <td style="width: 33%">
                                <h4 style="color: #9E9F9E">{l s='Billing Address' mod='everpsadvancepayment'}</h4>
                                <p>{$invoice_address nofilter}</p>
                            </td>
                            <td style="width: 33%">
                                <h4 style="color: #9E9F9E">{l s='Shop Address' mod='everpsadvancepayment'}</h4>
                                <p>{$store_infos nofilter}</p>
                            </td>
                        </tr>
                    </table>
                {else}
                    <table style="width: 100%">
                        <tr>
                            <td style="width: 50%">
                                <h4 style="color: #9E9F9E">{l s='Billing & Delivery Address.' mod='everpsadvancepayment'}</h4>
                                <p>{$invoice_address nofilter}</p>
                            </td>
                            <td style="width: 50%">
                                <h4 style="color: #9E9F9E">{l s='Shop Address.' mod='everpsadvancepayment'}</h4>
                                <p>{$store_infos nofilter}</p>
                            </td>
                        </tr>
                    </table>
                {/if}
            </td>
        </tr>
    </table>
    <br />
    <br />
    <!-- /ADDRESSES -->

    <table id="summary-tab" width="100%">
        <tr style="color:#FFFFFF; background-color: #4D4D4D;font-size:8px;">
            <th class="header small" valign="middle" style="color:#FFFFFF; background-color: #4D4D4D;font-size:8px;">{l s='Order Reference' d='Shop.Pdf' pdf='true'}</th>
            <th class="header small" valign="middle" style="color:#FFFFFF; background-color: #4D4D4D;font-size:8px;">{l s='Order date' d='Shop.Pdf' pdf='true'}</th>
        </tr>
        <tr>
            <td class="center small white">{$order->getUniqReference()}</td>
            <td class="center small white">{dateFormat date=$order->date_add full=0}</td>
        </tr>
    </table>

    <!-- CART -->
    <table id="cart_summary" width="100%" style="text-align:left;" cellpadding="3">
        <thead>
            {assign var='odd' value=0}
            <tr style="color:#FFFFFF; background-color: #4D4D4D;font-size:8px;">
                <td style="font-weight: bold; text-align:center; width:15%">{l s='Ref.' mod='everpsadvancepayment'}</td>
                <td style="font-weight: bold; text-align:center; width:40%">{l s='Product' mod='everpsadvancepayment'}</td>
                <td style="font-weight: bold; text-align:center; width:10%">{l s='Taxe rate' mod='everpsadvancepayment'}</td>
                <td style="font-weight: bold; text-align:center; width:15%">{l s='Unit price tax excl.' mod='everpsadvancepayment'}</td>
                <td style="font-weight: bold; text-align:center; width:5%">{l s='Qty' mod='everpsadvancepayment'}</td>
                <td style="font-weight: bold; text-align:center; width:15%">{l s='Total tax excl.' mod='everpsadvancepayment'}</td>
            </tr>
        </thead>
        <tbody>
            {foreach from=$products item=product}
                {include file="$pdf_product_line" productLast=$product@last productFirst=$product@first cannotModify=1}
            {/foreach}
        </tbody>
        <tfoot>

        </tfoot>
    </table>
    <!-- /CART -->
    <br />
    <!-- OTHER -->
    <table id="cart_other" width="100%" style="text-align:left;" cellpadding="0">
        <tr>
            <td style="width: 50%"></td>
            <td style="width: 50%">
                <table width="100%" cellpadding="3">
                    <thead>
                        {assign var='odd' value=0}
                        <tr style="color:#FFFFFF; background-color: #4D4D4D;font-size:10px;">
                            <td colspan="3" style="font-weight: bold; text-align:center;">{l s='Total Order' mod='everpsadvancepayment'}</td>
                        </tr>
                    </thead>
                    <tbody>
                        {* Total Order products *}
                        <tr>
                            <td colspan="2" style="text-align:right; font-weight:bold;background-color: #cccccc;">{l s='Total Order products (tax excl.)' mod='everpsadvancepayment'}</td>
                            <td colspan="1" style="text-align:right;">{Tools::displayPrice($total_products_tax_excl, $currency)}</td>
                        </tr>
                        {* End Total Order products *}
                        {* Shipping *}
                        <tr>
                            <td colspan="2" style="text-align:right; font-weight:bold;background-color: #cccccc;">{l s='Total Shipping (tax excl.)' mod='everpsadvancepayment'}</td>
                            {if $total_shipping_tax_excl > 0}
                                <td colspan="1" style="text-align:right;">{Tools::displayPrice($total_shipping_tax_excl, $currency)}</td>
                            {else}
                                <td colspan="1" style="text-align:right;">{l s='Free Shipping' mod='everpsadvancepayment'}</td>
                            {/if}
                        </tr>
                        {* End Shipping *}
                        {* tax *}
                        <tr>
                            <td colspan="2" style="text-align:right; font-weight:bold;background-color: #cccccc;">{l s='Total tax' mod='everpsadvancepayment'}</td>
                            <td colspan="1" style="text-align:right;">{Tools::displayPrice($main_tax, $currency)}</td>
                        </tr>
                        {* End tax *}
                        {if isset($show_total_discounts) && $show_total_discounts}
                        {* Reduction *}
                        <tr>
                            <td colspan="2" style="text-align:right; font-weight:bold;background-color: #cccccc;">{l s='Total reduction (tax excl.)' mod='everpsadvancepayment'}</td>
                            {if $total_discounts_tax_excl > 0}
                                <td colspan="1" style="text-align:right;">{Tools::displayPrice($total_discounts_tax_excl, $currency)}</td>
                            {else}
                                <td colspan="1" style="text-align:right;">{l s='No discount' mod='everpsadvancepayment'}</td>
                            {/if}
                        </tr>
                        {/if}
                        {* End Reduction *}
                        {* Total *}
                        <tr>
                            <td colspan="2" style="text-align:right; font-weight:bold;background-color: #cccccc;">{l s='Total (tax incl.)' mod='everpsadvancepayment'}</td>
                            <td colspan="1" style="text-align:right;">{Tools::displayPrice($total_paid_tax_incl, $currency)}</td>
                        </tr>
                        {* End Total *}
                        {* Total Advance Payment *}
                        <tr>
                            {if !$advance_use_taxes}
                            <td colspan="2" style="text-align:right; font-weight:bold;background-color: #cccccc;">{l s='Advance Payment (tax excl.)' mod='everpsadvancepayment'}</td>
                            <td colspan="1" style="text-align:right;">{Tools::displayPrice($advance_tax_excl, $currency)}</td>
                            {else}
                            <td colspan="2" style="text-align:right; font-weight:bold;background-color: #cccccc;">{l s='Advance Payment (tax incl.)' mod='everpsadvancepayment'}</td>
                            <td colspan="1" style="text-align:right;">{Tools::displayPrice($advance_tax_incl, $currency)}</td>
                            {/if}
                        </tr>
                        {* End Advance Payment *}
                    </tbody>
                </table>
            </td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td></tr>
    </table>
    <!-- /OTHER -->
    <br />

    <br />
    <!-- DETAIL TAX -->
    {if isset($tax_details) && !empty($tax_details) && count($tax_details)>0 && $use_taxes}
        <br />
        <table cellpadding="3">
            <thead>
                <tr style="color:#FFFFFF; background-color: #4D4D4D; font-weight:bold; padding:2px;">
                    <th style="width: 40%;">{l s='TAX DETAILS' mod='everpsadvancepayment'}</th>
                    <th style="width: 20%;">{l s='Tax rate' mod='everpsadvancepayment'}</th>
                    <th style="width: 20%;">{l s='Total without tax' mod='everpsadvancepayment'}</th>
                    <th style="width: 20%;text-align: right;">{l s='Total tax' mod='everpsadvancepayment'}</th>
                </tr>
            </thead>
            {foreach $tax_details as $tax}
                <tr>
                    <td style="width: 40%;">{$tax.prefix|escape:'htmlall':'UTF-8'}</td>
                    <td style="width: 20%;">{$tax.name|escape:'htmlall':'UTF-8'}</td>
                    <td style="width: 20%;">{Tools::displayPrice($tax.total_ht)}</td>
                    <td style="width: 20%;text-align: right;">{Tools::displayPrice($tax.total_tax)}</td>
                </tr>
            {/foreach}
        </table>
    {/if}
    <!-- /DETAIL TAX -->
</table>
{$additional_message nofilter}
