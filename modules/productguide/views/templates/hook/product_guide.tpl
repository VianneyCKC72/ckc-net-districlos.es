{* Bouton déclencheur *}
<div class="product-guide-trigger">
    <button type="button" id="open-guide-btn" class="btn btn-primary">
        <img src="/modules/productguide/views/img/outils.png" alt="Outils" width="20" height="20">
        <span>Guide de mesure</span>
    </button>
</div>

{* Panneau latéral *}
<div id="product-guide-panel" class="product-guide-panel">
    <div class="guide-header">
        <h3>Guide de mesure</h3>
        <button type="button" id="close-guide-btn" class="close-guide">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <div class="guide-content">
        {$guide_content nofilter}
    </div>
</div>

{* Overlay *}
<div id="guide-overlay" class="guide-overlay"></div>

