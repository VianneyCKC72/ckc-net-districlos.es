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

            <div class="ckc-job-meta" style="margin-bottom:14px;">
                <span class="ckc-stat ckc-stat--ok">{l s='Translated:' mod='ckctranslate'} {$result.translated|default:0|intval}</span>
                <span class="ckc-stat ckc-stat--skip">{l s='Skipped:' mod='ckctranslate'} {$result.skipped|default:0|intval}</span>
                <span class="ckc-stat ckc-stat--err">{l s='Errors:' mod='ckctranslate'} {$result.errors|default:0|intval}</span>
            </div>

            {if $auto_reload}
                <p class="ckc-progress-note">{l s='Next batch will start automatically...' mod='ckctranslate'}</p>
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
