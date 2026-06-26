<div class="ckc-translate-wrap">
    <div class="ckc-hero">
        <h2 class="ckc-hero__title">
            <i class="icon icon-translate"></i>
            {l s='CKC Translate Pro' mod='ckctranslate'}
        </h2>
        <p class="ckc-hero__text">
            {l s='Configure your AI translation workspace, manage queues and launch progressive translations with a polished back-office experience.' mod='ckctranslate'}
        </p>
    </div>

    <div class="panel">
        <h3><i class="icon icon-dashboard"></i> {l s='Estimation and guidance' mod='ckctranslate'}</h3>

        <div class="ckc-card ckc-card--soft">
            <span class="ckc-stat">{l s='Estimated batch cost:' mod='ckctranslate'} {$estimated_batch_cost|escape:'htmlall':'UTF-8'}</span>
            <span class="ckc-stat">{l s='Models available:' mod='ckctranslate'} {$available_models|@count}</span>
            <p class="ckc-card__text" style="margin-top:14px;">
                <strong>{l s='Advice:' mod='ckctranslate'}</strong>
                {l s='Start with product SEO fields, then descriptions, then categories and CMS.' mod='ckctranslate'}
            </p>

            <h4 class="ckc-card__title" style="margin-top:18px;">{l s='Available models' mod='ckctranslate'}</h4>
            <ul>
                {foreach from=$available_models item=model}
                    <li><span class="ckc-badge">AI</span> {$model.label|escape:'htmlall':'UTF-8'}</li>
                {/foreach}
            </ul>

            <h4 class="ckc-card__title" style="margin-top:18px;">{l s='Advanced prompt example' mod='ckctranslate'}</h4>
            <div class="well">
                {$prompt_example|escape:'htmlall':'UTF-8'}
            </div>
        </div>
    </div>

    {$form_html nofilter}

    <div class="panel">
        <h3><i class="icon icon-wrench"></i> {l s='Maintenance tools' mod='ckctranslate'}</h3>

        <div class="ckc-actions">
            <form method="post" action="{$current_url|escape:'htmlall':'UTF-8'}" style="display:inline-block;">
                <button type="submit" name="submitCkcTranslateResetQueue" class="btn btn-warning">
                    <i class="icon icon-refresh"></i>
                    {l s='Reset queue' mod='ckctranslate'}
                </button>
            </form>

            <form method="post" action="{$current_url|escape:'htmlall':'UTF-8'}" style="display:inline-block;">
                <button type="submit" name="submitCkcTranslateClearHistory" class="btn btn-danger" onclick="return confirm('{l s='Are you sure you want to clear translation history?' mod='ckctranslate' js=1}');">
                    <i class="icon icon-trash"></i>
                    {l s='Clear history' mod='ckctranslate'}
                </button>
            </form>
        </div>
    </div>

    <div class="panel">
        <h3><i class="icon icon-play"></i> {l s='Progressive translation actions' mod='ckctranslate'}</h3>
        <p class="ckc-muted">{l s='Each action processes a batch of items per launch until the queue is finished.' mod='ckctranslate'}</p>

        <div class="row">
            {foreach from=$jobs item=job}
                <div class="col-lg-4 col-md-6">
                    <div class="ckc-card">
                        <h4 class="ckc-card__title">{$job.label|escape:'htmlall':'UTF-8'}</h4>
                        <p class="ckc-card__text">{$job.advice|escape:'htmlall':'UTF-8'}</p>

                        <div class="ckc-job-meta">
                            <span class="ckc-stat">{l s='Total:' mod='ckctranslate'} {$job.total|intval}</span>
                            <span class="ckc-stat">{l s='Remaining:' mod='ckctranslate'} {$job.remaining|intval}</span>
                        </div>

                        <p>
                            <a class="btn btn-primary" href="{$current_url|escape:'htmlall':'UTF-8'}&run_job={$job.code|escape:'htmlall':'UTF-8'}">
                                <i class="icon icon-play"></i>
                                {l s='Launch' mod='ckctranslate'}
                            </a>
                        </p>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>

    <div class="panel">
        <h3><i class="icon icon-time"></i> {l s='Recent history' mod='ckctranslate'}</h3>

        {if $history_rows|@count}
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>{l s='Date' mod='ckctranslate'}</th>
                        <th>{l s='Job' mod='ckctranslate'}</th>
                        <th>{l s='Type' mod='ckctranslate'}</th>
                        <th>{l s='ID' mod='ckctranslate'}</th>
                        <th>{l s='Status' mod='ckctranslate'}</th>
                        <th>{l s='Model' mod='ckctranslate'}</th>
                        <th>{l s='Estimated cost' mod='ckctranslate'}</th>
                        <th>{l s='Message' mod='ckctranslate'}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach from=$history_rows item=row}
                        <tr>
                            <td>{$row.date_add|escape:'htmlall':'UTF-8'}</td>
                            <td>{$row.job_code|escape:'htmlall':'UTF-8'}</td>
                            <td>{$row.entity_type|escape:'htmlall':'UTF-8'}</td>
                            <td>{$row.entity_id|escape:'htmlall':'UTF-8'}</td>
                            <td><span class="ckc-badge">{$row.status|escape:'htmlall':'UTF-8'}</span></td>
                            <td>{$row.model|escape:'htmlall':'UTF-8'}</td>
                            <td>{$row.estimated_cost|escape:'htmlall':'UTF-8'}</td>
                            <td>{$row.message|escape:'htmlall':'UTF-8'}</td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        {else}
            <p class="ckc-muted">{l s='No history yet.' mod='ckctranslate'}</p>
        {/if}
    </div>

    <div class="panel">
        <h3><i class="icon icon-eye"></i> {l s='Translations preview (source → target)' mod='ckctranslate'}</h3>

        {if $preview_rows|@count}
            {foreach from=$preview_rows item=row}
                <div style="margin-bottom:16px;border:1px solid #e0e0e0;border-radius:6px;padding:10px;">
                    <strong>{$row.type|escape:'htmlall':'UTF-8'} #{$row.id|escape:'htmlall':'UTF-8'}</strong>
                    <span class="ckc-muted"> — {$row.jobs|escape:'htmlall':'UTF-8'} — {$row.date|escape:'htmlall':'UTF-8'}</span>
                    <div class="table-responsive">
                        <table class="table" style="margin-top:8px;">
                            <thead>
                            <tr>
                                <th style="width:140px;">{l s='Field' mod='ckctranslate'}</th>
                                <th>{l s='Source' mod='ckctranslate'}</th>
                                <th>{l s='Translation' mod='ckctranslate'}</th>
                            </tr>
                            </thead>
                            <tbody>
                            {foreach from=$row.fields item=f}
                                <tr>
                                    <td><code>{$f.label|escape:'htmlall':'UTF-8'}</code></td>
                                    <td class="ckc-muted">{$f.source|escape:'htmlall':'UTF-8'}</td>
                                    <td>{$f.target|escape:'htmlall':'UTF-8'}</td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            {/foreach}
        {else}
            <p class="ckc-muted">{l s='No translation to preview yet.' mod='ckctranslate'}</p>
        {/if}
    </div>
</div>