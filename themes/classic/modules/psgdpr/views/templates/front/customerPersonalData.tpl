{**
 * Theme override - GDPR personal data page (i18n via Shop.Theme.Custommodules)
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='RGPD - Données personnelles' d='Shop.Theme.Custommodules'}
{/block}

{block name='page_content'}
<div class="container">
    <section class="page_content">
        <div class="col-xs-12 psgdprinfo17">
            <h2>{l s='Accès à mes données' d='Shop.Theme.Custommodules'}</h2>
            <p>{l s='À tout moment, vous avez le droit de récupérer les données que vous avez fournies à notre site. Cliquez sur « Obtenir mes données » pour télécharger automatiquement une copie de vos données personnelles au format PDF ou CSV.' d='Shop.Theme.Custommodules'}</p>
            <a id="exportDataToCsv" class="btn btn-primary psgdprgetdatabtn17" target="_blank" href="{$psgdpr_csv_controller|escape:'htmlall':'UTF-8'}">{l s='OBTENIR MES DONNÉES EN CSV' d='Shop.Theme.Custommodules'}</a>
            <a id="exportDataToPdf" class="btn btn-primary psgdprgetdatabtn17" target="_blank" href="{$psgdpr_pdf_controller|escape:'htmlall':'UTF-8'}">{l s='OBTENIR MES DONNÉES EN PDF' d='Shop.Theme.Custommodules'}</a>
        </div>
        <div class="col-xs-12 psgdprinfo17">
            <h2>{l s='Demandes de rectification et d\'effacement' d='Shop.Theme.Custommodules'}</h2>
            <p>{l s='Vous avez le droit de modifier toutes les informations personnelles présentes dans la page « Mon compte ». Pour toute autre demande concernant la rectification et/ou l\'effacement de vos données personnelles, veuillez nous contacter via notre' d='Shop.Theme.Custommodules'} <a href="{$psgdpr_contactUrl|escape:'htmlall':'UTF-8'}">{l s='page de contact' d='Shop.Theme.Custommodules'}</a>. {l s='Nous examinerons votre demande et vous répondrons dans les meilleurs délais.' d='Shop.Theme.Custommodules'}</p>
        </div>
        <a href="{$urls.pages.identity}"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-left-icon lucide-move-left"><path d="M6 8L2 12L6 16"/><path d="M2 12H22"/></svg> {l s='Mon compte' d='Shop.Theme.Custommodules'}</a>

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
