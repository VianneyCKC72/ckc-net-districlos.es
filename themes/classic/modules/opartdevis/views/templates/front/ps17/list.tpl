{**
 * Prestashop module : OpartDevis
 *
 * @author Olivier CLEMENCE <manit4c@gmail.com>
 * @copyright  Op'art
 * @license Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 *}

{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='Your quotations' mod='opartdevis'}
{/block}

{block name='page_content'}
    {if isset($deleted) && $deleted=="success"}
        <div class="alert alert-success">{l s='Quotation deleted' mod='opartdevis'}</div>
    {/if}

    {if $quotations && count($quotations)}
        <table id="order-list" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th class="item"></th>
                    <th class="item">{l s='Date' mod='opartdevis'}</th>
                    {if isset($expiretime) && $expiretime > 0}
                        <th class="item">{l s='Expired date' mod='opartdevis'}</th>
                    {/if}
                    <th class="item">{l s='Name' mod='opartdevis'}</th>
                    <th class="item">&nbsp;</th>
                    <th class="item">&nbsp;</th>
                    <th class="last_item">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$quotations item=quotation name=myLoop}
                {assign var="date_districlos" value=$quotation.date_add|date_format:"%Y-%m-%d"}
                {$futurdate = date("Y-m-d",strtotime("+7days",strtotime($date_districlos)))}
                {assign var="date_districlos_today" value=$smarty.now|date_format:"%Y-%m-%d"}<br/>
                {assign var="difference_date" value=(strtotime($date_districlos_today) - strtotime($futurdate)) / 24 / 60 / 60}
                {if $date_districlos_today > $futurdate}
                    {$quotation.status=3}
                {/if}

                    <tr>
                        <td data-label="ID">{$quotation.id_opartdevis|escape:'htmlall':'UTF-8'}</td>
                        <td data-label="Date">{dateFormat date=$quotation.date_add full=1}</td>
                        {if isset($quotation.expire_date) && $quotation.expire_date > 0}
                            <td>{dateFormat date=$quotation.expire_date full=1}</td>
                        {/if}
                        <td data-label="Nom">{$quotation.name|escape:'htmlall':'UTF-8'}</td>
                        <td data-label="Modifier/Valider">
                            {if $quotation.status == OpartQuotation::VALIDATED}
                                <a href="{$link->getModuleLink('opartdevis','loadquotation',['opartquotationId'=>$quotation.id_opartdevis,'proceedCheckout'=>true])|escape:'htmlall':'UTF-8'}" class="btn btn-primary">
                                    <span class="opartDevisHide">{l s='Proceed to checkout' mod='opartdevis'}</span>
                                </a>
                            {else if $quotation.status == OpartQuotation::ORDERED}
                                <a href="{$link->getPageLink('order-detail', true, NULL, "id_order={$quotation.id_order|intval}")|escape:'html':'UTF-8'}" class="btn btn-primary">
                                    <span class="opartDevisHide">{l s='Display order' mod='opartdevis'}</span>
                                </a>
                            {else if $quotation.status == OpartQuotation::EXPIRED}
                                {l s='Expired' mod='opartdevis'}
                            {else}
                                <a href="{$link->getModuleLink('opartdevis','loadquotation',['opartquotationId'=>$quotation.id_opartdevis])|escape:'htmlall':'UTF-8'}" class="btn btn-outline-primary btn-sm">
                                    <span class="opartDevisHide">Modifier / Valider</span>
                                </a>
                            {/if}
                        </td>
                        <td data-label="Supprimer">
                            {if $quotation.status == OpartQuotation::NOT_VALIDATED || $quotation.status == OpartQuotation::EXPIRED}
                                <a href="{$link->getModuleLink('opartdevis', 'listquotation', ['action' => 'delete', 'opartquotationId' => $quotation.id_opartdevis])|escape:'htmlall':'UTF-8'}" class="btn btn-outline-danger btn-sm">
                                    <span class="opartDevisHide">{l s='Delete' mod='opartdevis'}</span>
                                </a>
                            {/if}
                        </td>
                        <td data-label="Télécharger">
                            <a href="{$link->getModuleLink('opartdevis', 'showquotation', ['id_cart' => $quotation.id_cart])|escape:'htmlall':'UTF-8'}" class="btn btn-outline-success btn-sm">
                                <span class="opartDevisHide">{l s='Download' mod='opartdevis'}</span>
                            </a>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
        <div id="block-order-detail" class="hidden">&nbsp;</div>
    {else}
        <p class="warning">{l s='You have no quotation' mod='opartdevis'}</p>
    {/if}
{/block}
