<!-- Modal service Dealt -->
<div id="dealt-service-modal" class="dealt-modal" style="display:none;">
    <div class="dealt-modal-content">
        <span id="dealt-close-modal" class="dealt-close">&times;</span>
        <p class="h4">{l s='Bonne nouvelle ! Nous intervenons dans votre ville.' d='Shop.Theme.Customdealt'}</p>
        <p class="h2">{l s='Veuillez vérifier les prérequis et conditions de réalisation de la prestation :' d='Shop.Theme.Customdealt'} </p>
        <hr />
        <ul class="dealt-conditions-list">
            {*            {if !empty($dealt_customer_commercial_description)}*}
                {*                {$dealt_customer_commercial_description nofilter}*}
            {*            {/if}*}

            {if !empty($dealt_expert_requirements_description)}
                {$dealt_expert_requirements_description nofilter}
            {/if}

            {*            {if !empty($dealt_eligibility_conditions)}*}

                {*                {$dealt_eligibility_conditions|escape:'html':'UTF-8'}*}

            {*            {/if}*}
        </ul>

        <button type="button" id="dealt-add-to-cart" class="btn btn-primary dealt-btn-primary">
            {l s='Ajouter le produit et le service au panier' d='Shop.Theme.Customdealt'}
        </button>
    </div>
</div>