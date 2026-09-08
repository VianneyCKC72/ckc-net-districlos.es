{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
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
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

<div id="custom-text">
  {$cms_infos.text nofilter}
</div>

<div class="hero-home">
  <div class="swiper swiper__home-banner">
    <div class="swiper-wrapper">
      {for $i=1 to 2}
        <div class="swiper-slide">
          <a href="#">
            <img class="img-cover rounded-4" src="https://picsum.photos/{rand(300, 600)}/{rand(300, 600)}" alt="" />
          </a>
        </div>
      {/for }
    </div>
  </div>
  <div>
    <a href="#" class="card__bg-image no-gradient">
      <div class="content">
        <h2 class="h2">{l s='Configurez votre clôture, très simplement.' d='Shop.Theme.Custommodules'}</h2>
        <p>{capture assign="ckc_home_config"}{l s='Créez votre clôture en 5 minutes grâce au [1]configurateur[/1] pour grillage rigide ou pour grillage souple.' sprintf=['[1]' => '<b>', '[/1]' => '</b>'] d='Shop.Theme.Custommodules'}{/capture}{$ckc_home_config nofilter}
        </p>
        <p class="link-arrow">
          {l s='Je configure ma clôture' d='Shop.Theme.Custommodules'}
          <span class="btn-primary-arrow">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              class="lucide lucide-move-up-right">
              <path d="M13 5H19V11" />
              <path d="M19 5L5 19" />
            </svg>
          </span>
        </p>
      </div>
      <div class="thumbnail">
        <img src="https://picsum.photos/{rand(300, 600)}/{rand(300, 600)}" alt="" />
      </div>
    </a>
  </div>
</div>

<br>
<br>
<br>

<div class="banner-services stack-row__cards">
  <div class="service">
    <img src="/themes/classic/assets/img/services/manufacturing.svg" loading="lazy" alt="">
    <p>{capture assign="ckc_fab"}{l s='Fabrication%br%européenne' sprintf=['%br%' => '<br>'] d='Shop.Theme.Custommodules'}{/capture}{$ckc_fab nofilter}</p>
  </div>
  <div class="service">
    <img src="/themes/classic/assets/img/services/paiement.svg" loading="lazy" alt="">
    <p>{l s='Facilités de paiement' d='Shop.Theme.Custommodules'}</p>
  </div>
  <div class="service">
    <img src="/themes/classic/assets/img/services/guarantee.svg" loading="lazy" alt="">
    <p>{capture assign="ckc_gar"}{l s='Garantie 10 ans%br%anti-corrosion' sprintf=['%br%' => '<br>'] d='Shop.Theme.Custommodules'}{/capture}{$ckc_gar nofilter}</p>
  </div>
  <div class="service">
    <img src="/themes/classic/assets/img/services/delivery.svg" loading="lazy" alt="">
    <p>{capture assign="ckc_liv"}{l s='Livraison à domicile%br%ou retrait en magasin' sprintf=['%br%' => '<br>'] d='Shop.Theme.Custommodules'}{/capture}{$ckc_liv nofilter}</p>
  </div>
</div>

<br>
<br>
<br>

<div class="banner-primary">
  <p><b>{l s='Payez en plusieurs fois' d='Shop.Theme.Custommodules'}<span style="margin-inline: 10px"><img
          src="/themes/classic/assets/img/services/multiple-paiement.svg" loading="lazy" alt=""></span>{l s='en carte bancaire sans frais' d='Shop.Theme.Custommodules'}</b></p>
  <p>{l s='Un crédit vous engage et doit être remboursé. vérifiez vos capacités de remboursement avant de vous engager.' d='Shop.Theme.Customcatalog'}</p>
</div>

<br>
<br>
<br>

<div class="stack-row__cards">
  {for $i=1 to 2}
    <a href="#" class="card__bg-image">
      <div class="content">
        <h2 class="h2">{l s='Ces produits existent aussi en kit !' d='Shop.Theme.Custommodules'}</h2>
        <p>{l s='Choisissez votre kit idéal en fonction du type de pose souhaité. Ils contiennent tout le nécessaire pour une clôture réussie !' d='Shop.Theme.Custommodules'}</p>
        <p class="link-arrow">
          {l s='Voir les kits' d='Shop.Theme.Custommodules'}
          <span class="btn-primary-arrow">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              class="lucide lucide-move-up-right">
              <path d="M13 5H19V11" />
              <path d="M19 5L5 19" />
            </svg>
          </span>
        </p>
      </div>
      <div class="thumbnail">
        <img src="https://picsum.photos/{rand(300, 600)}/{rand(300, 600)}" alt="" />
      </div>
    </a>
  {/for}
</div>

<br>
<br>
<br>

<div class="banner__newsletter">
  <div class="col-text">
    <h2 class="h2">{l s='Newsletter' d='Shop.Theme.Custommodules'}</h2>
    <p>{l s='Inscrivez-vous à la newsletter Districlos pour rester informés de nos nouveaux produits, bons plans et faire le plein d’inspirations !' d='Shop.Theme.Custommodules'}</p>
    <form action="" class="form-newsletter" method="post">
      <div class="input-newsletter">
        <input type="email" value="" name="email" class="email" required aria-label="Email">
        <button class="btn btn-primary-light d-none d-md-block">{l s='Je m\'inscris' d='Shop.Theme.Custommodules'}</button>
      </div>
      <label class="input-checkbox">{l s='J’accepte de recevoir d’autres communications de Districlos. Vous pourrez vous désabonner d’un simple clic !' d='Shop.Theme.Custommodules'}
        <input type="checkbox">
        <span class="checkmark"></span>
      </label>
      <button class="btn btn-primary-light d-md-none">{l s='Je m\'inscris' d='Shop.Theme.Custommodules'}</button>
    </form>
  </div>
  <div class="col-img">
    <img class="img-cover" src="https://picsum.photos/{rand(300, 600)}/{rand(300, 600)}" alt="" />
  </div>
</div>

<br>
<br>
<br>

<div class="stack-row__cards scroll-snap-mobile">
  {for $i=1 to 4}
    <a href="#" class="card__category card__thumbnail">
      <div class="content">
        <h3 class="h3">{l s='Grillages rigides' d='Shop.Theme.Custommodules'}</h3>
        <span class="btn-primary-arrow">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-move-up-right">
            <path d="M13 5H19V11" />
            <path d="M19 5L5 19" />
          </svg>
        </span>
      </div>
      <div class="thumbnail">
        <img src="https://picsum.photos/{rand(300, 600)}/{rand(300, 600)}" alt="" />
      </div>
    </a>
  {/for}
</div>

<br>
<br>
<br>

<div class="stack-row__cards">
  {for $i=1 to 4}
    <a href="#" class="card__category">
      <div class="content">
        <h3 class="h3">{l s='Grillages rigides' d='Shop.Theme.Custommodules'}</h3>
        <span class="btn-primary-arrow">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-move-up-right">
            <path d="M13 5H19V11" />
            <path d="M19 5L5 19" />
          </svg>
        </span>
      </div>
    </a>
  {/for}
</div>

<br>
<br>
<br>
