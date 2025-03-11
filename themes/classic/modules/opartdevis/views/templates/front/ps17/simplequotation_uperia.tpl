{**
* @category Prestashop
* @category Module
* @author Olivier CLEMENCE <manit4c@gmail.com>
* @copyright  Op'art
* @license Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
**}

{extends file='page.tpl'}
{block name='head_seo_title'}
    {$meta_title}
{/block}
{block name="page_content"}
    {capture name=path}{l s='Quotation request' mod='opartdevis'}{/capture}

    {if isset($errors)}
        {include file='_partials/form-errors.tpl' errors=$errors}
    {/if}

    {if isset($confirmation)}
        <p class="alert alert-success">{l s='Your request has been successfully sent to our team.' mod='opartdevis'}</p>
    {/if}

    <div id="iframe_devis" class="dis_flex p_btwn s_center">
        <div id="block-reassurance" class="shadow_box">
            <ul class="dis_flex flex_cln">
                <div class="block-reassurance-row dis_flex flex_row s_center">
                    <li>
                        <div class="block-reassurance-item">
                            <img src="/modules/blockreassurance/views/img/img_perso/paiement_home.png" alt="Paiement 100% sécurisé " class="block-reassurance-img unveil-loaded">
                            <span class="h6">Paiement 100% sécurisé </span>
                        </div>
                    </li>
                    <li>
                        <div class="block-reassurance-item">
                            <img src="/modules/blockreassurance/views/img/img_perso/livraison_home.png" alt="Livraison à domicile ou retrait en magasin " class="block-reassurance-img unveil-loaded">
                            <span class="h6">Livraison à domicile ou retrait en magasin </span>
                        </div>
                    </li>
                </div>
                <div class="block-reassurance-separator"></div>
                <div class="block-reassurance-row dis_flex flex_row s_center">
                    <li>
                        <div class="block-reassurance-item">
                            <img src="/modules/blockreassurance/views/img/img_perso/fab_home.png" alt="Fabrication européenne " class="block-reassurance-img unveil-loaded">
                            <span class="h6">Fabrication européenne </span>
                        </div>
                    </li>
                    <li>
                        <div class="block-reassurance-item">
                            <img src="/modules/blockreassurance/views/img/img_perso/garantie_home.png" alt="Garantie 10 ans anti-corrosion " class="block-reassurance-img unveil-loaded">
                            <span class="h6">Garantie 10 ans anti-corrosion </span>
                        </div>
                    </li>
                </div>
            </ul>
        </div>
        <div class="form_uperia_container">
            <h2>Demandez votre devis en <span class="highlight-text">30 secondes</span></h2>
            <div data-tf-live="01JCG29ZK4099ETJK1C6R2SKAF"></div>
            <script src="//embed.typeform.com/next/embed.js"></script>
        </div>
        <div class="bloc_promo_devis">
            <p class="txt_center"><strong>Promotions !</strong></p>
            <p>-5 % pour les commandes de -1000€ (avec le code promo FLEUR5)<br></p>
            <p>-7 % pour les commandes de 1000 à 2999€. (avec le code promo FLEUR7)<br></p>
            <p>-10% pour les commandes de 3000€ et +. (avec le code promo FLEUR10)<br></p>
            <p>-50% sur le moteur CARDIN sur les Packs Portails + Portillons<br></p>
            <p>-5% sur les kits de grillage rigide<br></p>
            <p>-5% sur la gamme Alu FLEX<br></p>
            {if $env_pro}
                <p>Livraison offerte dès <strong>450€</strong></p>
            {else}
                <p>Livraison offerte dès <strong>1499€</strong></p>
            {/if}
        </div>
    </div>
{/block}