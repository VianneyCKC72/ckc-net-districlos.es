{* modules/ckc_realisation/views/templates/admin/partials/_list.tpl *}
<div class="table-responsive">
    <table class="table table-striped ckc-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>{l s='Aperçu' mod=$module_name}</th>
            <th>{l s='Client' mod=$module_name}</th>
            <th>{l s='Groupe' mod=$module_name}</th>
            <th>{l s='Produit' mod=$module_name}</th>
            <th>{l s='Mots-clés' mod=$module_name}</th>
            <th>{l s='Titre' mod=$module_name}</th>
            <th>{l s='Statut' mod=$module_name}</th>
            <th>{l s='Ajout' mod=$module_name}</th>
            <th class="text-right">{l s='Actions' mod=$module_name}</th>
        </tr>
        </thead>
        <tbody>
        {if !$rows}
            <tr>
                <td colspan="8">
                    <div class="alert alert-info mb-0">{l s='Aucune réalisation.' mod=$module_name}</div>
                </td>
            </tr>
        {else}
            {foreach $rows as $r}
                {assign var=thumb value=''}{if $r.filename}{assign var=thumb value=$file_base_url|cat:'thumb_'|cat:$r.filename}{/if}
                {assign var=urlApprove value=$base_url|cat:'&ckc_action=approve&id_realisation='|cat:$r.id_realisation}
                {assign var=urlReject  value=$base_url|cat:'&ckc_action=reject&id_realisation='|cat:$r.id_realisation}
                {assign var=urlPending value=$base_url|cat:'&ckc_action=pending&id_realisation='|cat:$r.id_realisation}
                {assign var=urlEdit    value=$base_url|cat:'&ckc_action=edit&id_realisation='|cat:$r.id_realisation}
                {assign var=urlDelete  value=$base_url|cat:'&ckc_action=delete&id_realisation='|cat:$r.id_realisation}

                <tr>
                    <td>{$r.id_realisation|intval}</td>
                    {assign var=thumb value=$file_base_url|cat:'thumb_'|cat:$r.filename}
                    {assign var=full  value=$file_base_url|cat:$r.filename}
                    <td class="ckc-thumb-cell">
                        {if $r.filename}
                            <img src="{$thumb|escape:'html'}"
                                 alt=""
                                 onerror="this.onerror=null;this.src='{$full|escape:'html'}';"/>
                        {/if}
                    </td>

                    <td>{$r.customer_name|escape:'html'}</td>

                    <td>
                        type = {*{$r.customer_type}*}
                        {if $r.customer_type=='pro'}{l s='Professionnel' mod=$module_name}
                        {elseif $r.customer_type=='proticulier'}{l s='Proticulier' mod=$module_name}
                        {else}-{/if}
                    </td>
                    <td>
                        {if $r.id_product}
                            #{$r.id_product|intval}{if $r.product_name} — {$r.product_name|escape:'html'}{/if}
                        {else}-{/if}
                    </td>
                    <td>{$r.keywords|default:'-'|escape:'html'}</td>


                    <td>{$r.title|default:''|escape:'html'}</td>
                    <td>
                        {assign var=label value='label-default'}
                        {if $r.status=='pending'}{assign var=label value='label-warning'}{/if}
                        {if $r.status=='approved'}{assign var=label value='label-success'}{/if}
                        {if $r.status=='rejected'}{assign var=label value='label-danger'}{/if}
                        <span class="label {$label}">
  {$statuses[$r.status]|default:$r.status|escape:'html'}
</span>
                    </td>
                    <td>{$r.date_add|date_format:"%d/%m/%Y %H:%M"}</td>
                    <td class="ckc-actions-cell text-right">
{*                        <a class="btn btn-warning btn-sm" href="{$urlPending|escape:'html'}" title="{l s='Mettre en attente' mod=$module_name}">*}
{*                            <i class="icon-time"></i><span> {l s='En attente' mod=$module_name}</span>*}
{*                        </a>*}
                        <a class="btn btn-success btn-sm" href="{$urlApprove|escape:'html'}" title="{l s='Approuver' mod=$module_name}">
                            <i class="icon-check"></i><span> {l s='Approuver' mod=$module_name}</span>
                        </a>
                        <a class="btn btn-primary btn-sm" href="{$urlEdit|escape:'html'}" title="{l s='Modifier' mod=$module_name}">
                            <i class="icon-pencil"></i><span> {l s='Modifier' mod=$module_name}</span>
                        </a>
                        <a class="btn btn-danger btn-sm" href="{$urlReject|escape:'html'}" title="{l s='Rejeter' mod=$module_name}">
                            <i class="icon-remove"></i><span> {l s='Rejeter' mod=$module_name}</span>
                        </a>
                        <a class="btn btn-danger btn-sm" href="{$urlDelete|escape:'html'}"
                           title="{l s='Supprimer' mod=$module_name}"
                           onclick="return confirm('{l s='Supprimer définitivement ?' mod=$module_name}');">
                            <i class="icon-trash"></i><span> {l s='Supprimer' mod=$module_name}</span>
                        </a>
                    </td>

                </tr>
            {/foreach}
        {/if}
        </tbody>
    </table>
</div>
