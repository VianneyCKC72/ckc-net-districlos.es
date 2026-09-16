<div class="ckc-translate-wrap">
    <div class="ckc-hero">
        <h2 class="ckc-hero__title">
            <i class="icon icon-exchange"></i>
            {l s='Translation progress' mod='ckctranslate'}
        </h2>
        <p class="ckc-hero__text">
            {l s='Your translation job is running with progressive processing and automatic reload.' mod='ckctranslate'}
        </p>
    </div>

    <div class="panel">
        <div class="ckc-card ckc-card--soft">
            <div class="alert alert-{if $result.success}success{else}danger{/if}">
                {$result.message|escape:'htmlall':'UTF-8'}
            </div>

            {if $auto_reload}
                <p class="ckc-progress-note">{l s='Next item will start automatically...' mod='ckctranslate'}</p>
                <script type="text/javascript">
                    setTimeout(function () {
                        window.location.href = '{$auto_reload_url|escape:'javascript':'UTF-8'}';
                    }, 800);
                </script>
            {else}
                <p class="ckc-progress-note"><strong>{l s='Job finished.' mod='ckctranslate'}</strong></p>
            {/if}

            <p style="margin-top:18px;">
                <a class="btn btn-default" href="{$back_url|escape:'htmlall':'UTF-8'}">
                    <i class="icon icon-arrow-left"></i>
                    {l s='Back to configuration' mod='ckctranslate'}
                </a>
            </p>
        </div>
    </div>
</div>