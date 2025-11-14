{*
 * BO Espace Pro – listing clients (sélecteur seul + code couleur lignes)
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
                <th>Type de compte</th>
                <th>KBIS</th>
                <th>RIB</th>
            </tr>
            </thead>
            <tbody>
            {* debug marker v3 *}
            {foreach $customers as $customer}
                <tr class="{if $customer.id_default_group == $id_group_pro}row-pro{else}row-proticulier{/if}">
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

                        {* Adresse en une ligne, sans assign, sans implode *}
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

                    <td>
                        <div class="account-type">
        <span class="status-badge {if $customer.id_default_group == $id_group_pro}badge-pro{else}badge-proti{/if}">
          {if $customer.id_default_group == $id_group_pro}Professionnel{else}Proticulier{/if}
        </span>
                            <select
                                    class="form-control type-select js-change-group"
                                    data-id-customer="{$customer.id_customer|intval}"
                                    data-prev="{$customer.id_default_group|default:0|intval}">
                                {foreach $groupsSwitchable as $g}
                                    <option value="{$g.id_group|intval}" {if $g.id_group == $customer.id_default_group}selected{/if}>
                                        {$g.name|escape:'html'}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="small-muted">Groupe par défaut : {$customer.default_group_name|default:'—'|escape:'html'}</div>
                    </td>

                    <td>
                        {if isset($customer.fileUpload) && $customer.fileUpload}
                            {if isset($customer.fileExtension) && ($customer.fileExtension == 'jpg' || $customer.fileExtension == 'jpeg' || $customer.fileExtension == 'png')}
                                <a href="/upload/{$customer.fileUpload|escape:'url'}" target="_blank" class="file-chip" title="Voir le KBIS">
                                    <img src="/upload/{$customer.fileUpload|escape:'url'}" alt="KBIS">
                                </a>
                            {elseif isset($customer.fileExtension) && $customer.fileExtension == 'pdf'}
                                <a href="/upload/{$customer.fileUpload|escape:'url'}" target="_blank" class="file-chip" title="Ouvrir le PDF">
                                    <span class="icon"></span> <span>KBIS.pdf</span>
                                </a>
                            {else}
                                <span class="small-muted">Fichier : /upload/{$customer.fileUpload|escape:'html'}</span>
                            {/if}
                        {else}
                            <span class="small-muted">—</span>
                        {/if}
                    </td>

                    <td>
                        {if isset($customer.rib) && $customer.rib}
                            {if isset($customer.ribFileExtension) && ($customer.ribFileExtension == 'jpg' || $customer.ribFileExtension == 'jpeg' || $customer.ribFileExtension == 'png')}
                                <a href="/upload/{$customer.rib|escape:'url'}" target="_blank" class="file-chip" title="Voir le RIB">
                                    <img src="/upload/{$customer.rib|escape:'url'}" alt="RIB">
                                </a>
                            {elseif isset($customer.ribFileExtension) && $customer.ribFileExtension == 'pdf'}
                                <a href="/upload/{$customer.rib|escape:'url'}" target="_blank" class="file-chip" title="Ouvrir le PDF">
                                    <span class="icon"></span> <span>RIB.pdf</span>
                                </a>
                            {else}
                                <span class="small-muted">Fichier : /upload/{$customer.rib|escape:'html'}</span>
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
    window.PRO_GROUP_ID = {$id_group_pro|intval};
    window.PROTI_GROUP_ID = {$id_group_proticulier|intval};
    var ajax_change_group_url = '{$ajax_change_group_url|escape:'javascript'}';
    document.body.setAttribute('data-pro-id', '{$id_group_pro|intval}');
</script>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
