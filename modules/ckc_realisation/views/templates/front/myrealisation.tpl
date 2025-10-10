{block name='page_title'}{l s='Mes réalisations' mod=$module_name}{/block}

{block name='page_content'}

    <div class="card">
        <h2 class="h4">{l s='Ajouter une photo' mod=$module_name}</h2>
        <form method="post" action="{$upload_link|escape:'html'}" enctype="multipart/form-data">
            <input type="hidden" name="token" value="{$token|escape:'html'}" />
            <div class="form-group">
                <label>{l s='Magasin' mod=$module_name}</label>
                <select name="id_store" class="form-control" required>
                    {foreach from=$stores item=st}
                        <option value="{$st.id_store|intval}">{$st.name|escape:'html'} (ID {$st.id_store|intval})</option>
                    {/foreach}
                </select>
            </div>
            <div class="form-group">
                <label>{l s='Photo (JPEG/PNG ≤ 5MB)' mod=$module_name}</label>
                <input type="file" name="file" accept="image/*" required />
            </div>


            <button class="btn btn-primary" name="submitReal" type="submit">
                {l s='Ajouter' mod=$module_name}
            </button>
        </form>
    </div>

    <hr>

    <h2 class="h4">{l s='Mes photos' mod=$module_name}</h2>
    {if not $rows}
        <div class="alert alert-info">{l s='Vous n’avez pas encore ajouté de photo.' mod=$module_name}</div>
    {else}
        <div class="row">
            {foreach from=$rows item=r}
                <div class="col-md-3 col-sm-4 col-xs-6 mb-3">
                    <div class="card">
                        <img class="img-fluid" src="{$module_dir|escape:'html'}uploads/{$shop.id|intval}/thumb_{$r.filename|escape:'url'}" alt="{$r.alt|escape:'html'}">
                        <div class="card-body">
                            <div class="small">
                                {l s='Statut:' mod=$module_name}
                                <strong>{$r.status|escape:'html'}</strong>
                            </div>
                            <form method="post" action="{$delete_link|escape:'html'}" onsubmit="return confirm('{l s='Supprimer ?' mod=$module_name}');" style="margin-top:8px">
                                <input type="hidden" name="token" value="{$token|escape:'html'}" />
                                <input type="hidden" name="id_realisation" value="{$r.id_realisation|intval}" />
                                <button class="btn btn-sm btn-outline-danger" type="submit">{l s='Supprimer' mod=$module_name}</button>
                            </form>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    {/if}

{/block}
