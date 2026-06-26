{* Lien Espace PRO dans la barre d’icônes *}
{if isset($pro.env) && $pro.env == 1}
    {* Tu es déjà sur le site PRO : bouton "Documents" *}
    <a href="{Context::getContext()->link->getBaseLink(1)}" class="ico btn-espace-pro" rel="nofollow">
        <svg width="22" height="20" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3.83347 0C2.59109 0 1.49585 0.840662 1.17564 2.04102L0.0748041 6.16992C-0.233113 7.32435 0.442452 8.4839 1.49798 8.86719V15.75C1.49798 16.9838 2.51446 18 3.74849 18H8.74962C8.94857 18 9.13937 17.921 9.28005 17.7803C9.42073 17.6397 9.49977 17.4489 9.49979 17.25V12.9648C9.49979 12.4027 9.93775 11.9648 10.5 11.9648C11.0623 11.9648 11.5002 12.4027 11.5002 12.9648V17.25C11.5003 17.4489 11.5793 17.6397 11.72 17.7803C11.8607 17.921 12.0515 18 12.2504 18H17.2515C18.4856 18 19.5021 16.9838 19.5021 15.75V8.86621C20.5573 8.48279 21.2331 7.32422 20.9252 6.16992L19.8244 2.04102C19.5042 0.840662 18.4089 0 17.1666 0H3.83347ZM3.83347 1.5H17.1666C17.7366 1.5 18.228 1.87709 18.3748 2.42773L19.4757 6.55664C19.6076 7.0512 19.2619 7.5 18.7509 7.5H14.7744C14.3719 7.5 13.9961 7.30743 13.7605 6.98145L11.6946 4.12207C11.119 3.32649 9.88058 3.32578 9.30541 4.12207L7.24049 6.98145C7.00513 7.30718 6.62841 7.5 6.22659 7.5H2.24912C1.73738 7.5 1.39244 7.0512 1.52435 6.55664L2.62519 2.42773C2.77208 1.87709 3.26343 1.5 3.83347 1.5ZM10.5 5.03125L12.5444 7.86035C13.0611 8.57536 13.8916 9 14.7744 9H18.0017V15.75C18.0017 16.1733 17.6749 16.5 17.2515 16.5H13.0006V12.9648C13.0006 11.593 11.8722 10.4648 10.5 10.4648C9.12784 10.4648 7.99945 11.593 7.99945 12.9648V16.5H3.74849C3.32514 16.5 2.99832 16.1733 2.99832 15.75V9H6.22659C7.10905 9 7.93977 8.57562 8.45658 7.86035L10.5 5.03125ZM5.49888 11C4.94626 11 4.49866 11.4475 4.49866 12V13C4.49866 13.5525 4.94626 14 5.49888 14C6.05151 14 6.49911 13.5525 6.49911 13V12C6.49911 11.4475 6.05151 11 5.49888 11ZM15.5011 11C14.9485 11 14.5009 11.4475 14.5009 12V13C14.5009 13.5525 14.9485 14 15.5011 14C16.0538 14 16.5014 13.5525 16.5014 13V12C16.5014 11.4475 16.0538 11 15.5011 11Z" fill="black"/>
        </svg>
        <span class="text">{l s='Particuliers' d='Shop.Theme.Custommodules'}</span>
    </a>
{*    Todo : En attente du client !*}
{*    <a href="/guides-et-fiches-techniques-produits" class="ico btn-espace-pro" rel="nofollow">*}
{*        <img src="/themes/classic/assets/img/ico-espace-pro.svg" alt="Espace pro" width="20" height="19">*}
{*        <span class="text">Documents</span>*}
{*    </a>*}
{elseif !$logged}
    <a href="{$pro.url_domain}" class="ico btn-espace-pro" rel="nofollow">
        <img src="/themes/classic/assets/img/ico-espace-pro.svg" alt="{l s='Espace pro' d='Shop.Theme.Custommodules'}" width="20" height="19">
        <span class="text">{l s='Espace Pro' d='Shop.Theme.Custommodules'}</span>
    </a>
{/if}

<a href="/module/opartdevis/simplequotation" class="ico btn-devis">
    <img src="/themes/classic/assets/img/ico-devis.svg" alt="{l s='Page de de demande de Devis' d='Shop.Theme.Custommodules'}" width="21" height="21">
    <span class="text">{l s='Devis' d='Shop.Theme.Custommodules'}</span>
</a>
<a href="{$link->getCMSLink(11)}" class="ico btn-magasin">
    <img src="/themes/classic/assets/img/ico-magasins.svg" alt="{l s='Consultez nos magasins' d='Shop.Theme.Custommodules'}" width="20" height="20">
    <span class="text">{l s='Magasins' d='Shop.Theme.Custommodules'}</span>
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
                <img src="/themes/classic/assets/img/ico-compte.svg" alt="{l s='Connexion' d='Shop.Theme.Custommodules'}">
                <span class="hidden-sm-down">{l s='Mon compte' d='Shop.Theme.Custommodules'}</span>
            </a>
        {elseif isset($pro.env) && $pro.env == 1 && Context::getContext()->shop->id == 2}
            <a
                    href="{$pro.url_domain}"
                    title="{l s='Log in to your customer account' d='Shop.Theme.Customeraccount'}"
                    class="ico"
                    rel="nofollow"
            >
                <img src="/themes/classic/assets/img/ico-compte.svg" alt="{l s='Connexion' d='Shop.Theme.Custommodules'}">
                <span class="hidden-sm-down">{l s='Sign in' d='Shop.Theme.Actions'}</span>
            </a>

        {else}
            <a
                    href="{$urls.pages.authentication}?back={$urls.current_url|urlencode}"
                    title="{l s='Log in to your customer account' d='Shop.Theme.Customeraccount'}"
                    class="ico"
                    rel="nofollow"
            >
                <img src="/themes/classic/assets/img/ico-compte.svg" alt="{l s='Connexion' d='Shop.Theme.Custommodules'}">
                <span class="hidden-sm-down">{l s='Sign in' d='Shop.Theme.Actions'}</span>
            </a>
        {/if}
    </div>
</div>
