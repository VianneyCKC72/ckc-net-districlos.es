{if isset($id_order)}
    {if $id_order}
        <div class="card">
            <h3 class="card-header">
                <i class="material-icons">bookmark_outline</i>
                {l s='Transférer une commande' mod='wic_disabledfrontmultishop'}
            </h3>
            <div class="card-body">
                <form action="{$currentIndex|escape:'htmlall':'UTF-8'}&amp;id_order={$orderId|escape:'htmlall':'UTF-8'}&amp;vieworder&amp;token={$smarty.get.token|escape:'htmlall':'UTF-8'}" method="post">
                    {if $transfer}
                        <div class="alert alert-success" role="alert">{l s='La commande a été tranférée avec succès.' mod='wic_disabledfrontmultishop'}</div>
                    {/if}
                    <div class="alert alert-info">
                        {l s='Le commande' mod='wic_disabledfrontmultishop'} <b>{$id_order}</b> {l s='est actuellement associé à la boutique :' mod='wic_disabledfrontmultishop'} <b>{$shopOrder['name']}</b>
                    </div>
                    <div>
                        <label>{l s='Transférer la commande vers la boutique :' mod='wic_disabledfrontmultishop'}</label>
                        <select name="shop_transfer" class="form-control">
                            <option value="">{l s='Merci de sélectionner une boutique'}</option>
                            {foreach from=$shopList item=shopObj}
                                {if $shopObj.name != $shopOrder}
                                    <option value="{$shopObj.id_store}">{$shopObj.name}</option>
                                {/if}
                            {/foreach}
                        </select>
                    </div>
                    <br/>
                    <div>
                        <button type="submit" name="submitOrderTransfer" id="submitTransferOrder" class="btn btn-primary pull-left">{l s='Transférer la commande vers cette boutique' mod='wic_disabledfrontmultishop'}</button>
                    </div>
                </form>
            </div>
        </div>
    {/if}
{/if}

<div class="card">
    <h3 class="card-header">
        <i class="material-icons">send</i>
        Envoyer vers Divalto
    </h3>
    <div class="card-body">
        {if $ignoreDivalto}
            <div class="alert alert-danger" role="alert">Le statut actuel de cette commande ne permet pas de l'envoyer à Divalto.</div>
        {else}
            <form action="index.php&id_order={$id_order|escape:'htmlall':'UTF-8'}&vieworder&token={$smarty.get.token|escape:'htmlall':'UTF-8'}" method="post" style="height: 120px;">
                {if $transferDivalto}
                    <div class="alert alert-success" role="alert">La commande a été envoyée vers Divalto avec succès.</div>
                {/if}
                <div class="alert alert-info">
                    Utilisez cette fonctionnalité pour renvoyer manuellement cette commande vers Divalto.
                </div>
                <div>
                    <button type="submit" name="submitOrderToDivalto" id="submitOrderToDivalto" class="btn btn-primary pull-left">
                        Envoyer la commande vers Divalto
                    </button>
                </div>
            </form>
        {/if}
    </div>
</div>
