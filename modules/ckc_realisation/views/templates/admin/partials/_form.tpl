<form method="post" enctype="multipart/form-data" action="{$base_url|escape:'html'}" class="ckc-form">
    <input type="hidden" name="token" value="{$token|escape:'html'}"/>
    {if $editing && isset($row) && isset($row.id_realisation)}
        <input type="hidden" name="id_realisation" value="{$row.id_realisation|intval}"/>
    {/if}

    <div class="ckc-grid">
        <!-- Colonne 1 -->
        <div class="ckc-col">
            <div class="form-group">
                <label class="control-label">{l s='Type de client' mod=$module_name}</label>
                {assign var='ctype' value=''}
                {if $editing && isset($row) && isset($row.customer_type)}
                    {assign var='ctype' value=$row.customer_type}
                {/if}
                <select name="customer_type" class="form-control" required>
                    <option value="">{l s='— Sélectionner —' mod=$module_name}</option>
                    <option value="pro" {if $ctype=='pro'}selected{/if}>{l s='Professionnel' mod=$module_name}</option>
                    <option value="proticulier"
                            {if $ctype=='proticulier'}selected{/if}>{l s='Proticulier' mod=$module_name}</option>
                </select>
            </div>

            {assign var='row_id_product' value=0}
            {if isset($row) && isset($row.id_product)}{assign var='row_id_product' value=$row.id_product|intval}{/if}

            <div class="form-group">
                <label class="control-label">{l s='Produit' mod=$module_name}</label>
                <select name="id_product" id="id_product" class="form-control">
                    <option value="0">— {l s='Aucun produit' mod=$module_name} —</option>
                    {foreach $products as $p}
                        <option value="{$p.id_product|intval}" {if $row_id_product == $p.id_product}selected{/if}>
                            #{$p.id_product|intval} — {$p.name|escape:'html'}{if $p.reference} [{$p.reference|escape:'html'}]{/if}
                        </option>
                    {/foreach}
                </select>
            </div>


            {assign var='keywords_val' value=''}
            {if isset($row) && isset($row.keywords)}{assign var='keywords_val' value=$row.keywords}{/if}
            <div class="form-group">
                <label class="control-label">{l s='Mots-clés' mod=$module_name}</label>
                <input type="text" name="keywords" class="form-control"
                       value="{$keywords_val|escape:'html'}"
                       placeholder="{l s='Ex.: portail, grillage, pose' mod=$module_name}">
                <p class="help-block">{l s='Séparés par des virgules.' mod=$module_name}</p>
            </div>

            <div class="form-group">
                <label class="control-label">{l s='Statut' mod=$module_name}</label>
                {assign var='cur' value='pending'}
                {if isset($row) && isset($row.status)}{assign var='cur' value=$row.status}{/if}
                <select name="status" class="form-control">
                    {foreach from=$statuses key=k item=lbl}
                        <option value="{$k|escape:'html'}" {if $cur==$k}selected{/if}>{$lbl}</option>
                    {/foreach}
                </select>
            </div>

            <div class="form-group">
                <label class="control-label">{l s='Photo (JPEG/PNG ≤ 5MB)' mod=$module_name}</label>
                <input type="file" name="file" accept="image/*" class="form-control" {if !$editing}required{/if} />
                <p class="help-block">{l s='Conseil : utilisez une image 1600×1200 minimum.' mod=$module_name}</p>
                {if $editing && isset($row) && isset($row.filename) && $row.filename}
                    <div class="ckc-thumb mt-2">
                        <img src="{$file_base_url|escape:'html'}thumb_{$row.filename|escape:'url'}" alt=""/>
                    </div>
                {/if}
            </div>
        </div><!-- /ckc-col -->

        <!-- Colonne 2 -->
        <div class="ckc-col">
            {foreach $languages as $l}
                {assign var=lid value=$l.id_lang|intval}

                {assign var='title_val' value=''}
                {if isset($row_langs) && isset($row_langs[$lid]) && isset($row_langs[$lid].title)}
                    {assign var='title_val' value=$row_langs[$lid].title}
                {/if}

                {assign var='alt_val' value=''}
                {if isset($row_langs) && isset($row_langs[$lid]) && isset($row_langs[$lid].alt)}
                    {assign var='alt_val' value=$row_langs[$lid].alt}
                {/if}
                <div class="form-group">
                    <label class="control-label">{l s='Titre' mod=$module_name}</label>
                    <input type="text" class="form-control" name="title_{$lid}" value="{$title_val|escape:'html'}"/>
                </div>
                <div class="form-group">
                    <label class="control-label">{l s='Texte ALT' mod=$module_name}</label>
                    <input type="text" class="form-control" name="alt_{$lid}" value="{$alt_val|escape:'html'}"/>
                </div>
            {/foreach}
        </div>
    </div>

    <div class="ckc-actions">
        <button type="submit" class="btn btn-primary btn-xl" name="{if $editing}ckc_update{else}ckc_add{/if}">
            <i class="icon-check"></i> {if $editing}{l s='Enregistrer' mod=$module_name}{else}{l s='Ajouter' mod=$module_name}{/if}
        </button>
        {if $editing}
            <a class="btn btn-default btn-xl" href="{$base_url|escape:'html'}"><i
                        class="icon-remove"></i> {l s='Annuler' mod=$module_name}</a>
        {/if}
    </div>
</form>
