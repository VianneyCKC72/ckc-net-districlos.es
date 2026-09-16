{**
 * Prestashop module : everpsadvancepayment
 *
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link https://www.team-ever.com
 *}

<tr style="background-color: #cccccc;font-size:10px;">
    <td style="text-align:center; width:15%;font-size:8px;">
        {if $product.reference}
            {$product.reference|escape:'htmlall':'UTF-8'}
        {else}
            --
        {/if}
        {$smarty.foreach.foo.iteration|escape:'htmlall':'UTF-8'}
    </td>
    <td style="width:40%">
        <table>
            <tr style="font-size:10px;">
                <td>{$product.product_name|escape:'htmlall':'UTF-8'}</td>
            </tr>
            {if isset($product.product_attribute_id) && $product.product_attribute_id != 0}
                <tr style="font-size:10px;">
                    <td>{$product.attributes|escape:'htmlall':'UTF-8'}</td>
                </tr>
            {/if}
        </table>
    </td>

    <td style="text-align:center; width:10%">
        {$product.tax_rate|escape:'htmlall':'UTF-8'}{l s='%'  mod='everpsadvancepayment'}
    </td>
    <td style="text-align:center; width:15%">
        {if $product.gift && !empty($product.gift)}
            <span>{l s='Gift!'  mod='everpsadvancepayment'}</span>
        {else}
            {Tools::displayPrice($product.unit_price_tax_excl, $currency)}
        {/if}
    </td>
    <td style="text-align:center; width:5%">
        {$product.product_quantity|escape:'htmlall':'UTF-8'}
    </td>
    <td style="text-align:center; width:15%">
        <span id="total_product_price_{$product.id_product|escape:'htmlall':'UTF-8'}_{$product.id_product_attribute|escape:'htmlall':'UTF-8'}">
            {if $product.gift && !empty($product.gift)}
                <span>{l s='Gift!' mod='everpsadvancepayment'}</span>
            {else}
                {Tools::displayPrice($product.total_price_tax_excl, $currency)}
            {/if}
        </span>
    </td>
</tr>
