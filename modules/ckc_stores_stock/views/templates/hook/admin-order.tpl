{*
* Module Multi shipping Web In Color
*
*  @author    Web In Color - addons@webincolor.fr
*  @version 1.0
*  @uses Prestashop modules
*  @since 1.0 - novembre 2014
*  @package Wic Multi Shipping
*  @copyright Copyright &copy; 2014, Web In Color
*  @license   http://www.webincolor.fr
*}

{if version_compare($smarty.const._PS_VERSION_,'1.6','>=')}
    <div class="card">
        <h3 class="card-header">
            <i class="material-icons">send</i>
            {l s='Envoyer vers Divalto' mod='wic_disabledfrontmultishop'}
        </h3>
        <div class="card-body">
            {if $ignoreDivalto}
                <div class="alert alert-danger" role="alert">Le statut actuel de cette commande ne permet pas de l'envoyer à Divalto.</div>
            {else}
                <form action="{$currentIndex|escape:'htmlall':'UTF-8'}&amp;id_order={$id_order|escape:'htmlall':'UTF-8'}&amp;vieworder&amp;token={$smarty.get.token|escape:'htmlall':'UTF-8'}" method="post">
                    {if $transferDivalto}
                        <div class="alert alert-success" role="alert">{l s='La commande a été envoyée vers Divalto avec succès.' mod='wic_disabledfrontmultishop'}</div>
                    {/if}
                    <div class="alert alert-info">
                        Utilisez cette fonctionnalité pour renvoyer manuellement cette commande vers Divalto.
                    </div>
                    <div>
                        <button type="submit" name="submitOrderToDivalto" id="submitOrderToDivalto" class="btn btn-primary pull-left">
                            {l s='Envoyer la commande vers Divalto' mod='wic_disabledfrontmultishop'}
                        </button>
                    </div>
                </form>
            {/if}
        </div>
    </div>
{/if}
