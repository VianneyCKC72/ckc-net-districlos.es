<div>
	<table id="store{$boutique.id_store}" class="table table-striped table-bordered" style="width:100%; font-weight:300 !important;">
        <thead>
        <tr>
            <th>ID Boutique</th>
            <th>{l s='ID Product' mod='ckcstockmultistore'}</th>
            <th>{l s='ID Product Attribut' mod='ckcstockmultistore'}</th>
            <th>{l s='Quantité' mod='ckcstockmultistore'}</th>
        </tr>
        </thead>
        <tbody>
		{foreach $boutique.products as $product}
            <tr>
                <th>{$product.id_store}</th>
				<th>{$product.id_product}</th>
				<th>
					{if $product.id_product_attribute != 0}
						{$product.id_product_attribute}
					{else}
						Quantité totale pour toutes les déclinaisons / déclinaisons n'existent pas
					{/if}
				</th>
				<th>{$product.quantity}</th>
            </tr>
		{/foreach}

        </tbody>
    </table>
</div>

<script>
    var idStore = "{$boutique.id_store|escape:'htmlall':'UTF-8'}";
    var storeTable = '#store' + idStore

	$(storeTable).DataTable({
		"bLengthChange": false,
        "language": {
            "url": "{$module_dir}views/js/datatables/lang/fr.json"
        },
		"oLanguage": {
			"sSearch": "Rechercher :"
		},
		"searching": true,
		responsive: true,
		pageLength: 10,
		"order": [[0, "desc"]],
	});
</script>
