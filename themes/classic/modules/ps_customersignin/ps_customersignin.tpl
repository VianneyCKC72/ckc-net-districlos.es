{* Lien Espace PRO dans la barre d’icônes *}
{if isset($pro.env) && $pro.env == 1}
    {* Tu es déjà sur le site PRO : bouton "Documents" *}
    <a href="/guides-et-fiches-techniques-produits" class="ico btn-espace-pro" rel="nofollow">
        <img src="/themes/classic/assets/img/ico-espace-pro.svg" alt="Espace pro" width="20" height="19">
        <span class="text">Documents</span>
    </a>
{elseif !$logged}
    <a href="{$pro.url_domain}" class="ico btn-espace-pro" rel="nofollow">
        <img src="/themes/classic/assets/img/ico-espace-pro.svg" alt="Espace pro" width="20" height="19">
        <span class="text">Espace Pro</span>
    </a>
{/if}

<a href="/module/opartdevis/simplequotation" class="ico btn-devis">
    <img src="/themes/classic/assets/img/ico-devis.svg" alt="Page de de demande de Devis" width="21" height="21">
    <span class="text">Devis</span>
</a>
<a href="/content/11-nos-magasins-de-cloture" class="ico btn-magasin">
    <img src="/themes/classic/assets/img/ico-magasins.svg" alt="Consultez nos magasins" width="20" height="20">
    <span class="text">Magasins</span>
</a>

<div id="_desktop_user_info">
    <div class="user-info">
        {if $logged}
                <a
                        class="ico"
                        href="{$urls.pages.my_account}"
                        title="{l s='View my customer account' d='Shop.Theme.Customeraccount'}"
                        rel="nofollow"
                >
                    <img src="/themes/classic/assets/img/ico-compte.svg" alt="Connexion">
                    <span class="hidden-sm-down">Mon compte</span>
                </a>
        {else}
                <a
                        href="{$urls.pages.authentication}?back={$urls.current_url|urlencode}"
                        title="{l s='Log in to your customer account' d='Shop.Theme.Customeraccount'}"
                        class="ico"
                        rel="nofollow"
                >
                    <img src="/themes/classic/assets/img/ico-compte.svg" alt="Connexion">
                    <span class="hidden-sm-down">{l s='Sign in' d='Shop.Theme.Actions'}</span>
                </a>
        {/if}
    </div>
</div>
