<div class="panel">
    <div class="panel-heading">
        <h3><i class="icon-tags"></i> {l s='Gestion fin de vie des déclinaisons' mod='dtc_productendoflife'}</h3>
    </div>
    <div class="panel-body">
        {if isset($end_of_life_saved) && $end_of_life_saved}
            <div class="alert alert-success">
                <i class="icon-check"></i> {l s='Statuts fin de vie sauvegardés avec succès !' mod='dtc_productendoflife'}
            </div>
        {/if}
        <p class="help-block">{l s='Cochez les déclinaisons qui sont en fin de vie. Ces déclinaisons afficheront un message spécial dans le displayStock.' mod='dtc_productendoflife'}</p>
        
        <div id="end_of_life_form">
            <input type="hidden" name="id_product" value="{$id_product}">
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{l s='ID' mod='dtc_productendoflife'}</th>
                            <th>{l s='Référence' mod='dtc_productendoflife'}</th>
                            <th>{l s='Attributs' mod='dtc_productendoflife'}</th>
                            <th class="text-center">
                                {l s='Fin de vie' mod='dtc_productendoflife'}
                                <br>
                                <small>
                                    <label style="font-weight: normal; margin: 0;">
                                        <input type="checkbox" id="select_all_end_of_life" style="margin-right: 5px;">
                                        {l s='Tout sélectionner' mod='dtc_productendoflife'}
                                    </label>
                                </small>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {if $combinations}
                            {foreach from=$combinations item=combination}
                                <tr>
                                    <td>{$combination.id_product_attribute}</td>
                                    <td>
                                        {if $combination.reference}
                                            {$combination.reference}
                                        {else}
                                            <span class="text-muted">{l s='Aucune référence' mod='dtc_productendoflife'}</span>
                                        {/if}
                                    </td>
                                    <td>
                                        {if $combination.attributes}
                                            {$combination.attributes}
                                        {else}
                                            <span class="text-muted">{l s='Aucun attribut' mod='dtc_productendoflife'}</span>
                                        {/if}
                                    </td>
                                    <td class="text-center">
                                        <div class="form-group">
                                            <span class="switch prestashop-switch fixed-width-lg">
                                                <input type="checkbox" 
                                                       name="end_of_life[{$combination.id_product_attribute}]" 
                                                       id="end_of_life_{$combination.id_product_attribute}" 
                                                       value="1" 
                                                       {if isset($end_of_life_status[$combination.id_product_attribute]) && $end_of_life_status[$combination.id_product_attribute]}checked="checked"{/if}>
                                                <label for="end_of_life_{$combination.id_product_attribute}">
                                                    {l s='Oui' mod='dtc_productendoflife'}
                                                </label>
                                                <a class="slide-button btn"></a>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            {/foreach}
                        {else}
                            <tr>
                                <td colspan="4" class="text-center">
                                    <span class="text-muted">{l s='Aucune déclinaison trouvée pour ce produit.' mod='dtc_productendoflife'}</span>
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
            
            {if $combinations}
                <div class="panel-footer">
                    <button type="button" id="save_end_of_life" class="btn btn-primary pull-right">
                        <i class="process-icon-save"></i> {l s='Sauvegarder fin de vie' mod='dtc_productendoflife'}
                    </button>
                </div>
            {/if}
        </div>
    </div>
</div>

<style>
.panel h3 {
    margin-top: 0;
}
.table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}
.switch {
    margin: 0 auto;
}
.panel-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    padding: 15px;
}
#select_all_end_of_life {
    transform: scale(1.2);
    margin-right: 8px !important;
}
</style>

<script>
$(document).ready(function() {
    $('#save_end_of_life').on('click', function(e) {
        e.preventDefault();
        var id_product = $('input[name="id_product"]').val();
        var end_of_life_data = {};
        
        // Collecter les données de toutes les déclinaisons du formulaire (cochées et décochées)
        $('input[name^="end_of_life["]').each(function() {
            var name = $(this).attr('name');
            var id = name.match(/\[(\d+)\]/)[1];
            end_of_life_data[id] = $(this).is(':checked') ? 1 : 0;
        });
        
        
        // Afficher un indicateur de chargement
        $(this).prop('disabled', true).html('<i class="process-icon-loading"></i> Sauvegarde...');
        
        // Envoyer les données via AJAX
        $.ajax({
            url: '/modules/dtc_productendoflife/ajax_save.php',
            type: 'POST',
            data: {
                id_product: id_product,
                end_of_life: end_of_life_data
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Afficher un message de succès
                    $('.panel-body').prepend('<div class="alert alert-success"><i class="icon-check"></i> ' + response.message + '</div>');
                    
                    // Masquer le message après 3 secondes
                    setTimeout(function() {
                        $('.alert-success').fadeOut();
                    }, 3000);
                } else {
                    alert('Erreur: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Erreur lors de la sauvegarde: ' + error);
            },
            complete: function() {
                // Réactiver le bouton
                $('#save_end_of_life').prop('disabled', false).html('<i class="process-icon-save"></i> Sauvegarder fin de vie');
            }
        });
    });
});
</script>

