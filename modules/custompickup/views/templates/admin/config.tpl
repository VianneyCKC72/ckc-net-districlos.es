<div class="panel">
    <h3><i class="icon-cogs"></i> {l s='Configuration des points de retrait' mod='custompickup'}</h3>

    <form method="post" action="">
        <div class="form-group">
            <label for="pickupStores">
                {l s='Sélectionnez les magasins à activer pour le retrait :' mod='custompickup'}
            </label>

            <div class="well" style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
                {foreach from=$stores item=store}
                    <div class="checkbox" style="margin-bottom: 8px;">
                        <label>
                            <input type="checkbox" name="CUSTOMPICKUP_SELECTED_STORES[]" value="{$store.id_store}" {if in_array($store.id_store, $selectedStores)}checked{/if}>
                            <strong>{$store.name}</strong> <span class="text-muted">({$store.city})</span>
                        </label>
                    </div>
                {/foreach}
            </div>
        </div>

        {if $products_no_pickup|@count > 0}

            <label for="pickupStores">
                {l s='Produits sans retrait magasin :' mod='custompickup'}
            </label>

            <ul style="background:#f8f8f8; border:1px solid #e0e0e0; border-radius:6px; padding:15px 25px; max-height:300px; overflow-y:auto; margin-bottom:20px;">
                {foreach from=$products_no_pickup item=prod}
                    <li style="padding:4px 0; border-bottom:1px solid #eaeaea; font-size:1em;">
                        <span style="color:#888;">({$prod.id_product})</span> <span style="color:#222;">{$prod.product_name|escape:'htmlall':'UTF-8'}</span>
                    </li>
                {/foreach}
            </ul>
        {else}
            <p style="color:#2d7a2d; font-weight:bold;">Tous les produits ont un transporteur de type Retrait, Économique ou Standard.</p>
        {/if}

        {if $selectedStoresInfo|@count > 0}

            <label for="pickupStores">
                {l s='Correspondance magasins / transporteurs :' mod='custompickup'}
            </label>
            
            <table style="width:100%; background:#f8f8f8; border-radius:6px; border:1px solid #e0e0e0; margin-bottom:20px;">
                <thead>
                    <tr>
                        <th style="padding:6px;">ID</th>
                        <th style="padding:6px;">Magasin</th>
                        <th style="padding:6px;">Ville</th>
                        <th style="padding:6px;">Transporteur associé</th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$selectedStoresInfo item=store}
                    <tr>
                        <td style="padding:6px;">{$store.id_store}</td>
                        <td style="padding:6px;">{$store.store_name|escape:'htmlall':'UTF-8'}</td>
                        <td style="padding:6px;">{$store.city|escape:'htmlall':'UTF-8'}</td>
                        <td style="padding:6px;">
                            {if $store.carrier}
                                ({$store.carrier.id_carrier}) {$store.carrier.name|escape:'htmlall':'UTF-8'}
                            {else}
                                <span style="color:red;">Aucun</span>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        {/if}

        <div class="panel-footer">
            <button type="submit" name="submit_custompickup_config" class="btn btn-success">
                <i class="icon-save"></i> {l s='Enregistrer les paramètres' mod='custompickup'}
            </button>
            {assign var=totalProducts value=0}
            {if isset($products_no_pickup)}
                {assign var=totalProducts value=$products_no_pickup|@count}
            {else}
                {assign var=totalProducts value=0}
            {/if}
            {* On prend tous les produits actifs si besoin, sinon à adapter *}

            <button type="submit" id="custompickup-reindex-btn" name="custompickup_reindex_products" class="btn btn-warning" onclick="return confirm('Cette opération va réinitialiser les transporteurs de tous les produits. Continuer ?');">
                <span id="custompickup-reindex-label">Réindexer les produits (Retrait)</span>
                <span id="custompickup-reindex-progress" style="display:none; margin-left:10px;">
                    <span id="custompickup-reindex-count">0</span> / <span id="custompickup-reindex-total">{$totalProducts}</span> indexé
                    <span style="display:inline-block; width:120px; height:10px; background:#eee; border-radius:5px; margin-left:8px; vertical-align:middle;">
                        <span id="custompickup-reindex-bar" style="display:block; height:10px; width:0; background:#f0ad4e; border-radius:5px;"></span>
                    </span>
                </span>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('custompickup-reindex-btn');
    if (!btn) return;
    btn.addEventListener('click', function(e) {
        setTimeout(function() {
            var total = parseInt(document.getElementById('custompickup-reindex-total').textContent, 10);
            if (!total || isNaN(total)) total = 1;
            var count = 0;
            var label = document.getElementById('custompickup-reindex-label');
            var progress = document.getElementById('custompickup-reindex-progress');
            var countSpan = document.getElementById('custompickup-reindex-count');
            var totalSpan = document.getElementById('custompickup-reindex-total');
            var bar = document.getElementById('custompickup-reindex-bar');
            if (!progress) return;
            label.style.display = 'none';
            progress.style.display = 'inline-block';
            btn.disabled = true;
            var interval = setInterval(function() {
                count++;
                countSpan.textContent = count;
                bar.style.width = (count/total*120) + 'px';
                if (count >= total) {
                    clearInterval(interval);
                    btn.disabled = false;
                    label.style.display = '';
                    progress.style.display = 'none';
                }
            }, 10);
        }, 100);
    });
});
</script>
