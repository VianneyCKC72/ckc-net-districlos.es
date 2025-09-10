{*
 * BO Espace Pro – listing clients
 * Améliorations :
 * - KPIs plus denses
 * - Tableau clair et responsive
 * - Colonne “Type de compte” avec select (switch Particulier/Pro)
 * - Badges statut lisibles
 * - Affichage KBIS/RIB avec icône / thumbnail
 * - États vides + tooltips
 * - Hooks/JS existants compatibles
 *}

<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" crossorigin="anonymous">

<style>
    /* Touches légères pour clarifier */
    .kpi-container .kpi-content .title { font-weight:600; display:block; }
    .kpi-container .kpi-content .subtitle { font-size:12px; color:#6c868e; display:block; margin-top:2px; }
    .kpi-container .kpi-content .value { font-size:20px; font-weight:700; margin-top:4px; display:block; }
    .notification-panel { background:#F5F8FB; border-left:4px solid #2eacce; padding:12px 16px; margin-bottom:15px; }
    .notification-title { margin:0; font-weight:600; }
    #customers thead th { white-space:nowrap; }
    #customers td { vertical-align:top; }
    .small-muted { font-size:12px; color:#6c868e; }
    .badge { display:inline-block; padding:4px 8px; border-radius:14px; font-size:12px; font-weight:600; }
    .badge-success { background:#2ecc71; color:#fff; }
    .badge-warning { background:#f1c40f; color:#4a4a4a; }
    .badge-danger { background:#e74c3c; color:#fff; }
    .file-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border:1px solid #dbe5eb; border-radius:8px; }
    .file-chip img { max-height:64px; border-radius:6px; }
    .file-chip .icon { width:18px; height:18px; display:inline-block; background:#2eacce; border-radius:3px; position:relative; }
    .file-chip .icon:after { content:''; position:absolute; left:4px; top:3px; width:10px; height:12px; background:#fff; }
    .actions-col .btn{ margin:4px 0; width:100%; }
    .type-select { min-width:160px; }
    .empty-state { padding:24px; text-align:center; color:#6c868e; border:1px dashed #dbe5eb; border-radius:10px; }
</style>

{if !empty($editkbis)}
    <div class="panel">{$editkbis}</div>
{/if}
{if !empty($editrib)}
    <div class="panel">{$editrib}</div>
{/if}

{if $InactiveCount > 0}
    <div class="panel-inside notification-panel">
        <h4 class="notification-title">
            {if $InactiveCount == 1}
                Vous avez {$InactiveCount} client en attente de validation.
            {else}
                Vous avez {$InactiveCount} clients en attente de validation.
            {/if}
        </h4>
    </div>
{/if}

<div class="panel kpi-container">
    <div class="kpi-refresh">
        <button class="close refresh" type="button" onclick="refresh_kpis();">
            <i class="process-icon-refresh" style="font-size:1em"></i>
        </button>
    </div>
    <div class="row">
        <div class="col-sm-6 col-md-3 col-lg-2">
            <div class="box-stats label-tooltip color1" data-toggle="tooltip" title="Total depuis l’ouverture">
                <div class="kpi-content">
                    <i class="icon icon-user"></i>
                    <span class="title">Inscrits Espace PRO</span>
                    <span class="subtitle">Depuis le début</span>
                    <span class="value">{$nbCustomers}</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 col-lg-2">
            <div class="box-stats label-tooltip color1" data-toggle="tooltip" title="30 derniers jours">
                <div class="kpi-content">
                    <i class="icon icon-user"></i>
                    <span class="title">Inscrits Espace PRO</span>
                    <span class="subtitle">30 jours</span>
                    <span class="value">{$nbCustomersLastDay}</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 col-lg-2">
            <div class="box-stats label-tooltip color1" data-toggle="tooltip" title="En attente d’activation côté PRO">
                <div class="kpi-content">
                    <i class="icon icon-time"></i>
                    <span class="title">En attente</span>
                    <span class="subtitle">30 jours</span>
                    <span class="value">{$InactiveCountLastDay}</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 col-lg-2">
            <div class="box-stats label-tooltip color1" data-toggle="tooltip" title="Refusés côté PRO">
                <div class="kpi-content">
                    <i class="icon icon-ban-circle"></i>
                    <span class="title">Refusés</span>
                    <span class="subtitle">30 jours</span>
                    <span class="value">{$RefuseCountLastDay}</span>
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
                <th>Statut</th>
                <th style="width:150px;">Actions</th>
            </tr>
            </thead>
            <tbody>
            {foreach $customers as $customer}
                <tr class="{if $customer.active == 1}isActive{else}NotActive{/if}">
                    <td>{$customer.id_customer}</td>

                    <td>
                        <div>
                            {if $customer.id_gender == 1}M.{elseif $customer.id_gender == 2}Mme.{elseif $customer.id_gender == 5}Société{/if}
                            <strong>{$customer.lastname} {$customer.firstname}</strong>
                            <div class="small-muted">{$customer.email}</div>
                        </div>

                        {if isset($customer.tel) && $customer.tel}
                            <div class="small-muted"><strong>Tel :</strong> {$customer.tel}</div>
                        {/if}

                        {if isset($customer.company) && $customer.company}
                            <div class="small-muted"><strong>Société :</strong> {$customer.company}</div>
                        {/if}

                        {assign var=addrLine value=""}
                        {if isset($customer.adresse) && $customer.adresse}{$addrLine = $customer.adresse}{/if}
                        {if isset($customer.adresse2) && $customer.adresse2}{if $addrLine}{$addrLine = "$addrLine, "}{/if}{$addrLine = "$addrLine$customer.adresse2"}{/if}
                        {if isset($customer.cp) || isset($customer.ville)}
                            {if $addrLine}{$addrLine = "$addrLine, "}{/if}{$addrLine = "$addrLine"}{if isset($customer.cp)}{$addrLine = "$addrLine$customer.cp "}{/if}{if isset($customer.ville)}{$addrLine = "$addrLine$customer.ville"}{/if}
                        {/if}
                        {if isset($customer.country_name) && $customer.country_name}
                            {if $addrLine}{$addrLine = "$addrLine, "}{/if}{$addrLine = "$addrLine$customer.country_name"}
                        {/if}
                        {if $addrLine}
                            <div class="small-muted">{$addrLine}</div>
                        {/if}

                        {if isset($customer.siret) && $customer.siret}
                            <div class="small-muted"><strong>SIRET :</strong> {$customer.siret}</div>
                        {/if}

                        <div class="small-muted">
                            <strong>Assujetti TVA :</strong>
                            {if isset($customer.tva) && $customer.tva == 0}Non{else}Oui{/if}
                            {if isset($customer.vat_number) && $customer.vat_number}
                                — <strong>N° TVA :</strong> {$customer.vat_number}
                            {/if}
                        </div>

                        {if isset($customer.naf) && $customer.naf}
                            <div class="small-muted"><strong>Code NAF :</strong> {$customer.naf}</div>
                        {/if}
                    </td>

                    {*
                     * Type de compte – sélecteur de groupe (bind sur ajax_change_group_url)
                     * On suppose presence de $groupsSwitchable (id_group / name)
                     * et que le groupe courant = $customer.id_default_group
                     *}
                    <td>
                        <select
                                class="form-control type-select js-change-group"
                                data-id-customer="{$customer.id_customer}"
                                data-prev="{$customer.id_default_group|default:0}">
                            {foreach $groupsSwitchable as $g}
                                <option value="{$g.id_group}" {if $g.id_group == $customer.id_default_group}selected{/if}>
                                    {$g.name}
                                </option>
                            {/foreach}
                        </select>
                        <div class="small-muted">Groupe par défaut : {$customer.default_group_name|default:'—'}</div>
                    </td>

                    <td>
                        {if isset($customer.fileUpload) && $customer.fileUpload}
                            {if isset($customer.fileExtension) && ($customer.fileExtension == 'jpg' || $customer.fileExtension == 'jpeg' || $customer.fileExtension == 'png')}
                                <a href="/upload/{$customer.fileUpload}" target="_blank" class="file-chip" title="Voir le KBIS">
                                    <img src="/upload/{$customer.fileUpload}" alt="KBIS">
                                </a>
                            {elseif isset($customer.fileExtension) && $customer.fileExtension == 'pdf'}
                                <a href="/upload/{$customer.fileUpload}" target="_blank" class="file-chip" title="Ouvrir le PDF">
                                    <span class="icon"></span> <span>KBIS.pdf</span>
                                </a>
                            {else}
                                <span class="small-muted">Fichier : /upload/{$customer.fileUpload}</span>
                            {/if}
                        {else}
                            <span class="small-muted">—</span>
                        {/if}
                    </td>

                    <td>
                        {if isset($customer.rib) && $customer.rib}
                            {if isset($customer.ribFileExtension) && ($customer.ribFileExtension == 'jpg' || $customer.ribFileExtension == 'jpeg' || $customer.ribFileExtension == 'png')}
                                <a href="/upload/{$customer.rib}" target="_blank" class="file-chip" title="Voir le RIB">
                                    <img src="/upload/{$customer.rib}" alt="RIB">
                                </a>
                            {elseif isset($customer.ribFileExtension) && $customer.ribFileExtension == 'pdf'}
                                <a href="/upload/{$customer.rib}" target="_blank" class="file-chip" title="Ouvrir le PDF">
                                    <span class="icon"></span> <span>RIB.pdf</span>
                                </a>
                            {else}
                                <span class="small-muted">Fichier : /upload/{$customer.rib}</span>
                            {/if}
                        {else}
                            <span class="small-muted">—</span>
                        {/if}
                    </td>

                    <td>
                        {if isset($customer.date_add) && $customer.date_add}
                            <div class="small-muted">
                                Créé le {dateFormat date=$customer.date_add|date_format:'%Y-%m-%d' full=0}
                                à {$customer.date_add|date_format:"%H:%M"}
                            </div>
                        {/if}

                        {if isset($customer.refuse) && $customer.refuse == 1}
                            <span class="badge badge-danger">Refusé</span>
                        {else}
                            {if isset($customer.active) && $customer.active == 1}
                                <span class="badge badge-success">Actif</span>
                            {else}
                                <span class="badge badge-warning">En attente</span>
                            {/if}
                        {/if}
                    </td>

                    <td class="actions-col">
                        {if $customer.refuse == 0}
                            {if $customer.active}
                                <button class="btn btn-warning btn-desactiver" data-id="{$customer.id_customer}">Désactiver</button>
                            {else}
                                <button class="btn btn-primary btn-activer" data-id="{$customer.id_customer}">Activer</button>
                            {/if}
                        {/if}
                        {if $customer.refuse}
                            <button class="btn btn-success btn-accepte" data-id="{$customer.id_customer}">Débloquer</button>
                        {else}
                            <button class="btn btn-danger btn-refuse" data-id="{$customer.id_customer}">Refuser</button>
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
    var ajax_change_group_url = '{$ajax_change_group_url|escape:'javascript'}';
</script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
