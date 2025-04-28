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

{if !empty($configuration)}
    <div class="panel">
        {$configuration}
    </div>
{/if}

<script>
    $(document).ready(function () {
        // Initialisation de DataTable
        var currentUrl = window.location.href.split('?')[0];
        var newUrl = currentUrl + "?controller=AdminModules&configure=ckc_espacepro&token=" + token;

        // Fonction pour afficher un message et rediriger
        function showMessageAndRedirect(message, backgroundColor, customerId, isActive) {
            var $infoMessage = $('<div></div>', {
                text: message,
                css: {
                    position: 'fixed',
                    bottom: '20px',
                    left: '50%',
                    transform: 'translateX(-50%)',
                    backgroundColor: backgroundColor,
                    color: 'white',
                    padding: '10px',
                    borderRadius: '5px',
                    zIndex: 1000
                }
            }).appendTo('body');

            var currentUrl = window.location.href.split('?')[0];
            var newUrl = currentUrl + "?controller=AdminEspacePro&configure=ckc_espacepro&token=" + token + "&id_customer=" + customerId + "&active=" + isActive;

            setTimeout(function () {
                window.location.href = newUrl;
                $infoMessage.remove();
            }, 3000);
        }

        // Fonction pour obtenir la valeur d'un paramètre GET par nom
        function getParameterByName(name, url = window.location.href) {
            name = name.replace(/[\[\]]/g, '\\$&');
            var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
                results = regex.exec(url);
            if (!results) return null;
            if (!results[2]) return '';
            return decodeURIComponent(results[2].replace(/\+/g, ' '));
        }
    });
</script>
<style>
    #module_form_submit_btn .process-icon-save {
        display: none;
    }

    #module_form_submit_btn,
    #module_form_submit_btn_1 {
        color: #fff;
        background: #25b9d7;
        border-color: #25b9d7;
        text-shadow: none;
    }

    .panel-heading {
        margin-bottom: 20px;
    }

    .process-icon-save {
        display: none;
    }
    .btn {
        border: none;
        color: white;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 4px 2px;
        cursor: pointer;
        border-radius: 5px;
        transition: background-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .btn:hover {
        opacity: 0.8;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        transform: translateY(-2px);
    }

    .btn-primary {
        text-shadow: none;
        color: #fff !important;
        background: #25b9d7 !important;
        border-color: #25b9d7 !important;
    }

    .btn-alert {
        text-shadow: none;
        background: #dc3545 !important;
        border-color: #dc3545 !important;
        color: #fff !important;
    }

    /* Ajoutez également une transition pour le bouton en état actif ou focus pour améliorer l'accessibilité */
    .btn:active,
    .btn:focus {
        outline: none;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.4);
        transform: translateY(1px);
    }
</style>
