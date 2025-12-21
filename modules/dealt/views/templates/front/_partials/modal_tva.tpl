{* modules/dealt/views/templates/front/_partials/modal_tva.tpl *}
<div id="dealt-tva-modal" class="dealt-modal">
    <div class="dealt-modal-content">
        <span id="dealt-tva-close" class="dealt-close">&times;</span>

        <p class="h4">Conditions TVA réduite</p>
        <p class="h2">Êtes-vous éligible à la TVA à 10&nbsp;% ?</p>
        <hr />

        <div class="dealt-conditions-list">
            <ul>
                <li>Le logement doit être achevé depuis plus de 2 ans (maison ou appartement, résidence principale ou secondaire).</li>
                <li>Les travaux doivent être réalisés par un professionnel (pose facturée).</li>
                <li>L’adresse d’intervention doit être identique à l’adresse de livraison.</li>
                <li>Le service concerne l’amélioration, la transformation, l’aménagement ou l’entretien du logement (hors construction neuve et extension).</li>
                <li>Le bénéficiaire doit être particulier ou copropriété (hors locaux exclusivement professionnels).</li>
            </ul>
        </div>
        <form id="dealt-tva-form" method="post" action="{$tva_attestation_url|escape:'html':'UTF-8'}">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="eligible" value="1" id="tvaEligible" required />
                <label class="form-check-label" for="tvaEligible">
                    J’ai lu et j’atteste remplir toutes les conditions d’éligibilité à la TVA réduite à 10 %.
                </label>
            </div>
            <div id="tva-error-msg" style="display:none;color:red;font-size:13px;margin-top:5px;">
                Vous devez cocher cette case pour continuer.
            </div>

            <input type="hidden" name="submit_tva_attestation" value="1" />
            <input type="hidden" name="token" value="{$tva_token|default:''|escape:'html':'UTF-8'}" />

            <button type="submit" class="dealt-btn-primary" id="tva-submit-btn" style="margin-top: 12px;">
                Valider mon éligibilité
            </button>
        </form>


    </div>
</div>
