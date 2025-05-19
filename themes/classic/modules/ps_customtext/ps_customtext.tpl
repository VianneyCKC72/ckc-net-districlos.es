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
        <h2 class="h2">Configurez votre clôture, très simplement.</h2>
        <p>Créez votre clôture en 5 minutes grâce au <b>configurateur</b> pour grillage rigide ou pour grillage souple.
        </p>
        <p class="link-arrow">
          Je configure ma clôture
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
    <p>Fabrication<br>européenne</p>
  </div>
  <div class="service">
    <img src="/themes/classic/assets/img/services/paiement.svg" loading="lazy" alt="">
    <p>Facilités de paiement</p>
  </div>
  <div class="service">
    <img src="/themes/classic/assets/img/services/guarantee.svg" loading="lazy" alt="">
    <p>Garantie 10 ans<br>anti-corrosion</p>
  </div>
  <div class="service">
    <img src="/themes/classic/assets/img/services/delivery.svg" loading="lazy" alt="">
    <p>Livraison à domicile<br>ou retrait en magasin</p>
  </div>
</div>

<br>
<br>
<br>

<div class="banner-primary">
  <p><b>Payez en plusieurs fois<span style="margin-inline: 10px"><img
          src="/themes/classic/assets/img/services/multiple-paiement.svg" loading="lazy" alt=""></span>en carte bancaire
      sans frais</b></p>
  <p>Un crédit vous engage et doit être remboursé. vérifiez vos capacités de remboursement avant de vous engager.</p>
</div>

<br>
<br>
<br>

<div class="stack-row__cards">
  {for $i=1 to 2}
    <a href="#" class="card__bg-image">
      <div class="content">
        <h2 class="h2">Ces produits existent aussi en kit !</h2>
        <p>Choisissez votre kit idéal en fonction du type de pose souhaité.
          Ils contiennent tout le nécessaire pour une clôture réussie !</p>
        <p class="link-arrow">
          Voir les kits
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
    <h2 class="h2">Newsletter</h2>
    <p>Inscrivez-vous à la newsletter Districlos pour rester informés de nos nouveaux produits, bons plans et faire le
      plein d’inspirations !</p>
    <form action="" class="form-newsletter" method="post">
      <div class="input-newsletter">
        <input type="email" value="" name="email" class="email" required aria-label="Email">
        <button class="btn btn-primary-light d-none d-md-block">Je m'inscris</button>
      </div>
      <label class="input-checkbox">J’accepte de recevoir d’autres communications de Districlos. Vous pourrez vous
        désabonner d’un simple clic !
        <input type="checkbox">
        <span class="checkmark"></span>
      </label>
      <button class="btn btn-primary-light d-md-none">Je m'inscris</button>
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
        <h3 class="h3">Grillages rigides</h3>
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
        <h3 class="h3">Grillages rigides</h3>
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
