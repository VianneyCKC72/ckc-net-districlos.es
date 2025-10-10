<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link rel="stylesheet" href="{$module_dir}views/css/admin.css?v=3">

<div class="ckc-real ckc-theme-pro">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-{if $editing}pencil{else}plus{/if}"></i>
            {if $editing}{l s='Éditer une réalisation' mod=$module_name}{else}{l s='Ajouter une réalisation' mod=$module_name}{/if}
        </div>

        {include file="module:{$module_name}/views/templates/admin/partials/_form.tpl"
        module_name=$module_name
        base_url=$base_url
        token=$token
        editing=$editing
        row=$row
        row_langs=$row_langs
        stores=$stores
        languages=$languages
        statuses=$statuses
        shop_id=$shop_id
        file_base_url=$file_base_url}
    </div>

    <div class="panel">
        <div class="panel-heading">
            <i class="icon-list"></i> {l s='Réalisations' mod=$module_name}
        </div>

        {include file="module:{$module_name}/views/templates/admin/partials/_filters.tpl"
        module_name=$module_name
        base_url=$base_url
        token=$token
        f_status=$f_status}

        {include file="module:{$module_name}/views/templates/admin/partials/_list.tpl"
        module_name=$module_name
        base_url=$base_url
        token=$token
        rows=$rows
        languages=$languages
        statuses=$statuses
        shop_id=$shop_id
        file_base_url=$file_base_url}

        {include file="module:{$module_name}/views/templates/admin/partials/_pagination.tpl"
        base_url=$base_url
        token=$token
        page=$page
        pages=$pages
        f_status=$f_status}
    </div>
</div>
