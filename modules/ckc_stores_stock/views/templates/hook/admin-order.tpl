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
