<div class="tva-attestation-block">
    <label class="switch">
        <input type="checkbox" id="tva-attestation-toggle" />
        <span class="slider round"></span>
    </label>
    <span>{l s='Réduction de TVA' d='Shop.Theme.Customdealt'}</span>

    <div id="tva-attestation-modal" class="tva-modal" style="display:none;">
        <div class="tva-modal-content">
            <span class="tva-close-modal">&times;</span>
            <h3>{l s='Conditions d’éligibilité à la TVA réduite' d='Shop.Theme.Customdealt'}</h3>
            <ul>
                <li>{l s='Logement achevé depuis plus de 2 ans' d='Shop.Theme.Customdealt'}</li>
                <li>{l s='Travaux réalisés dans ce logement' d='Shop.Theme.Customdealt'}</li>
                <li>{l s='Vous êtes propriétaire ou locataire' d='Shop.Theme.Customdealt'}</li>
            </ul>
            <a href="{$tva_attestation_url}" class="btn btn-primary dealt-tva-open-popup">
                {l s='Certifier mon éligibilité à la TVA réduite' d='Shop.Theme.Customdealt'}
            </a>
        </div>
    </div>
</div>
