<div id="ckc-customer-boutique-block" class="card card col-lg-6 m-3">
    <h3 class="card-header">
        Boutique
    </h3>
    <div class="card-body">
        {if !$has_store}
            <div class="alert alert-info">
                {l s='Aucune commande pour ce client.' mod='ckcstockstores'}
            </div>
        {elseif $denied}
            <div class="alert alert-warning">
                {l s='La dernière boutique existe mais vous n’êtes pas autorisé à la voir.' mod='ckcstockstores'}
            </div>
        {else}
            <p style="margin:0.25rem 0;">
                {$store_name|escape:'html'}
            </p>
        {/if}
    </div>
</div>
