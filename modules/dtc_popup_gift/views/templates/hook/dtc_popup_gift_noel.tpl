{**
* Modal Noël
*}

<link rel="stylesheet" href="/modules/dtc_popup_gift/views/css/wheel-noel.css">

<div id="modal-noel" class="modal-noel">
    <div class="modal-content-noel">
        <img src="/modules/dtc_popup_gift/views/img/fond-sans-flocons.png" class="popup-bg-noel" alt="Modal Noël">
        <canvas id="snow-canvas-noel"></canvas>
        
        <!-- Cadeau -->
        <div class="cadeau-container" id="cadeauContainer">
            <div class="cadeau-content" id="cadeauContent">
                <img src="/modules/dtc_popup_gift/views/img/produits-occultation.png" class="cadeau-interieur" alt="Produits" id="cadeauInterieur">
            </div>
            <div class="cadeau-wrapper">
                <img src="/modules/dtc_popup_gift/views/img/cadeau-avec-neige-sans-texte.png"
                     class="cadeau-base"
                     alt="Cadeau"
                     id="cadeauBase"
                     data-text-img="/modules/dtc_popup_gift/views/img/cadeaux-avec-texte.png">
                <img src="/modules/dtc_popup_gift/views/img/couvercle2d.png" class="cadeau-couvercle" alt="Couvercle" id="cadeauCouvercle">
            </div>
        </div>
        
        <span class="close close-noel" id="closeModalNoel">&times;</span>
    </div>
</div>

<script src="/modules/dtc_popup_gift/views/js/snow-noel.js"></script>
<script src="/modules/dtc_popup_gift/views/js/cadeau-noel.js"></script>
<script src="/modules/dtc_popup_gift/views/js/wheel-noel.js"></script>

