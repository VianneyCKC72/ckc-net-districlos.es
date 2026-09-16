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
                            <img src="/modules/blockreassurance/views/img/img_perso/paiement_home.png" alt="{l s='Paiement 100% sécurisé ' d='Shop.Theme.Custommodules'}" class="block-reassurance-img unveil-loaded">
                            <span class="h6">{l s='Paiement 100% sécurisé ' d='Shop.Theme.Custommodules'}</span>
                        </div>
                    </li>
                    <li>
                        <div class="block-reassurance-item">
                            <img src="/modules/blockreassurance/views/img/img_perso/livraison_home.png" alt="{l s='Livraison à domicile ou retrait en magasin ' d='Shop.Theme.Custommodules'}" class="block-reassurance-img unveil-loaded">
                            <span class="h6">{l s='Livraison à domicile ou retrait en magasin ' d='Shop.Theme.Custommodules'}</span>
                        </div>
                    </li>
                </div>
                <div class="block-reassurance-separator"></div>
                <div class="block-reassurance-row dis_flex flex_row s_center">
                    <li>
                        <div class="block-reassurance-item">
                            <img src="/modules/blockreassurance/views/img/img_perso/fab_home.png" alt="{l s='Fabrication européenne ' d='Shop.Theme.Custommodules'}" class="block-reassurance-img unveil-loaded">
                            <span class="h6">{l s='Fabrication européenne ' d='Shop.Theme.Custommodules'}</span>
                        </div>
                    </li>
                    <li>
                        <div class="block-reassurance-item">
                            <img src="/modules/blockreassurance/views/img/img_perso/garantie_home.png" alt="{l s='Garantie 10 ans anti-corrosion ' d='Shop.Theme.Custommodules'}" class="block-reassurance-img unveil-loaded">
                            <span class="h6">{l s='Garantie 10 ans anti-corrosion ' d='Shop.Theme.Custommodules'}</span>
                        </div>
                    </li>
                </div>
            </ul>
        </div>
        <div class="form_uperia_container">
            <h2>{capture assign="ckc_devis30"}{l s='Demandez votre devis en [1]30 secondes[/1]' sprintf=['[1]' => '<span class="highlight-text">', '[/1]' => '</span>'] d='Shop.Theme.Custommodules'}{/capture}{$ckc_devis30 nofilter}</h2>
            <div data-tf-live="01JCG29ZK4099ETJK1C6R2SKAF"></div>
            <script src="//embed.typeform.com/next/embed.js"></script>
        </div>
        <div class="bloc_promo_devis">
            <p class="txt_center"><strong>{l s='Promotions !' d='Shop.Theme.Custommodules'}</strong></p>
            <p>{l s='-5 % pour les commandes de -1000€ (avec le code promo FLEUR5)' d='Shop.Theme.Custommodules'}<br></p>
            <p>{l s='-7 % pour les commandes de 1000 à 2999€. (avec le code promo FLEUR7)' d='Shop.Theme.Custommodules'}<br></p>
            <p>{l s='-10% pour les commandes de 3000€ et +. (avec le code promo FLEUR10)' d='Shop.Theme.Custommodules'}<br></p>
            <p>{l s='-50% sur le moteur CARDIN sur les Packs Portails + Portillons' d='Shop.Theme.Custommodules'}<br></p>
            <p>{l s='-5% sur les kits de grillage rigide' d='Shop.Theme.Custommodules'}<br></p>
            <p>{l s='-5% sur la gamme Alu FLEX' d='Shop.Theme.Custommodules'}<br></p>
            {if $env_pro}
                <p>{capture assign="ckc_liv450"}{l s='Livraison offerte dès [1]450€[/1]' sprintf=['[1]' => '<strong>', '[/1]' => '</strong>'] d='Shop.Theme.Custommodules'}{/capture}{$ckc_liv450 nofilter}</p>
            {else}
                <p>{capture assign="ckc_liv1499"}{l s='Livraison offerte dès [1]1499€[/1]' sprintf=['[1]' => '<strong>', '[/1]' => '</strong>'] d='Shop.Theme.Custommodules'}{/capture}{$ckc_liv1499 nofilter}</p>
            {/if}
        </div>
    </div>
{/block}