<div class="card ckc-logo-lang">
    <h3 class="card-header">
        {l s='Logo par langue' d='Modules.Ckclogolang.Admin'}
    </h3>
    <div class="card-body">
        <p class="ckc-logo-lang__help text-muted">
            {if $ckc_logo_on_theme_page}
                {l s='Le bloc « Logo » plus bas reste le logo par défaut (toutes les langues). Ici, vous pouvez en définir un autre par langue (ex. anglais). Si une langue n’a pas de fichier, le logo par défaut est utilisé.' d='Modules.Ckclogolang.Admin'}
            {else}
                {l s='Le logo par défaut se règle dans Apparence → Thème → Logo. Ici, un fichier optionnel par langue. Si une langue n’a pas de fichier, le logo du thème est utilisé.' d='Modules.Ckclogolang.Admin'}
            {/if}
        </p>
        <form method="post" action="{$ckc_logo_action|escape:'html':'UTF-8'}" enctype="multipart/form-data">
            <input type="hidden" name="submitCkcLogoLang" value="1">
            {if $ckc_logo_from_theme}
                <input type="hidden" name="ckc_logo_from_theme" value="1">
            {/if}
            {foreach from=$ckc_logo_rows item=row}
                <div class="ckc-logo-lang__row">
                    <div class="ckc-logo-lang__lang">
                        {$row.name|escape:'html':'UTF-8'}
                        <span class="ckc-logo-lang__iso">{$row.iso_code|escape:'html':'UTF-8'}</span>
                    </div>
                    <div class="ckc-logo-lang__preview">
                        {if $row.src}
                            <img src="{$row.src|escape:'html':'UTF-8'}" alt="{$row.name|escape:'html':'UTF-8'}">
                        {else}
                            <span class="ckc-logo-lang__empty">{l s='Logo du thème (par défaut)' d='Modules.Ckclogolang.Admin'}</span>
                        {/if}
                    </div>
                    <div class="ckc-logo-lang__fields">
                        <input type="file" name="{$row.input_name|escape:'html':'UTF-8'}" accept=".png,.jpg,.jpeg,.gif,.svg,.webp">
                        {if $row.filename}
                            <label class="ckc-logo-lang__delete">
                                <input type="checkbox" name="{$row.delete_name|escape:'html':'UTF-8'}" value="1">
                                {l s='Supprimer et revenir au logo du thème' d='Modules.Ckclogolang.Admin'}
                            </label>
                        {/if}
                    </div>
                </div>
            {/foreach}
            <div class="text-right mt-3">
                <button type="submit" class="btn btn-primary">{l s='Enregistrer' d='Modules.Ckclogolang.Admin'}</button>
            </div>
        </form>
    </div>
</div>
