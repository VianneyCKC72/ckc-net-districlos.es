<link rel="stylesheet" href="/modules/dtc_popup_gift/views/css/wheel.css">

<div id="modal" class="modal-wheel">
    <div class="modal-content-wheel">
        <img src="/modules/dtc_popup_gift/views/img/fond.jpg" class="popup-bg" alt="Fond">
        <span class="close" id="closeModal">&times;</span>
        <div class="popup-left"></div>
        <div class="popup-right">
            <canvas id="wheel" width="350" height="350"></canvas>
            <button id="spin" class="spin-bottom">FAIRE TOURNER!</button>
        </div>
    </div>
</div>

<!-- Bouton invisible pour déclencher la popup via le JS -->
<button id="openModalMini" style="display:none;"></button>

<div id="mini-wheel-container">
    <canvas id="mini-wheel" width="80" height="80"></canvas>
    <div id="mini-wheel-badge">🎁</div>
</div>

<script src="/modules/dtc_popup_gift/views/js/wheel.js"></script>