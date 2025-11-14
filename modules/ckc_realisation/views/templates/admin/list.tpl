{* modules/ckc_realisation/views/templates/admin/list.tpl *}
{extends file="helpers/layout/layout.tpl"}

{block name="content"}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-photo"></i> {$title|default:{l s='Réalisations' mod='ckc_realisation'}}
            <div class="pull-right">
                <a href="{$new_link|escape:'html'}" class="btn btn-primary">
                    <i class="icon-plus"></i> {l s='Ajouter une réalisation' mod='ckc_realisation'}
                </a>
            </div>
        </div>

        {if isset($list) && $list}
            {$list nofilter}
        {else}
            <div class="alert alert-info">
                {l s='Aucune réalisation pour le moment.' mod='ckc_realisation'}
            </div>
        {/if}
    </div>
{/block}
