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

    <div class="alert alert-info">
        <strong>{l s='Current mode:' mod='ckctranslate'}</strong>
        {if $translate_mode == 'force'}
            {l s='Force re-translate everything' mod='ckctranslate'}
        {elseif $translate_mode == 'missing'}
            {l s='Empty fields only (legacy)' mod='ckctranslate'}
        {else}
            {l s='Empty fields OR French copy in target (recommended)' mod='ckctranslate'}
        {/if}
        <br>
        {l s='Tip: use Reset queue before relaunching after changing mode. Batch size = AI translations only (already-done items are skipped without burning the quota).' mod='ckctranslate'}
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

            <form method="post" action="{$current_url|escape:'htmlall':'UTF-8'}" style="display:inline-block;">
                <button type="submit" name="submitCkcClearCeCache" class="btn btn-default" onclick="return confirm('{l s='Clear CE element cache for target language? CE will re-render all translated pages.' mod='ckctranslate' js=1}');">
                    <i class="icon icon-remove-circle"></i>
                    {l s='Clear CE cache (target lang)' mod='ckctranslate'}
                </button>
            </form>
        </div>
    </div>

    <div class="panel">
        <h3><i class="icon icon-play"></i> {l s='Progressive translation actions' mod='ckctranslate'}</h3>
        <p class="ckc-muted">{l s='Each launch translates up to N items with AI. Already-done entries are skipped automatically and do not consume the batch quota.' mod='ckctranslate'}</p>

        <div class="row">
            {foreach from=$jobs item=job}
                <div class="col-lg-4 col-md-6">
                    <div class="ckc-card">
                        <h4 class="ckc-card__title">{$job.label|escape:'htmlall':'UTF-8'}</h4>
                        <p class="ckc-card__text">{$job.advice|escape:'htmlall':'UTF-8'}</p>

                        <div class="ckc-job-meta">
                            <span class="ckc-stat">{l s='Total:' mod='ckctranslate'} {$job.total|intval}</span>
                            <span class="ckc-stat">{l s='Queue left:' mod='ckctranslate'} {$job.remaining|intval}</span>
                            <span class="ckc-stat ckc-stat--ok">{l s='Done:' mod='ckctranslate'} {$job.done|intval}</span>
                            <span class="ckc-stat ckc-stat--skip">{l s='Skipped:' mod='ckctranslate'} {$job.skipped|intval}</span>
                            <span class="ckc-stat ckc-stat--err">{l s='Errors:' mod='ckctranslate'} {$job.errors|intval}</span>
                        </div>

                        <p>
                            <a class="btn btn-primary" href="{$current_url|escape:'htmlall':'UTF-8'}&run_job={$job.code|escape:'htmlall':'UTF-8'}">
                                <i class="icon icon-play"></i>
                                {l s='Launch' mod='ckctranslate'}
                            </a>
                            <a class="btn btn-default" href="{$current_url|escape:'htmlall':'UTF-8'}&history_job={$job.code|escape:'htmlall':'UTF-8'}#ckc-history">
                                <i class="icon icon-list"></i>
                                {l s='History' mod='ckctranslate'}
                            </a>
                        </p>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>

    <div class="panel" id="ckc-history">
        <h3><i class="icon icon-time"></i> {l s='Recent history' mod='ckctranslate'}</h3>

        <form method="get" class="form-inline" style="margin-bottom:14px;">
            <label for="history_job">{l s='Filter by job' mod='ckctranslate'}</label>
            <select id="history_job" class="form-control" onchange="window.location.href=this.value;">
                <option value="{$current_url|escape:'htmlall':'UTF-8'}#ckc-history"{if !$history_job} selected="selected"{/if}>{l s='All jobs' mod='ckctranslate'}</option>
                {foreach from=$history_job_options item=opt}
                    <option value="{$current_url|escape:'htmlall':'UTF-8'}&history_job={$opt.code|escape:'htmlall':'UTF-8'}#ckc-history"{if $history_job == $opt.code} selected="selected"{/if}>{$opt.label|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
        </form>

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
                            <td>
                                <span class="ckc-badge ckc-badge--{$row.status|escape:'htmlall':'UTF-8'}">
                                    {$row.status|escape:'htmlall':'UTF-8'}
                                </span>
                            </td>
                            <td>{$row.model|escape:'htmlall':'UTF-8'}</td>
                            <td>{$row.estimated_cost|escape:'htmlall':'UTF-8'}</td>
                            <td>{$row.message|escape:'htmlall':'UTF-8'}</td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
            <p class="ckc-muted">{l s='Showing up to 100 latest rows.' mod='ckctranslate'}</p>
        {else}
            <p class="ckc-muted">{l s='No history yet.' mod='ckctranslate'}</p>
        {/if}
    </div>
</div>
