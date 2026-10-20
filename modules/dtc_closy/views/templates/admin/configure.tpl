{*
* Configuration BO — dtc_closy
*}
<div class="panel">
    <h3><i class="icon icon-question-circle"></i> {l s='Districlos — Closy' mod='dtc_closy'}</h3>
    <p class="help-block">
        {l s='Bulles IA sur fiche produit (OpenAI) + assistant YourGPT (icône flottante et panneau latéral).' mod='dtc_closy'}
    </p>
    <div class="row">
        <div class="col-lg-8">
            <div class="alert alert-info" style="margin-bottom:0;">
                <strong>{l s='Mode test fiche produit' mod='dtc_closy'}</strong> —
                {l s='Ajoutez' mod='dtc_closy'} <code>?dtc_closy_test=1</code>
                {l s='à l’URL produit pour afficher un bandeau de diagnostic.' mod='dtc_closy'}
            </div>
        </div>
        <div class="col-lg-4">
            <p style="margin-top:0;">
                <strong>{l s='Statistiques' mod='dtc_closy'}</strong><br>
                {l s='Produits actifs' mod='dtc_closy'} : <strong>{$dtc_closy_stats.total_products|intval}</strong><br>
                {l s='Avec questions IA' mod='dtc_closy'} : <strong>{$dtc_closy_stats.products_with_questions|intval}</strong><br>
                {l s='Questions en base' mod='dtc_closy'} : <strong>{$dtc_closy_stats.total_questions|intval}</strong>
            </p>
        </div>
    </div>
</div>

<form method="post" action="{$dtc_closy_form_action|escape:'html':'UTF-8'}" class="form-horizontal dtc-closy-admin-config">
    <input type="hidden" name="token" value="{$dtc_closy_admin_token|escape:'html':'UTF-8'}">

    <div class="panel">
        <h3><i class="icon icon-power-off"></i> {l s='Général' mod='dtc_closy'}</h3>

        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Module actif' mod='dtc_closy'}</label>
            <div class="col-lg-9">
                <select name="DTC_CLOSY_ACTIVE" class="form-control fixed-width-sm">
                    <option value="1" {if $dtc_closy_active}selected{/if}>{l s='Oui' mod='dtc_closy'}</option>
                    <option value="0" {if !$dtc_closy_active}selected{/if}>{l s='Non' mod='dtc_closy'}</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Afficher sans questions IA' mod='dtc_closy'}</label>
            <div class="col-lg-9">
                <select name="DTC_CLOSY_SHOW_FALLBACK" class="form-control fixed-width-lg">
                    <option value="1" {if $dtc_closy_show_fallback}selected{/if}>{l s='Oui — délai + CTA uniquement' mod='dtc_closy'}</option>
                    <option value="0" {if !$dtc_closy_show_fallback}selected{/if}>{l s='Non — masquer tout le bloc' mod='dtc_closy'}</option>
                </select>
            </div>
        </div>

        <div class="panel-footer">
            <button type="submit" name="submitDtcClosy" value="1" class="btn btn-primary pull-right">
                <i class="process-icon-save"></i> {l s='Enregistrer' mod='dtc_closy'}
            </button>
        </div>
    </div>

    <div class="panel">
        <h3><i class="icon icon-comment"></i> {l s='Bulles sous la galerie produit' mod='dtc_closy'}</h3>
        <p class="help-block">{l s='Un texte par langue (sélecteur FR / EN).' mod='dtc_closy'}</p>

                {capture name='closy_lbl_header'}{l s='Titre du bloc' mod='dtc_closy'}{/capture}
        {include file='module:dtc_closy/views/templates/admin/_partials/lang_field.tpl' dtc_lang_label=$smarty.capture.closy_lbl_header dtc_lang_name='DTC_CLOSY_HEADER_LABEL' dtc_lang_values=$dtc_closy_header_label dtc_lang_type='text'}
                {capture name='closy_lbl_transport'}{l s='Question livraison (fixe)' mod='dtc_closy'}{/capture}
        {include file='module:dtc_closy/views/templates/admin/_partials/lang_field.tpl' dtc_lang_label=$smarty.capture.closy_lbl_transport dtc_lang_name='DTC_CLOSY_STATIC_TRANSPORT' dtc_lang_values=$dtc_closy_static_transport dtc_lang_type='text'}
                {capture name='closy_lbl_cta'}{l s='Libellé CTA' mod='dtc_closy'}{/capture}
        {capture name='closy_help_cta'}{l s='Bouton vert « Posez votre question » (ouvre le chat).' mod='dtc_closy'}{/capture}
        {include file='module:dtc_closy/views/templates/admin/_partials/lang_field.tpl' dtc_lang_label=$smarty.capture.closy_lbl_cta dtc_lang_name='DTC_CLOSY_CTA_LABEL' dtc_lang_values=$dtc_closy_cta_label dtc_lang_type='text' dtc_lang_help=$smarty.capture.closy_help_cta}
        <div class="panel-footer">
            <button type="submit" name="submitDtcClosy" value="1" class="btn btn-primary pull-right">
                <i class="process-icon-save"></i> {l s='Enregistrer' mod='dtc_closy'}
            </button>
        </div>
    </div>

    <div class="panel">
        <h3><i class="icon icon-magic"></i> {l s='Génération des questions (OpenAI)' mod='dtc_closy'}</h3>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Clé API OpenAI' mod='dtc_closy'}</label>
            <div class="col-lg-6">
                <input type="text" name="DTC_CLOSY_OPENAI_API_KEY" value="{$dtc_closy_api_key|escape:'html':'UTF-8'}" class="form-control" placeholder="sk-..." autocomplete="off">
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Modèle' mod='dtc_closy'}</label>
            <div class="col-lg-4">
                <input type="text" name="DTC_CLOSY_OPENAI_MODEL" value="{$dtc_closy_model|escape:'html':'UTF-8'}" class="form-control" placeholder="gpt-4o-mini">
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Taille du batch (cron)' mod='dtc_closy'}</label>
            <div class="col-lg-2">
                <input type="number" name="DTC_CLOSY_BATCH_SIZE" value="{$dtc_closy_batch_size|intval}" class="form-control" min="1" max="200">
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" name="submitDtcClosy" value="1" class="btn btn-primary pull-right">
                <i class="process-icon-save"></i> {l s='Enregistrer' mod='dtc_closy'}
            </button>
        </div>
    </div>

    <div class="panel">
        <h3><i class="icon icon-comments"></i> {l s='Panneau assistant YourGPT' mod='dtc_closy'}</h3>
        {if $dtc_closy_panel_ready}
            <div class="alert alert-success">{l s='Panneau configuré.' mod='dtc_closy'} {if $dtc_closy_panel_visible_now}{l s='Visible maintenant.' mod='dtc_closy'}{else}{l s='Masqué (planning).' mod='dtc_closy'}{/if}</div>
        {else}
            <div class="alert alert-warning">{l s='Panneau inactif ou incomplet.' mod='dtc_closy'}</div>
        {/if}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Panneau custom actif' mod='dtc_closy'}</label>
            <div class="col-lg-9">
                <select name="DTC_CLOSY_PANEL_ACTIVE" class="form-control fixed-width-sm">
                    <option value="1" {if $dtc_closy_panel_active}selected{/if}>{l s='Oui' mod='dtc_closy'}</option>
                    <option value="0" {if !$dtc_closy_panel_active}selected{/if}>{l s='Non' mod='dtc_closy'}</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Clé API YourGPT' mod='dtc_closy'}</label>
            <div class="col-lg-6">
                <input type="password" name="DTC_CLOSY_YOURGPT_API_TOKEN" value="{$dtc_closy_yourgpt_api_token|escape:'html':'UTF-8'}" class="form-control" autocomplete="new-password">
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Widget UID YourGPT' mod='dtc_closy'}</label>
            <div class="col-lg-6">
                <input type="text" name="DTC_CLOSY_YOURGPT_WIDGET_UID" value="{$dtc_closy_yourgpt_widget_uid|escape:'html':'UTF-8'}" class="form-control">
            </div>
        </div>
                {capture name='closy_lbl_ptitle'}{l s='Titre du panneau' mod='dtc_closy'}{/capture}
        {include file='module:dtc_closy/views/templates/admin/_partials/lang_field.tpl' dtc_lang_label=$smarty.capture.closy_lbl_ptitle dtc_lang_name='DTC_CLOSY_PANEL_TITLE' dtc_lang_values=$dtc_closy_panel_title dtc_lang_type='text'}
                {capture name='closy_lbl_welcome'}{l s="Message d'accueil (fiche produit)" mod='dtc_closy'}{/capture}
        {include file='module:dtc_closy/views/templates/admin/_partials/lang_field.tpl' dtc_lang_label=$smarty.capture.closy_lbl_welcome dtc_lang_name='DTC_CLOSY_PANEL_WELCOME' dtc_lang_values=$dtc_closy_panel_welcome dtc_lang_type='textarea'}
                {capture name='closy_lbl_welcome_g'}{l s="Message d'accueil (site global)" mod='dtc_closy'}{/capture}
        {include file='module:dtc_closy/views/templates/admin/_partials/lang_field.tpl' dtc_lang_label=$smarty.capture.closy_lbl_welcome_g dtc_lang_name='DTC_CLOSY_PANEL_WELCOME_GLOBAL' dtc_lang_values=$dtc_closy_panel_welcome_global dtc_lang_type='textarea'}
        <div class="panel-footer">
            <button type="submit" name="submitDtcClosy" value="1" class="btn btn-primary pull-right">
                <i class="process-icon-save"></i> {l s='Enregistrer' mod='dtc_closy'}
            </button>
        </div>
    </div>

    <div class="panel">
        <h3><i class="icon icon-clock-o"></i> {l s='Planning affichage (lanceur)' mod='dtc_closy'}</h3>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Planning actif' mod='dtc_closy'}</label>
            <div class="col-lg-9">
                <select name="DTC_CLOSY_SCHEDULE_ENABLED" class="form-control fixed-width-sm">
                    <option value="1" {if $dtc_closy_schedule_enabled}selected{/if}>{l s='Oui' mod='dtc_closy'}</option>
                    <option value="0" {if !$dtc_closy_schedule_enabled}selected{/if}>{l s='Non' mod='dtc_closy'}</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Heure de début' mod='dtc_closy'}</label>
            <div class="col-lg-2">
                <input type="number" name="DTC_CLOSY_SCHEDULE_HOUR_START" value="{$dtc_closy_schedule_hour_start|intval}" class="form-control" min="0" max="24">
            </div>
            <label class="control-label col-lg-3">{l s='Heure de fin' mod='dtc_closy'}</label>
            <div class="col-lg-2">
                <input type="number" name="DTC_CLOSY_SCHEDULE_HOUR_END" value="{$dtc_closy_schedule_hour_end|intval}" class="form-control" min="0" max="24">
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Jours d’affichage' mod='dtc_closy'}</label>
            <div class="col-lg-9">
                <input type="hidden"
                       name="DTC_CLOSY_SCHEDULE_ACTIVE_DAYS_SELECTED"
                       id="dtc-closy-schedule-days-value"
                       value="{$dtc_closy_schedule_days_saved|escape:'html':'UTF-8'}">
                <label class="checkbox-inline" style="margin-right:12px;">
                    <input type="checkbox" name="DTC_CLOSY_SCHEDULE_DAY_1" value="1" data-dtc-closy-schedule-day="1" {if $dtc_closy_schedule_day_1}checked="checked"{/if}> {l s='Lundi' mod='dtc_closy'}
                </label>
                <label class="checkbox-inline" style="margin-right:12px;">
                    <input type="checkbox" name="DTC_CLOSY_SCHEDULE_DAY_2" value="1" data-dtc-closy-schedule-day="2" {if $dtc_closy_schedule_day_2}checked="checked"{/if}> {l s='Mardi' mod='dtc_closy'}
                </label>
                <label class="checkbox-inline" style="margin-right:12px;">
                    <input type="checkbox" name="DTC_CLOSY_SCHEDULE_DAY_3" value="1" data-dtc-closy-schedule-day="3" {if $dtc_closy_schedule_day_3}checked="checked"{/if}> {l s='Mercredi' mod='dtc_closy'}
                </label>
                <label class="checkbox-inline" style="margin-right:12px;">
                    <input type="checkbox" name="DTC_CLOSY_SCHEDULE_DAY_4" value="1" data-dtc-closy-schedule-day="4" {if $dtc_closy_schedule_day_4}checked="checked"{/if}> {l s='Jeudi' mod='dtc_closy'}
                </label>
                <label class="checkbox-inline" style="margin-right:12px;">
                    <input type="checkbox" name="DTC_CLOSY_SCHEDULE_DAY_5" value="1" data-dtc-closy-schedule-day="5" {if $dtc_closy_schedule_day_5}checked="checked"{/if}> {l s='Vendredi' mod='dtc_closy'}
                </label>
                <label class="checkbox-inline" style="margin-right:12px;">
                    <input type="checkbox" name="DTC_CLOSY_SCHEDULE_DAY_6" value="1" data-dtc-closy-schedule-day="6" {if $dtc_closy_schedule_day_6}checked="checked"{/if}> {l s='Samedi' mod='dtc_closy'}
                </label>
                <label class="checkbox-inline" style="margin-right:12px;">
                    <input type="checkbox" name="DTC_CLOSY_SCHEDULE_DAY_7" value="1" data-dtc-closy-schedule-day="7" {if $dtc_closy_schedule_day_7}checked="checked"{/if}> {l s='Dimanche' mod='dtc_closy'}
                </label>
                <p class="help-block">{l s='Enregistré :' mod='dtc_closy'} <code id="dtc-closy-schedule-days-display">{$dtc_closy_schedule_days_saved|escape:'html':'UTF-8'}</code></p>
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" name="submitDtcClosy" value="1" class="btn btn-primary pull-right">
                <i class="process-icon-save"></i> {l s='Enregistrer' mod='dtc_closy'}
            </button>
        </div>
    </div>

    <div class="panel">
        <h3><i class="icon icon-wrench"></i> {l s='Outils & maintenance' mod='dtc_closy'}</h3>
        <p class="help-block">{l s='Actions ponctuelles : elles n’enregistrent pas la configuration ci-dessus.' mod='dtc_closy'}</p>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Génération batch' mod='dtc_closy'}</label>
            <div class="col-lg-9">
                <button type="submit" name="submitDtcRunBatch" value="1" class="btn btn-primary">{l s='Lancer le batch OpenAI' mod='dtc_closy'}</button>
                <p class="help-block">{l s='Utilise la taille de batch définie dans la section OpenAI (produits sans questions).' mod='dtc_closy'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Test un produit' mod='dtc_closy'}</label>
            <div class="col-lg-9 form-inline">
                <input type="number" name="dtc_test_id_product" class="form-control fixed-width-xl" min="1" placeholder="id_product">
                <button type="submit" name="submitDtcTestProduct" value="1" class="btn btn-default">{l s='Tester' mod='dtc_closy'}</button>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Groupes clients' mod='dtc_closy'}</label>
            <div class="col-lg-9">
                <button type="submit" name="submitDtcRepairModuleGroups" value="1" class="btn btn-default">{l s='Recréer ps_module_group' mod='dtc_closy'}</button>
                <p class="help-block">{l s='Si les hooks ne s’exécutent pas (restriction ps_module_group).' mod='dtc_closy'}</p>
            </div>
        </div>
    </div>
</form>

<div class="panel">
    <h3>{l s='Logs OpenAI' mod='dtc_closy'}</h3>
    {if !empty($dtc_closy_stats.last_logs)}
        <table class="table table-striped">
            {foreach from=$dtc_closy_stats.last_logs item=log}
                <tr><td>{$log.created_at}</td><td>{$log.id_product|intval}</td><td>{$log.status}</td></tr>
            {/foreach}
        </table>
    {else}
        <p class="text-muted">{l s='Aucun appel enregistré.' mod='dtc_closy'}</p>
    {/if}
</div>