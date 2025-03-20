{**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='RGPD - Données personnelles' mod='psgdpr'}
{/block}

{block name='page_content'}
<div class="container">
    <section class="page_content">
        <div class="col-xs-12 psgdprinfo17">
            <h2>{l s='Accéder à mes données' mod='psgdpr'}</h2>
            <p>{l s='À tout moment, vous avez le droit de récupérer les données que vous avez fournies sur notre site. Cliquez sur "Obtenir mes données" pour télécharger automatiquement une copie de vos données personnelles sur un fichier pdf ou csv.' mod='psgdpr'}</p>
            <a id="exportDataToCsv" class="btn btn-primary psgdprgetdatabtn17" target="_blank" href="{$psgdpr_csv_controller|escape:'htmlall':'UTF-8'}">{l s='OBTENIR MES DONNÉES EN CSV' mod='psgdpr'}</a>
            <a id="exportDataToPdf" class="btn btn-primary psgdprgetdatabtn17" target="_blank" href="{$psgdpr_pdf_controller|escape:'htmlall':'UTF-8'}">{l s='OBTENIR MES DONNÉES EN PDF' mod='psgdpr'}</a>
        </div>
        <div class="col-xs-12 psgdprinfo17">
            <h2>{l s='Demandes de rectification et/ou d’effacement' mod='psgdpr'}</h2>
            <p>{l s='Vous avez le droit de modifier toutes les informations personnelles figurant sur la page "Mon compte". Pour toute autre demande concernant la rectification et/ou l’effacement de vos données personnelles, veuillez nous contacter via notre' mod='psgdpr'} <a href="{$psgdpr_contactUrl|escape:'htmlall':'UTF-8'}">{l s='page contact' mod='psgdpr'}</a>. {l s='Nous examinerons votre demande et y répondrons dès que possible.' mod='psgdpr'}</p>
        </div>
    </section>
</div>
{literal}
<script type="text/javascript">
    var psgdpr_front_controller = "{/literal}{$psgdpr_front_controller|escape:'htmlall':'UTF-8'}{literal}";
    var psgdpr_id_customer = "{/literal}{$psgdpr_front_controller|escape:'htmlall':'UTF-8'}{literal}";
    var psgdpr_ps_version = "{/literal}{$psgdpr_ps_version|escape:'htmlall':'UTF-8'}{literal}";
</script>
{/literal}
{/block}
