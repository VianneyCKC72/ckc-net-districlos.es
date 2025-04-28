<div class="panel">
    <h3>{l s='Stock par boutique' mod='ckcstockmultistore'}</h3>
    <table id="productStockTable" class="table table-striped table-bordered">
        <thead>
        <tr>
            <th>{l s='ID Boutique' mod='ckcstockmultistore'}</th>
            <th>{l s='ID Attribut' mod='ckcstockmultistore'}</th>
            <th>{l s='Nom de la boutique' mod='ckcstockmultistore'}</th>
            <th>{l s='Stock disponible' mod='ckcstockmultistore'}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $stocks as $stock}
            <tr>
                <td>{$stock.id_store}</td>
                <th>{$stock.id_product_attribute}</th>
                <td>{$stock.name}</td>
                <td>{$stock.quantity|default:0}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#productStockTable').DataTable({
            "language": {
                "url": "{$module_dir}views/js/datatables/lang/fr.json"
            }
        });
    });
</script>
