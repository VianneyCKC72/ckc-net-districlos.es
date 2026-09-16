<form method="post" action="{$form_action}" class="defaultForm form-horizontal">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Configuration des paliers de remises' mod='dtc_paliers_remise'}
        </div>

        <div class="alert alert-info">
            {l s='Le module gère automatiquement 3 groupes clients et 3 règles panier partagées (Bronze / Argent / Or). Aucune création manuelle n\'est nécessaire.' mod='dtc_paliers_remise'}
            <br>
            {l s='À vérifier en BO : Clients > Groupes et Catalogue > Remises panier (noms « Club Pro … »).' mod='dtc_paliers_remise'}
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Période glissante (mois)' mod='dtc_paliers_remise'}
            </label>
            <div class="col-lg-2">
                <input type="number" name="period_months" value="{$period}" min="1" max="60" class="form-control" />
            </div>
        </div>

        <hr />

        <h4>{l s='Paliers' mod='dtc_paliers_remise'}</h4>
        <p class="help-block">{l s='Ajoutez, modifiez ou supprimez les paliers de remise. Les seuils sont en € HT.' mod='dtc_paliers_remise'}</p>

        <table class="table" id="dtc-palier-table">
            <thead>
                <tr>
                    <th>{l s='Nom du niveau' mod='dtc_paliers_remise'}</th>
                    <th>{l s='Seuil minimum (€ HT)' mod='dtc_paliers_remise'}</th>
                    <th>{l s='Remise (%)' mod='dtc_paliers_remise'}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {foreach $tiers as $tier}
                    <tr>
                        <td><input type="text" name="tier_name[]" value="{$tier.name|escape:'htmlall':'UTF-8'}" class="form-control" required /></td>
                        <td><input type="number" name="tier_min[]" value="{$tier.min}" min="0" step="0.01" class="form-control" required /></td>
                        <td><input type="number" name="tier_discount[]" value="{$tier.discount}" min="0" max="100" step="0.01" class="form-control" required /></td>
                        <td><button type="button" class="btn btn-danger btn-sm dtc-remove-row"><i class="icon-trash"></i></button></td>
                    </tr>
                {/foreach}
            </tbody>
        </table>

        <button type="button" class="btn btn-default" id="dtc-add-row">
            <i class="icon-plus"></i> {l s='Ajouter un palier' mod='dtc_paliers_remise'}
        </button>

        <div class="panel-footer">
            <button type="submit" name="submitDtcPalierConfig" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Enregistrer' mod='dtc_paliers_remise'}
            </button>
        </div>
    </div>
</form>

<script>
    (function() {
        document.getElementById('dtc-add-row').addEventListener('click', function() {
            var tbody = document.querySelector('#dtc-palier-table tbody');
            var row = document.createElement('tr');
            row.innerHTML =
                '<td><input type="text" name="tier_name[]" value="" class="form-control" required /></td>' +
                '<td><input type="number" name="tier_min[]" value="0" min="0" step="0.01" class="form-control" required /></td>' +
                '<td><input type="number" name="tier_discount[]" value="0" min="0" max="100" step="0.01" class="form-control" required /></td>' +
                '<td><button type="button" class="btn btn-danger btn-sm dtc-remove-row"><i class="icon-trash"></i></button></td>';
            tbody.appendChild(row);
        });

        document.querySelector('#dtc-palier-table').addEventListener('click', function(e) {
            var btn = e.target.closest('.dtc-remove-row');
            if (btn) {
                btn.closest('tr').remove();
            }
        });
    })();
</script>
