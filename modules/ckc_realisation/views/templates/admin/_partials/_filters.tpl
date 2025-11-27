<form method="get" action="" class="form-inline" style="padding:10px 15px;">
    <input type="hidden" name="controller" value="AdminModules">
    <input type="hidden" name="configure" value="{$module_name|escape:'html'}">
    <input type="hidden" name="module_name" value="{$module_name|escape:'html'}">
    <input type="hidden" name="token" value="{$token|escape:'html'}">

    <div class="form-group">
        <label>{l s='Statut' mod=$module_name}</label>
        <select name="f_status" class="form-control" style="margin:0 8px">
            <option value="">{l s='Tous' mod=$module_name}</option>
            <option value="pending"  {if $f_status=='pending'}selected{/if}>{l s='En attente' mod=$module_name}</option>
            <option value="approved" {if $f_status=='approved'}selected{/if}>{l s='Approuvée' mod=$module_name}</option>
            <option value="rejected" {if $f_status=='rejected'}selected{/if}>{l s='Rejetée' mod=$module_name}</option>
        </select>
    </div>

    <div class="form-group" style="margin-left:10px">
        <label>{l s='Magasin' mod=$module_name}</label>
        <select name="f_store" class="form-control" style="margin:0 8px">
            <option value="">{l s='Tous les magasins' mod=$module_name}</option>
            {foreach $stores as $s}
                <option value="{$s.id_store|intval}" {if $f_store==$s.id_store}selected{/if}>
                    {$s.id_store|intval} - {$s.name|escape:'html'}
                </option>
            {/foreach}
        </select>
    </div>

    <button class="btn btn-default" type="submit">
        <i class="icon-search"></i> {l s='Filtrer' mod=$module_name}
    </button>
</form>
