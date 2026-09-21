{**
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<link rel="stylesheet" href="/modules/dtc_popup_gift/views/css/wheel.css">
<div id="modal" class="modal-wheel">
    <div class="modal-content-wheel">
        <img src="/modules/dtc_popup_gift/views/img/fond.jpg" class="popup-bg" alt="Fond">
        <span class="close" id="closeModal">&times;</span>
        <div class="popup-left">
            <!-- (vide ou tu peux ajouter du contenu plus tard) -->
        </div>
        <div class="popup-right">
            <canvas id="wheel" width="350" height="350"></canvas>
            <button id="spin" class="spin-bottom">FAIRE TOURNER!</button>
        </div>
    </div>
</div>

<div class="banniere-je-participe">
{**<div class="cycliste-align">
  <img src="/modules/dtc_popup_gift/views/img/cycliste_blanc.webp" class="cycliste-fdj" alt="cycliste-fdj">
  <img src="/modules/dtc_popup_gift/views/img/cycliste_blanc.webp" class="cycliste-fdj" alt="cycliste-fdj";>
  <img src="/modules/dtc_popup_gift/views/img/cycliste_blanc.webp" class="cycliste-fdj" alt="cycliste-fdj";>
  </div>
</div>*}
  <span class="banniere-texte">
    Tentez votre chance, participez au tirage au sort
  </span>
  <button id="openModal" class="banniere-btn">Je participe</button>
{**<div class="cycliste-align">
  <img src="/modules/dtc_popup_gift/views/img/cycliste_blanc.webp" class="cycliste-fdj" alt="cycliste-fdj">
  <img src="/modules/dtc_popup_gift/views/img/cycliste_blanc.webp" class="cycliste-fdj" alt="cycliste-fdj";>
  <img src="/modules/dtc_popup_gift/views/img/cycliste_blanc.webp" class="cycliste-fdj" alt="cycliste-fdj";>
  </div>
</div>*}

<script src="/modules/dtc_popup_gift/views/js/wheel.js"></script>