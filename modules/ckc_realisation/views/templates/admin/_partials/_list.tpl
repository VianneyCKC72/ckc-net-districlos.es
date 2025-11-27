<div class="table-responsive">
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>{l s='Aperçu' mod=$module_name}</th>
            <th>{l s='Magasin' mod=$module_name}</th>
            <th>{l s='Client' mod=$module_name}</th>
            <th>{l s='Titre' mod=$module_name}</th>
            <th>{l s='Statut' mod=$module_name}</th>
            <th>{l s='Ajout' mod=$module_name}</th>
            <th class="text-right">{l s='Actions' mod=$module_name}</th>
        </tr>
        </thead>
        <tbody>
        {if !$rows}
            <tr><td colspan="8"><div class="alert alert-info" style="margin:0">{l s='Aucune réalisation.' mod=$module_name}</div></td></tr>
        {else}
            {foreach $rows as $r}
                {assign var=thumb value=''}
                {if $r.filename}{assign var=thumb value=$file_base_url|cat:'thumb_'|cat:$r.filename}{/if}
                {assign var=urlApprove value=$base_url|cat:'&action=approve&id_realisation='|cat:$r.id_realisation}
                {assign var=urlReject  value=$base_url|cat:'&action=reject&id_realisation='|cat:$r.id_realisation}
                {assign var=urlPending value=$base_url|cat:'&action=pending&id_realisation='|cat:$r.id_realisation}
                {assign var=urlEdit    value=$base_url|cat:'&action=edit&id_realisation='|cat:$r.id_realisation}
                {assign var=urlDelete  value=$base_url|cat:'&action=delete&id_realisation='|cat:$r.id_realisation}
                <tr>
                    <td>{$r.id_realisation|intval}</td>
                    <td>{if $thumb}<img src="{$thumb|escape:'html'}" style="height:48px">{/if}</td>
                    <td>{$r.id_store|intval}</td>
                    <td>{if $r.id_customer}{$r.id_customer|intval}{else}-{/if}</td>
                    <td>{$r.title|default:''|escape:'html'}</td>
                    <td>
                        {assign var=label value='default'}
                        {if $r.status=='pending'}{assign var=label value='warning'}{/if}
                        {if $r.status=='approved'}{assign var=label value='success'}{/if}
                        {if $r.status=='rejected'}{assign var=label value='danger'}{/if}
                        <span class="label label-{$label}">{$r.status|escape:'html'}</span>
                    </td>
                    <td>{$r.date_add|escape:'html'}</td>
                    <td class="text-right">
                        <a class="btn btn-xs btn-default" href="{$urlPending|escape:'html'}" title="{l s='Mettre en attente' mod=$module_name}">
                            <i class="icon-time"></i>
                        </a>
                        <a class="btn btn-xs btn-success" href="{$urlApprove|escape:'html'}" title="{l s='Approuver' mod=$module_name}">
                            <i class="icon-check"></i>
                        </a>
                        <a class="btn btn-xs btn-warning" href="{$urlReject|escape:'html'}" title="{l s='Rejeter' mod=$module_name}">
                            <i class="icon-remove"></i>
                        </a>
                        <a class="btn btn-xs btn-primary" href="{$urlEdit|escape:'html'}" title="{l s='Éditer' mod=$module_name}">
                            <i class="icon-pencil"></i>
                        </a>
                        <a class="btn btn-xs btn-danger" href="{$urlDelete|escape:'html'}"
                           onclick="return confirm('{l s='Supprimer définitivement ?' mod=$module_name}')" title="{l s='Supprimer' mod=$module_name}">
                            <i class="icon-trash"></i>
                        </a>
                    </td>
                </tr>
            {/foreach}
        {/if}
        </tbody>
    </table>
</div>
<!-- End of file _list.tpl -->