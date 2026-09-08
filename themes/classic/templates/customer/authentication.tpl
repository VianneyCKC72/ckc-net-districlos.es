
{**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{extends file='page.tpl'}

{if $pro.env == 1}
  <h1 class="text-center">
    {block name='page_title'}
      {*            <span class="text-center">Bienvenue dans votre espace professionnel !</span>*}
    {/block}
  </h1>
{/if}

{block name='page_content'}
  {if $pro.env == 1}
    <p class="text-center sous-texte-auth">{l s='Bienvenue dans votre Espace Professionnel dédié aux experts du bâtiment, aux jardiniers et aux paysagistes passionnés. Chez Districlos, nous comprenons l\'importance d\'un partenariat solide pour mener à bien vos projets. C\'est pourquoi nous avons créé cet espace exclusif, où vous bénéficiez de tarifs avantageux et de remises toute l\'année sur nos clôtures, grillages et portails de qualité supérieure.' d='Shop.Theme.Customcustomer'}</p>
  {/if}
  {block name='login_form_container'}
    {if $pro.env == 1}<div class="row">{/if}
    <section class="login-form{if $pro.env == 1} col-6{/if}">
      {if $pro.env == 1}
        <p class="text-center titreForm"><strong>{l s='Je me connecte à mon espace' d='Shop.Theme.Customcustomer'}</strong></p>
      {/if}

      {render file='customer/_partials/login-form.tpl' ui=$login_form  show_title=false}
    </section>

    {if $pro.env == 1}
      <div class="col-6 contenair-img-auth">
        <figure>
          <picture>
            <source srcset="/img/cms/auth/img-espace-pro3.webp" type="image/webp">
            <img class="img-connect" alt="Authentification Districlos" src="/img/cms/auth/img-espace-pro3.jpg">
          </picture>
        </figure>

        <div class="avis">
          <div class="texte">
            <p>{l s='"Mon expérience avec Districlos a été géniale, et je suis convaincu que toute collaboration sera synonyme de succès et de satisfaction"' d='Shop.Theme.Customcustomer'}</p>
            <br>
            <p class="light">{l s='Thomas - Paysagiste' d='Shop.Theme.Customcustomer'}</p>
          </div>
        </div>
        <div class="block-bas">
          <p class="green-dark"><strong>{l s='100€ offert dès 1 000€ d\'achat HT' d='Shop.Theme.Customcustomer'}</strong></p>
          <p class="text-white">{l s='pour toute première commande en tant que professionnel !' d='Shop.Theme.Customcustomer'}</p>
        </div>
      </div>
      </div>
    {/if}

    {block name='display_after_login_form'}
      {hook h='displayCustomerLoginFormAfter'}
    {/block}

    {if $pro.env == 1}
      <div class="steps-container">
        <div class="step">
          <div class="step-number">1</div>
          <div class="step-text">{capture assign="ckc_step1"}{l s='Je remplis le formulaire%br%d\'inscription' sprintf=['%br%' => '<br>'] d='Shop.Theme.Customcustomer'}{/capture}{$ckc_step1 nofilter}</div>
        </div>
        <div class="step">
          <div class="step-number">2</div>
          <div class="step-text">{capture assign="ckc_step2"}{l s='Mon compte est validé%br% par les équipes Districlos' sprintf=['%br%' => '<br>'] d='Shop.Theme.Customcustomer'}{/capture}{$ckc_step2 nofilter}</div>
        </div>
        <div class="step">
          <div class="step-number">3</div>
          <div class="step-text">{capture assign="ckc_step3"}{l s='Je bénéficie de [1]100€ offert[/1]%br%[2]dès 1000€ d\'achat![/2]' sprintf=['[1]' => '<strong>', '[/1]' => '</strong>', '[2]' => '<strong>', '[/2]' => '</strong>', '%br%' => '<br>'] d='Shop.Theme.Customcustomer'}{/capture}{$ckc_step3 nofilter}</div>
        </div>
      </div>

      {include file='customer/_partials/custom-reassurance-ep.tpl'}

      {include file='customer/_partials/authentification-eo-explication.tpl'}

      <div class="row full-width margin-bottom-80">
        <div class="col-sm-12">
          <div class="container">
            <h3 style="margin-bottom:0!important" class="title_descr"><strong>{l s='Vous avez du talent !' d='Shop.Theme.Customcustomer'}</strong></h3>
          </div>
          <div class="contenair-img">
            <picture><source srcset="/img/cms/auth/occultante.webp" type="image/webp"><img src="/img/cms/auth/occultante.JPG" alt="occultante" loading="lazy"></picture>
            <picture><source srcset="/img/cms/auth/panneau_rigide.webp" type="image/webp"><img src="/img/cms/auth/panneau_rigide.jpg" alt="panneau rigide" loading="lazy"></picture>
            <picture><source srcset="/img/cms/auth/pente.webp" type="image/webp"><img src="/img/cms/auth/pente.jpg" alt="pente" loading="lazy"></picture>
            <picture><source srcset="/img/cms/auth/portail.webp" type="image/webp"><img src="/img/cms/auth/portail.jpg" alt="portail" loading="lazy"></picture>
            <picture><source srcset="/img/cms/auth/toile.webp" type="image/webp"><img src="/img/cms/auth/toile.jpg" alt="toile" loading="lazy"></picture>
          </div>
        </div>
      </div>
    {/if}
  {/block}
{/block}

