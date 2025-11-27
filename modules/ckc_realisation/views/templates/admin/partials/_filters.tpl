{* modules/ckc_realisation/views/templates/admin/partials/_filters.tpl *}
<form method="get" action="" class="form-inline ckc-filters">
    <input type="hidden" name="controller" value="AdminModules">
    <input type="hidden" name="configure" value="{$module_name|escape:'html'}">
    <input type="hidden" name="module_name" value="{$module_name|escape:'html'}">
    <input type="hidden" name="token" value="{$token|escape:'html'}">

    <div class="form-group">
        <label class="control-label">{l s='Statut' mod=$module_name}</label>
        <select name="f_status" class="form-control">
            <option value="">{l s='Tous' mod=$module_name}</option>
            <option value="pending"  {if $f_status=='pending'}selected{/if}>{l s='En attente' mod=$module_name}</option>
            <option value="approved" {if $f_status=='approved'}selected{/if}>{l s='Approuvée' mod=$module_name}</option>
            <option value="rejected" {if $f_status=='rejected'}selected{/if}>{l s='Rejetée' mod=$module_name}</option>
        </select>
    </div>

    <div class="form-group">
        <label class="control-label">{l s='Type de client' mod=$module_name}</label>
        <select name="f_type" class="form-control">
            <option value="">{l s='Tous' mod=$module_name}</option>
            <option value="pro"         {if $f_type=='pro'}selected{/if}>{l s='Professionnel' mod=$module_name}</option>
            <option value="proticulier" {if $f_type=='proticulier'}selected{/if}>{l s='Proticulier' mod=$module_name}</option>
        </select>
    </div>


    <div class="btn-group">
        <button class="btn btn-default btn-ghost" type="submit">
            <i class="icon-search"></i> {l s='Filtrer' mod=$module_name}
        </button>
        <a class="btn btn-default btn-ghost" href="{$base_url|escape:'html'}">
            <i class="icon-refresh"></i> {l s='Réinitialiser' mod=$module_name}
        </a>
    </div>
</form>
