<div class="realisation-form mb-4">
    <h2 class="h5 mb-3">{l s='Ajouter une photo' d='Shop.Theme.Customckc'}</h2>

    <form method="post" action="{$upload_link|escape:'html'}" enctype="multipart/form-data">
        <input type="hidden" name="token" value="{$token|escape:'html'}" />
        <div class="form-group mb-3">
            <label>{l s='Photo (JPEG/PNG ≤ 5MB)' d='Shop.Theme.Customckc'}</label>
            <input type="file" name="file" accept="image/*" required class="form-control" />
        </div>
        <button class="btn btn-primary" name="submitReal" type="submit">
            {l s='Ajouter' d='Shop.Theme.Customckc'}
        </button>
    </form>
</div>

<hr />

<div class="realisation-list mt-4">
    <h2 class="h5 mb-3">{l s='Mes photos' d='Shop.Theme.Customckc'}</h2>

    {if not $rows}
        <div class="alert alert-info">
            {l s='Vous n’avez pas encore ajouté de photo.' d='Shop.Theme.Customckc'}
        </div>
    {else}
        <div class="row">
            {foreach from=$rows item=r}
                <div class="col-md-3 col-sm-4 col-6 mb-3">
                    <div class="card shadow-sm">
                        <img class="img-fluid" src="{$module_dir|escape:'html'}uploads/{$shop.id|intval}/thumb_{$r.filename|escape:'url'}" alt="">
                        <div class="card-body p-2 text-center">
                            <div class="small text-muted mb-2">
                                {l s='Statut :' d='Shop.Theme.Customckc'} <strong>{$r.status|escape:'html'}</strong>
                            </div>
                            <form method="post" action="{$delete_link|escape:'html'}"
                                  onsubmit="return confirm('{l s='Supprimer ?' d='Shop.Theme.Customckc' js=1}');">
                                <input type="hidden" name="token" value="{$token|escape:'html'}" />
                                <input type="hidden" name="id_realisation" value="{$r.id_realisation|intval}" />
                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                    {l s='Supprimer' d='Shop.Theme.Customckc'}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    {/if}
</div>
