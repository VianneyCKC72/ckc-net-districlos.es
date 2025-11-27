<form method="post" enctype="multipart/form-data" action="{$base_url|escape:'html'}">
    <input type="hidden" name="token" value="{$token|escape:'html'}" />
    {if $editing && $row.id_realisation}
        <input type="hidden" name="id_realisation" value="{$row.id_realisation|intval}" />
    {/if}

    <div class="form-group">
        <label>{l s='Magasin' mod=$module_name}</label>
        <select name="id_store" class="form-control" required>
            <option value="">{l s='— Sélectionner —' mod=$module_name}</option>
            {foreach $stores as $s}
                <option value="{$s.id_store|intval}" {if $editing && $row.id_store == $s.id_store}selected{/if}>
                    {$s.id_store|intval} - {$s.name|escape:'html'}
                </option>
            {/foreach}
        </select>
    </div>

    <div class="form-group">
        <label>{l s='Statut' mod=$module_name}</label>
        <select name="status" class="form-control">
            {assign var=cur value=($editing ? $row.status : 'pending')}
            {foreach from=$statuses key=k item=lbl}
                <option value="{$k|escape:'html'}" {if $cur==$k}selected{/if}>{$lbl}</option>
            {/foreach}
        </select>
    </div>

    <div class="form-group">
        <label>{l s='Photo (JPEG/PNG ≤ 5MB)' mod=$module_name}</label>
        <input type="file" name="file" accept="image/*" {if !$editing}required{/if} />
        {if $editing && $row.filename}
            <div style="margin-top:8px">
                <img src="{$file_base_url|escape:'html'}thumb_{$row.filename|escape:'url'}" style="max-height:120px" alt="">
            </div>
        {/if}
    </div>

    {foreach $languages as $l}
        {assign var=lid value=$l.id_lang|intval}
        <div class="form-group">
            <label>{l s='Titre' mod=$module_name} ({$l.iso_code|escape:'html'})</label>
            {assign var=_title value=''}
            {if isset($row_langs[$lid].title)}{assign var=_title value=$row_langs[$lid].title}{elseif $editing && $l.id_lang==$cookie->id_lang}{assign var=_title value=$row.title}{/if}
            <input type="text" class="form-control" name="title_{$lid}" value="{$_title|escape:'html'}" />
        </div>
        <div class="form-group">
            <label>{l s='Texte ALT' mod=$module_name} ({$l.iso_code|escape:'html'})</label>
            {assign var=_alt value=''}
            {if isset($row_langs[$lid].alt)}{assign var=_alt value=$row_langs[$lid].alt}{elseif $editing && $l.id_lang==$cookie->id_lang}{assign var=_alt value=$row.alt}{/if}
            <input type="text" class="form-control" name="alt_{$lid}" value="{$_alt|escape:'html'}" />
        </div>
    {/foreach}

    <button type="submit" class="btn btn-primary" name="{if $editing}ckc_update{else}ckc_add{/if}">
        <i class="icon-check"></i> {if $editing}{l s='Enregistrer' mod=$module_name}{else}{l s='Ajouter' mod=$module_name}{/if}
    </button>
    {if $editing}
        <a class="btn btn-default" href="{$base_url|escape:'html'}"><i class="icon-remove"></i> {l s='Annuler' mod=$module_name}</a>
    {/if}
</form>
