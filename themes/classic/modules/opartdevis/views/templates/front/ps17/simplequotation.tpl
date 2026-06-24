{extends file='page.tpl'}
{block name='head_seo_title'}
    {l s='Obtenez un devis de clôture, grillage, portail avec Districlos' d='Shop.Theme.Custommodules'}
{/block}
{block name="page_content"}
    {capture name=path}{l s='Quotation request' mod='opartdevis'}{/capture}

    {if isset($errors)}
        {include file='_partials/form-errors.tpl' errors=$errors}
    {/if}

    {if isset($confirmation)}
        <p class="alert alert-success">{l s='Your request has been successfully sent to our team.' mod='opartdevis'}</p>
    {/if}
    <div class="loader-container">
        <div class="loaderProduct"></div>
    </div>
    {hook h='displayPromoDevisText'}
    <h1>{l s='Demandez votre devis en 30 secondes' d='Shop.Theme.Custommodules'}</h1>
    <div id="iframe_devis" class="dis_flex p_btwn s_center">
        <div data-tf-live="01JCG29ZK4099ETJK1C6R2SKAF"></div><script src="//embed.typeform.com/next/embed.js"></script>
    </div>
{/block}
