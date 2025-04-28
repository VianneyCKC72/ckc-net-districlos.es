{*
* 2007-2023 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2023 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" integrity=
"sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3"
      crossorigin="anonymous">

{if !empty($editkbis)}
    <div class="panel">
        {$editkbis}
    </div>
{/if}

{if !empty($editrib)}
    <div class="panel">
        {$editrib}
    </div>
{/if}
{if !empty($customers)}
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
                <div id="box-conversion-rate" data-toggle="tooltip" class="box-stats label-tooltip color1">
                    <div class="kpi-content">
                        <i class="icon icon-user"></i>
                        <span class="title">Inscription à l'espace PRO</span>
                        <span class="subtitle">Depuis le début</span>
                        <span class="value">{$nbCustomers}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3 col-lg-2">
                <div id="box-conversion-rate" data-toggle="tooltip" class="box-stats label-tooltip color1">
                    <div class="kpi-content">
                        <i class="icon icon-user"></i>
                        <span class="title">Inscription à l'espace PRO</span>
                        <span class="subtitle">30 Jours</span>
                        <span class="value">{$nbCustomersLastDay}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3 col-lg-2">
                <div id="box-conversion-rate" data-toggle="tooltip" class="box-stats label-tooltip color1">
                    <div class="kpi-content">
                        <i class="icon icon-user"></i>
                        <span class="title">En attente de validation</span>
                        <span class="subtitle">30 Jours</span>
                        <span class="value">{$InactiveCountLastDay}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3 col-lg-2">
                <div id="box-conversion-rate" data-toggle="tooltip" class="box-stats label-tooltip color1">
                    <div class="kpi-content">
                        <i class="icon icon-user"></i>
                        <span class="title">Client refusé</span>
                        <span class="subtitle">30 Jours</span>
                        <span class="value">{$RefuseCountLastDay}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <h3>
            <i class="icon icon-user"></i>
            Clients professionnelles ({$nbCustomers})
        </h3>

        <table id="customers" class="table table-striped table-bordered" style="width:100%">
            <thead>
            <tr>
                <th>{l s='ID'}</th>
                <th>Clients</th>
                <th>KBIS</th>
                <th>RIB</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            {foreach $customers as $customer}
                <tr class="{if $customer.active == 1}isActive{else}NotActive{/if}">
                    <td>{$customer.id_customer}</td>
                    <td>
                        {if $customer.id_gender == 1}
                            M.
                        {elseif $customer.id_gender == 2}
                            Mme.
                        {elseif $customer.id_gender == 5}
                            Société
                            <br>
                        {/if}

                        {$customer.lastname} {$customer.firstname} <br>
                        <strong>Email:</strong> {$customer.email}<br>
                        {if $customer.tel}
                            <strong>Tel:</strong>
                            {$customer.tel}
                            <br>
                        {/if}

                        {if $customer.company}
                            <strong>Société: </strong>
                            {$customer.company}
                            <br>
                        {/if}
                        {$customer.adresse}{if $customer.adresse2} {$customer.adresse2}{/if}
                        , {$customer.cp} {$customer.ville}
                        {if $customer.country_name}, {$customer.country_name} {/if}
                        {if $customer.siret}
                            <br>
                            <strong>N° Siret:</strong>
                            {$customer.siret}
                        {/if}
                        <br><strong>TVA:</strong> {if $customer.tva == 0}Non{else}Oui{/if}
                        {if $customer.vat_number}
                            <br>
                            <strong>N° TVA:</strong>
                            {$customer.vat_number}
                        {/if}
                        {if $customer.naf}
                            <br>
                            <strong>Code NAF:</strong>
                            {$customer.naf}
                        {/if}
                    </td>
                    <td>
                        {if $customer.fileUpload}
                            {if $customer.fileExtension == 'jpg' or $customer.fileExtension == 'jpeg' or $customer.fileExtension == 'png'}
                                <a href="/upload/{$customer.fileUpload}" target="_blank">
                                    <img src="/upload/{$customer.fileUpload}" alt="Image"
                                         style="width: 120px; height: auto;">
                                </a>
                            {elseif $customer.fileExtension == 'pdf'}
                                <a download href="/upload/{$customer.fileUpload}">Ouvrir le PDF</a>
                            {else}
                                Fichier: upload/{$customer.fileUpload}
                            {/if}
                        {/if}
                    </td>
                    <td>
                        {if $customer.rib}
                            {if $customer.ribFileExtension == 'jpg' or $customer.ribFileExtension == 'jpeg' or $customer.ribFileExtension == 'png'}
                                <a href="/upload/{$customer.rib}" target="_blank">
                                    <img src="/upload/{$customer.rib}" alt="Image"
                                         style="width: 120px; height: auto;">
                                </a>
                            {elseif $customer.ribFileExtension == 'pdf'}
                                <a download href="/upload/{$customer.rib}">Ouvrir le PDF</a>
                            {else}
                                Fichier dans: upload/{$customer.rib}
                            {/if}
                        {/if}
                    </td>
                    <td>
                        Créé le {dateFormat date=$customer.date_add|date_format:'%Y-%m-%d' full=0}
                        à {$customer.date_add|date_format:"%H:%M"}
                        <br><br>
                        {if $customer.refuse == 1}
                            <span class="label label-refunded">Refusé</span>
                        {else}
                            {if $customer.active == 1}
                                <span class="label label-success">Actif</span>
                            {else}
                                <span class="label label-warning">Inactif</span>
                            {/if}
                        {/if}

                    </td>
                    <td>
                        {if $customer.refuse == 0}
                            {if $customer.active}
                                {* Bouton Désactiver *}
                                <button class="btn btn-warning btn-desactiver" data-id="{$customer.id_customer}">
                                    Désactiver
                                </button>
                            {else}
                                {* Bouton Activer *}
                                <button class="btn btn-primary btn-activer" data-id="{$customer.id_customer}">
                                    Activer
                                </button>
                            {/if}
                        {/if}
                        <br><br>
                        {if $customer.refuse}
                            {* Bouton Désactiver *}
                            <button class="btn btn-success btn-accepte" data-id="{$customer.id_customer}">
                                Débloquer
                            </button>
                        {else}
                            {* Bouton Activer *}
                            <button class="btn btn-alert btn-refuse" data-id="{$customer.id_customer}">
                                Refuser
                            </button>
                        {/if}
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
{/if}


<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>