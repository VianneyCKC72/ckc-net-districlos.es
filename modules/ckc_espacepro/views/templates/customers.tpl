{*
 * BO Espace Pro – listing clients (multi-groupes PRO support)
 *}

<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" crossorigin="anonymous">

{if !empty($editkbis)}
    <div class="panel">{$editkbis}</div>
{/if}
{if !empty($editrib)}
    <div class="panel">{$editrib}</div>
{/if}

<div class="panel kpi-container">
    <div class="row">
        <div class="col-sm-6 col-md-3 col-lg-2">
            <div class="box-stats label-tooltip color1" title="Nombre total de clients inscrits à l'espace PRO">
                <div class="kpi-content">
                    <i class="icon icon-user"></i>
                    <span class="title">Inscrits Espace PRO</span>
                    <span class="subtitle">Depuis le début</span>
                    <span class="value">{$nbCustomers}</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 col-lg-2">
            <div class="box-stats label-tooltip color1" title="Nombre de clients inscrits ces 30 derniers jours">
                <div class="kpi-content">
                    <i class="icon icon-calendar"></i>
                    <span class="title">Inscrits Espace PRO</span>
                    <span class="subtitle">30 jours</span>
                    <span class="value">{$nbCustomersLastDay}</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 col-lg-2">
            <div class="box-stats label-tooltip color1" title="Comptes actuellement en Professionnel">
                <div class="kpi-content">
                    <i class="icon icon-briefcase"></i>
                    <span class="title">Professionnels</span>
                    <span class="subtitle">Actuels</span>
                    <span class="value">{$nbPro}</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 col-lg-2">
            <div class="box-stats label-tooltip color1" title="Comptes actuellement en Proticulier">
                <div class="kpi-content">
                    <i class="icon icon-user"></i>
                    <span class="title">Proticulier</span>
                    <span class="subtitle">Actuels</span>
                    <span class="value">{$nbProticulier}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{if !empty($customers)}
    <div class="panel">
        <h3>
            <i class="icon icon-user"></i>
            Clients professionnels ({$nbCustomers})
        </h3>

        <table id="customers" class="table table-striped table-bordered" style="width:100%">
            <thead>
            <tr>
                <th>{l s='ID'}</th>
                <th>Client</th>
                <th>Date de création</th>
                <th>Type de compte</th>
                <th>KBIS</th>
                <th>RIB</th>
            </tr>
            </thead>
            <tbody>
            {foreach $customers as $customer}

                {assign var=isPro value=in_array($customer.id_default_group, $id_groups_pro)}

                <tr class="{if $customer.default_group_name != "Proticulier"}row-pro{else}row-proticulier{/if}">
                    <td>{$customer.id_customer|intval}</td>

                    <td>
                        <div>
                            {if isset($customer.id_gender) && $customer.id_gender == 1}M.
                            {elseif isset($customer.id_gender) && $customer.id_gender == 2}Mme.
                            {elseif isset($customer.id_gender) && $customer.id_gender == 5}Société{/if}
                            <strong>{$customer.lastname|default:''|escape:'html'} {$customer.firstname|default:''|escape:'html'}</strong>
                            <div class="small-muted">{$customer.email|default:''|escape:'html'}</div>
                        </div>

                        {if isset($customer.tel) && $customer.tel}
                            <div class="small-muted"><strong>Tel :</strong> {$customer.tel|escape:'html'}</div>
                        {/if}

                        {if isset($customer.company) && $customer.company}
                            <div class="small-muted"><strong>Société :</strong> {$customer.company|escape:'html'}</div>
                        {/if}

                        {if $customer.adresse || $customer.adresse2 || $customer.cp || $customer.ville || $customer.country_name}
                            <div class="small-muted">
                                {if $customer.adresse}{$customer.adresse|escape:'html'}{/if}
                                {if $customer.adresse2}{if $customer.adresse}, {/if}{$customer.adresse2|escape:'html'}{/if}
                                {if $customer.cp || $customer.ville}
                                    {if $customer.adresse || $customer.adresse2}, {/if}
                                    {if $customer.cp}{$customer.cp|escape:'html'}{/if}{if $customer.cp && $customer.ville} {/if}{if $customer.ville}{$customer.ville|escape:'html'}{/if}
                                {/if}
                                {if $customer.country_name}
                                    {if $customer.adresse || $customer.adresse2 || $customer.cp || $customer.ville}, {/if}
                                    {$customer.country_name|escape:'html'}
                                {/if}
                            </div>
                        {/if}

                        {if isset($customer.siret) && $customer.siret}
                            <div class="small-muted"><strong>SIRET :</strong> {$customer.siret|escape:'html'}</div>
                        {/if}

                        {if isset($customer.naf) && $customer.naf}
                            <div class="small-muted"><strong>Code NAF :</strong> {$customer.naf|escape:'html'}</div>
                        {/if}
                    </td>

                    <td>{$customer.date_add|date_format:"%H:%M:%S %d/%m/%Y"}</td>

                    <td>
                        <div class="account-type">
                            <span class="status-badge {if $customer.default_group_name != "Proticulier"}badge-pro{else}badge-proti{/if}">
                              {if $isPro}
                                  {foreach $groupsSwitchable as $g}
                                      {if $g.id_group == $customer.id_default_group}
                                          {$g.name|escape:'html'}
                                      {/if}
                                  {/foreach}
                              {else}
                                  Proticulier
                              {/if}
                            </span>

                            <select
                                    class="form-control type-select js-change-group"
                                    data-id-customer="{$customer.id_customer|intval}"
                                    data-prev="{$customer.id_default_group|default:0|intval}">
                                {foreach $groupsSwitchable as $g}
                                    <option value="{$g.id_group|intval}"
                                            {if $g.id_group == $customer.id_default_group}selected{/if}>
                                        {$g.name|escape:'html'}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="small-muted">Groupe par défaut
                            : {$customer.default_group_name|default:'—'|escape:'html'}</div>
                    </td>

                    <td>
                        {if $customer.fileUpload}
                            {assign var=ext value=$customer.fileExtension|default:''|lower}
                            {if in_array($ext, ['jpg','jpeg','png'])}
                                {*                                <a href="/upload/pro_docs/{$customer.fileUpload|escape:'url'}" target="_blank"
                                                                   class="file-chip" title="Voir le KBIS">
                                                                    <img src="/upload/pro_docs/{$customer.fileUpload|escape:'url'}" alt="KBIS">
                                                                </a>*}
                                <div data-file-name="{$customer.fileUpload|escape:'url'}" data-type="image/{$ext}"
                                     class="file-chip file-download" style="cursor: pointer;">
                                    {*<img src="/upload/pro_docs/{$customer.fileUpload|escape:'url'}" alt="KBIS">*}
                                    <span class="icon"></span> <span>KBIS.{$ext}</span>
                                </div>
                            {elseif $ext == 'pdf'}
                                <div data-file-name="{$customer.fileUpload|escape:'url'}" data-type="{$ext}"
                                     class="file-chip file-download" style="cursor: pointer;">
                                    <span class="icon"></span> <span>KBIS.pdf</span>
                                </div>
                            {else}
                                <span class="small-muted">Fichier : /upload/pro_docs/{$customer.fileUpload|escape:'html'}</span>
                            {/if}
                        {else}
                            <span class="small-muted">—</span>
                        {/if}
                    </td>

                    <td>
                        {if $customer.rib}
                            {assign var=ext value=$customer.ribFileExtension|default:''|lower}
                            {if in_array($ext, ['jpg','jpeg','png'])}
                                <div data-file-name="{$customer.rib|escape:'url'}" data-type="image/{$ext}"
                                     class="file-chip file-download" style="cursor: pointer;">
                                    <span class="icon"></span> <span>RIB.{$ext}</span>
                                    {*<img src="/upload/pro_docs/{$customer.rib|escape:'url'}" alt="RIB">*}
                                </div>
                            {elseif $ext == 'pdf'}
                                <div data-file-name="{$customer.rib|escape:'url'}" data-type="{$ext}"
                                     class="file-chip file-download" style="cursor: pointer;">
                                    <span class="icon"></span> <span>RIB.pdf</span>
                                </div>
                            {else}
                                <span class="small-muted">Fichier : /upload/pro_docs/{$customer.rib|escape:'html'}</span>
                            {/if}
                        {else}
                            <span class="small-muted">—</span>
                        {/if}
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
{else}
    <div class="panel">
        <h3><i class="icon icon-user"></i> Clients professionnels</h3>
        <div class="empty-state">
            Aucun client professionnel à afficher pour le moment.
        </div>
    </div>
{/if}

<script>
    window.PRO_GROUP_IDS = {$id_groups_pro|json_encode nofilter};
    window.PROTI_GROUP_ID = {$id_group_proticulier|intval};
    var ajax_change_group_url = '{$ajax_change_group_url|escape:'javascript'}';
    var ajax_download_file_url = '{$downloadFileUrl|escape:'javascript'}';
</script>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
