<div class="panel">
    <div class="panel-heading">
        <i class="icon-list"></i> {l s='Déclinaisons en fin de vie' mod='dtc_productendoflife'}
    </div>
    <div class="panel-body">
        <p class="alert alert-info">
            <i class="icon-info-circle"></i> 
            {l s='Total: %d déclinaison(s) marquée(s) en fin de vie' sprintf=[$total_count] mod='dtc_productendoflife'}
        </p>
        
        {if $end_of_life_products}
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s='ID Déclinaison' mod='dtc_productendoflife'}</th>
                        <th>{l s='Produit' mod='dtc_productendoflife'}</th>
                        <th>{l s='Référence' mod='dtc_productendoflife'}</th>
                        <th>{l s='Attributs' mod='dtc_productendoflife'}</th>
                        <th>{l s='Seuil stock' mod='dtc_productendoflife'}</th>
                        <th>{l s='Date ajout' mod='dtc_productendoflife'}</th>
                        <th>{l s='Date mise à jour' mod='dtc_productendoflife'}</th>
                        <th>{l s='Actions' mod='dtc_productendoflife'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$end_of_life_products item=product}
                        <tr>
                            <td>{$product.id_product_attribute}</td>
                            <td>
                                {if $product.id_product}
                                    <a href="{$link->getAdminLink('AdminProducts')}&id_product={$product.id_product}&updateproduct" target="_blank">
                                        {$product.product_name}
                                    </a>
                                {else}
                                    -
                                {/if}
                            </td>
                            <td>{$product.reference|default:'-'}</td>
                            <td>{$product.attributes|default:'-'}</td>
                            <td>
                                <input type="number" 
                                       class="form-control threshold-input" 
                                       data-id="{$product.id_product_attribute}"
                                       value="{$product.stock_threshold|default:''}" 
                                       placeholder="Aucun"
                                       min="0"
                                       style="width: 80px;">
                            </td>
                            <td>{$product.date_add}</td>
                            <td>{$product.date_upd}</td>
                            <td>
                                <button class="btn btn-primary btn-sm save-threshold" data-id="{$product.id_product_attribute}">
                                    <i class="icon-save"></i> {l s='Sauvegarder' mod='dtc_productendoflife'}
                                </button>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        {else}
            <p class="alert alert-warning">
                <i class="icon-warning"></i> 
                {l s='Aucune déclinaison en fin de vie' mod='dtc_productendoflife'}
            </p>
        {/if}
    </div>
</div>

<script>
$(document).ready(function() {
    $('.save-threshold').on('click', function() {
        var btn = $(this);
        var id_product_attribute = btn.data('id');
        var threshold = $('.threshold-input[data-id="' + id_product_attribute + '"]').val();
        
        btn.prop('disabled', true).html('<i class="icon-refresh icon-spin"></i> Sauvegarde...');
        
        $.ajax({
            url: '/modules/dtc_productendoflife/ajax_save_threshold.php',
            type: 'POST',
            data: {
                id_product_attribute: id_product_attribute,
                stock_threshold: threshold
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    btn.html('<i class="icon-check"></i> Sauvegardé');
                    setTimeout(function() {
                        btn.prop('disabled', false).html('<i class="icon-save"></i> {l s='Sauvegarder' mod='dtc_productendoflife'}');
                    }, 2000);
                } else {
                    alert('Erreur: ' + response.message);
                    btn.prop('disabled', false).html('<i class="icon-save"></i> {l s='Sauvegarder' mod='dtc_productendoflife'}');
                }
            },
            error: function() {
                alert('Erreur lors de la sauvegarde');
                btn.prop('disabled', false).html('<i class="icon-save"></i> {l s='Sauvegarder' mod='dtc_productendoflife'}');
            }
        });
    });
});
</script>
