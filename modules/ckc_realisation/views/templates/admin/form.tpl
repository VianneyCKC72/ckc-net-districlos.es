{* modules/ckc_realisation/views/templates/admin/form.tpl *}
{extends file="helpers/layout/layout.tpl"}

{block name="content"}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-pencil"></i> {$title|default:{l s='Réalisation' mod='ckc_realisation'}}
            <div class="pull-right">
                <a class="btn btn-default" href="{$back_link|escape:'html'}">
                    <i class="icon-arrow-left"></i> {l s='Retour à la liste' mod='ckc_realisation'}
                </a>
            </div>
        </div>

        {if isset($help) && $help}
            <div class="alert alert-info">
                <i class="icon-info-circle"></i> {$help}
            </div>
        {/if}

        {if isset($form) && $form}
            {$form nofilter}
        {else}
            <div class="alert alert-warning">
                {l s='Formulaire non disponible.' mod='ckc_realisation'}
            </div>
        {/if}
    </div>
{/block}
