{* <form method="post" action="{$form_action}">
    <label for="dealt_api_key">Clé API DEALT</label>
    <input type="text" name="DEALT_API_KEY" value="{$dealt_api_key}" class="form-control" />
    <br>
    <button type="submit" name="submitDealt" class="btn btn-primary">Enregistrer</button>
</form> *}

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<div class="panel">
    <h3>Liste des commandes :</h3>
    <table id="orders" class="table table-striped table-bordered" style="width:100%;">
        <thead>
        <tr>
            <th>{l s='Nom & Prénom' mod='dealt'}</th>
            <th>{l s='Date' mod='dealt'}</th>
            <th>{l s='Id Panier' mod='dealt'}</th>
            <th>{l s='Id Commande' mod='dealt'}</th>
            <th>{l s='Attestation TVA' mod='dealt'}</th>
        </tr>
        </thead>
        <tbody>
        {if $orderList}
            {foreach $orderList as $order}
                <tr>
                    <th>{$order.lastname} {$order.firstname}</th>
                    <th>{$order.date_add}</th>
                    <th>{$order.id_cart}</th>
                    <th>{$order.id_order}</th>
                    <th>
                        {if $order.is_validated}
                            Activée
                        {else}
                            Pas activée
                        {/if}
                    </th>
                </tr>
            {/foreach}
        {/if}
        </tbody>
    </table>
</div>
<div class="panel">
    <h3>Association des services :</h3>

</div>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>

<script type="text/javascript">

    $('#orders').DataTable({
        "bLengthChange": false, // Cache le menu de changement de nombre d'éléments affichés
        // language: {
        //     url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json',
        // },
        language: {
            paginate: {
                first: '&#8606;',
                last: '&#8608;',
                previous: '&#8592;',
                next: '&#8594;'
            }
        },
        "searching": true,
        responsive: true,
        pageLength: 10,
        "order": [[0, "desc"]], // Trie la première colonne (ID) en ordre décroissant
    });

</script>
