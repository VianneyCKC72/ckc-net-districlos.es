
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

{if $espace_pro == 1}
  <h1 class="text-center">
    {block name='page_title'}
      {*            <span class="text-center">Bienvenue dans votre espace professionnel !</span>*}
    {/block}
  </h1>
{/if}

{block name='page_title'}
  {l s='Log in to your account' d='Shop.Theme.Customeraccount'}
{/block}

{block name='page_content'}
  {if $espace_pro == 1}
    <p class="text-center sous-texte-auth">Bienvenue dans votre Espace Professionnel dédié aux experts du bâtiment,
      aux jardiniers
      et aux paysagistes passionnés. Chez Districlos, nous comprenons l'importance d'un partenariat solide pour
      mener à bien vos projets. C'est pourquoi nous avons créé cet espace exclusif, où vous bénéficiez de tarifs
      avantageux et de remises toute l'année sur nos clôtures, grillages et portails de qualité supérieure.</p>
  {/if}
  {block name='login_form_container'}
    {if $espace_pro == 1}<div class="row">{/if}
    <section class="login-form{if $espace_pro == 1} col-4{/if}">
      {if $espace_pro != 1}
        <div class="no-account">
          <a href="{$urls.pages.my_account}" data-link-action="display-register-form">
            {l s='Se connecter' d='Shop.Theme.Global'}</a>
          <a href="{$urls.pages.register}" data-link-action="display-register-form">
            {l s='S\'inscrire' d='Shop.Theme.Global'}
          </a>
        </div>
      {else}
        <p class="text-center titreForm"><strong>Je me connecte à mon espace</strong></p>
      {/if}

      {render file='customer/_partials/login-form.tpl' ui=$login_form}
    </section>
    {if $espace_pro == 1}
      <div class="col-8 contenair-img-auth">
        <figure>
          <img class="img-connect" alt="Authentification Districlos" src="/img/cms/auth/img-espace-pro.jpeg">
        </figure>
        <div class="avis">
          <div class="texte">
            <p>"Mon expérience avec Districlos a été géniale, et je suis convaincu que toute collaboration
              sera synonyme de succès et de satisfaction"</p>
            <br>
            <p class="light">Thomas - Paysagiste</p>
          </div>
        </div>
        <div class="block-bas">
          <p class="green-dark"><strong>100€ offert dès 1 000€ d'achat HT</strong></p>
          <p class="text-white">pour toute première commande en tant que professionnel !</p>
        </div>
      </div>
      </div>
    {/if}

    {block name='display_after_login_form'}
      {hook h='displayCustomerLoginFormAfter'}
    {/block}

    {if $espace_pro == 1}
      <div class="steps-container">
        <div class="step">
          <div class="step-number">1</div>
          <div class="step-text">Je remplis le formulaire<br>d'inscription</div>
        </div>
        <div class="step">
          <div class="step-number">2</div>
          <div class="step-text">Mon compte est validé<br> par les équipes Districlos</div>
        </div>
        <div class="step">
          <div class="step-number">3</div>
          <div class="step-text">Je bénéficie de <strong>100€ offert</strong><br><strong>dès 1000€
              d'achat!</strong></div>
        </div>
      </div>

      {include file='customer/_partials/custom-reassurance-ep.tpl'}

      {include file='customer/_partials/authentification-eo-explication.tpl'}

      <div class="row full-width margin-bottom-80">
        <div class="col-sm-12">
          <div class="container">
            <h3 style="margin-bottom:0!important" class="title_descr"><strong>Vous avez du talent !</strong></h3>
          </div>
          <div class="contenair-img">
            <img src="/img/cms/auth/occultante.JPG" alt="occultante">
            <img src="/img/cms/auth/panneau_rigide.jpg" alt="panneau rigide">
            <img src="/img/cms/auth/pente.jpg" alt="pente">
            <img src="/img/cms/auth/portail.jpg" alt="portail">
            <img src="/img/cms/auth/toile.jpg" alt="toile">
          </div>
        </div>
      </div>
    {/if}
  {/block}
{/block}

