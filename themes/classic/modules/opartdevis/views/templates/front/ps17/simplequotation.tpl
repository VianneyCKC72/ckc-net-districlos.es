{extends file='page.tpl'}
{block name='head_seo_title'}
    Obtenez un devis de clôture, grillage, portail avec Districlos
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

    <h1>Demandez votre devis en 30 secondes</h1>
    <div id="iframe_devis" class="dis_flex p_btwn s_center">
        <iframe width="100%" height="600px" src="https://form.typeform.com/to/MZNpWY65?typeform-embed-id=4768577811830069&typeform-emb[…]ion=next&embed-opacity=100&typeform-embed-handles-redirect=1"></iframe>
    </div>
{/block}