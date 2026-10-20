{**
 * CKC Vianney — libellés zipcode Dealt (main.js lit dealtZipI18n)
 *}
{l s='Service indisponible à cette adresse.' d='Shop.Theme.Customdealt' assign='dealt_zip_unavailable'}
{l s='Veuillez saisir un code postal.' d='Shop.Theme.Customdealt' assign='dealt_zip_empty'}
{l s='Informations manquantes.' d='Shop.Theme.Customdealt' assign='dealt_zip_missing_info'}
{l s='Configuration manquante.' d='Shop.Theme.Customdealt' assign='dealt_zip_missing_config'}
{l s='Vérification en cours...' d='Shop.Theme.Customdealt' assign='dealt_zip_checking'}
{l s='Erreur lors de la vérification.' d='Shop.Theme.Customdealt' assign='dealt_zip_error'}
<script>
  var dealtServiceUrl = {$link->getCMSLink(656)|json_encode nofilter};
  var dealtZipI18n = {
    unavailable: {$dealt_zip_unavailable|json_encode nofilter},
    empty: {$dealt_zip_empty|json_encode nofilter},
    missingInfo: {$dealt_zip_missing_info|json_encode nofilter},
    missingConfig: {$dealt_zip_missing_config|json_encode nofilter},
    checking: {$dealt_zip_checking|json_encode nofilter},
    error: {$dealt_zip_error|json_encode nofilter}
  };
</script>
