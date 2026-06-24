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
        <div class="wrapper-table-myaccount-page-devis">
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
                                    <span class="opartDevisHide">{l s='Modifier / Valider' d='Shop.Theme.Custommodules'}</span>
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
        </div>
    {else}
        <p>
            {l s='Vous n\'avez pas encore de devis enregistré sur votre compte.' d='Shop.Theme.Custommodules'}
            <br>
            <a href="{$link->getModuleLink('opartdevis', 'simplequotation')}" class="mt-2 btn btn-primary">
                {l s='Faire une demande de devis' d='Shop.Theme.Custommodules'}
            </a>
        </p>
    {/if}
    <a href="/identite"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-left-icon lucide-move-left"><path d="M6 8L2 12L6 16"/><path d="M2 12H22"/></svg> {l s='Mon compte' d='Shop.Theme.Custommodules'}</a>

{/block}
