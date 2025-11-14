<h2>Ajouter une section</h2>

<div style="background-color:#f8f9fa; border-left:4px solid #007bff; padding:15px; margin-bottom:20px; border-radius:4px; margin-top: 20px">
    <p>
        Ici, vous pouvez récupérer les produits qui possèdent un guide et/ou une fiche technique.
        Il suffit de saisir la catégorie souhaitée. Les produits ayant des documents associés
        seront affichés, et ces documents pourront être téléchargés au format <strong>ZIP</strong>.
    </p>
    <p>
        L'URL en front de la page sera : <code>DOMAIN-PRO/guides-et-fiches-techniques-produits</code>
    </p>
    <p>
        Vous pouvez choisir le titre de chaque section. Chaque section correspond à une catégorie,
        et vous pouvez également définir l'ordre d'affichage des sections (drag and drop via l'icone <i class="icon-arrows"></i> ).
    </p>
</div>

{assign var=url value=$link->getAdminLink('AdminGuidesFiches')}

<form method="post">
    <label for="id_category" class="d-block">Catégorie :</label>
    <select name="id_category" id="id_category" class="form-control select2 d-block" required>
        <option value="">-- Choisissez une catégorie --</option>
        {foreach from=$categories item=cat}
            <option value="{$cat.id}">{$cat.name}</option>
        {/foreach}
    </select>

    <br>

    <label for="title_section" class="d-block mt-4">Titre de la section :</label>
    <input type="text" name="title_section" required />

    <button type="submit" name="submit_add_section" class="btn btn-primary mt-3">Ajouter</button>
</form>

<hr>

<h2>Sections existantes</h2>
<form method="post" id="bulk-delete-form">
    <button type="submit" name="bulk_delete_sections" class="btn btn-danger mb-3" onclick="return confirm('Supprimer les sections sélectionnées ?');">
        Supprimer la sélection
    </button>
</form>

    <table class="table">
        <thead>
            <tr>
                <th><input type="checkbox" id="check-all" /></th>
                <th>#</th>
                <th>ID</th>
                <th>ID Catégorie</th>
                <th>Titre de la section en front-office</th>
                <th>Active</th>
                <th>Créé le</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="sortable-sections">
            {foreach from=$sections item=section}
                <tr data-id="{$section.id_section}" class="sortable-row">
                    <td><input type="checkbox" name="bulk_ids[]" form="bulk-delete-form"  value="{$section.id_section}" class="bulk-checkbox" /></td>
                    <td class="drag-handle"><i class="icon-arrows"></i></td>
                    <td>{$section.id_section}</td>
                    <td>{$section.id_category}</td>
                    <td>
                        <form method="post" style="display: flex; gap: 5px;">
                            <input type="hidden" name="id_section" value="{$section.id_section}" />
                            <input  type="text" name="title_section" value="{$section.title_section|escape:'htmlall'}" class="form-control form-control-sm" required />
                            <button type="submit" name="update_section" class="btn btn-sm btn-primary">
                                Modifier
                            </button>
                        </form>
                    </td>
                    <td>
                        <a href="#" class="toggle-active" data-id="{$section.id_section}" title="Activer / Désactiver">
                            {if $section.active}
                                <i class="icon-check text-success"></i>
                            {else}
                                <i class="icon-remove text-danger"></i>
                            {/if}
                        </a>
                    </td>
                    <td>{$section.created_at}</td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="id_section" value="{$section.id_section}">
                            <button type="submit" name="delete_section" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette section ?');">
                                Supprimer
                            </button>
                        </form>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>



<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        $('#id_category').select2({
            placeholder: "Recherchez une catégorie...",
            allowClear: true
        });
    });
</script>

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
    $(document).ready(function() {
        $('.toggle-active').on('click', function(e) {
            e.preventDefault();

            var $link = $(this);
            var id_section = $link.data('id');
            $.ajax({
                url: '{$url}',
                type: 'POST',
                data: { 
                    id_section: id_section,
                    ajax: 1,
                    action: 'ToggleActive'
                },
                dataType: "json",

                success: function(response) {


                    console.log(response)
                    if (response.success) {
                        if (response.active) {
                            $link.html('<i class="icon-check text-success"></i>');
                        } else {
                            $link.html('<i class="icon-remove text-danger"></i>');
                        }
                    } else {

                        console.log(response);
                        alert('Erreur lors de la mise à jour: ' + (response.error || 'Erreur inconnue'));
                    }
                },
                error: function() {
                    alert('Erreur AJAX.');
                }
            });
        });
    });
</script>


{* ordering section *}
<script>
    $(function () {
        $('#sortable-sections').sortable({
            handle: '.drag-handle',
            update: function (event, ui) {
                let orderedIDs = $('#sortable-sections tr').map(function () {
                    return $(this).data('id');
                }).get();

                $.ajax({
                    type: 'POST',
                    url: '{$url}',  
                    data: { 
                            ordered_ids: orderedIDs,
                        ajax: 1,
                        action: 'UpdateOrdering'
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            $.growl.notice({ message: "Ordre mis à jour avec succès !" });
                        } else {
                            alert("Erreur lors de la mise à jour de l'ordre.");
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log('Erreur AJAX:', xhr.responseText);
                        alert("Erreur lors de la mise à jour de l'ordre.");
                    }
                });
            }
        });
    });
</script>

{* bulk delete *}
<script>
    $('#check-all').on('click', function () {
        $('.bulk-checkbox').prop('checked', this.checked);
    });
</script>
