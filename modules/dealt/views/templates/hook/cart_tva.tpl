<div class="tva-attestation-block">
    <label class="switch">
        <input type="checkbox" id="tva-attestation-toggle" />
        <span class="slider round"></span>
    </label>
    <span>Réduction de TVA</span>

    <div id="tva-attestation-modal" class="tva-modal" style="display:none;">
        <div class="tva-modal-content">
            <span class="tva-close-modal">&times;</span>
            <h3>Conditions d’éligibilité à la TVA réduite</h3>
            <ul>
                <li>Logement achevé depuis plus de 2 ans</li>
                <li>Travaux réalisés dans ce logement</li>
                <li>Vous êtes propriétaire ou locataire</li>
            </ul>
            <a href="{$tva_attestation_url}" class="btn btn-primary dealt-tva-open-popup">
                Certifier mon éligibilité à la TVA réduite
            </a>
        </div>
    </div>
</div>
