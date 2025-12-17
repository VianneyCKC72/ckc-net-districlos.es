<!-- Modal service Dealt -->
<div id="dealt-service-modal" class="dealt-modal" style="display:none;">
    <div class="dealt-modal-content">
        <span id="dealt-close-modal" class="dealt-close">&times;</span>
        <p class="h4">Bonne nouvelle ! Nous intervenons dans votre ville.</p>
        <p class="h2">Veuillez vérifier les prérequis et conditions de réalisation de la prestation.</p>
        <hr/>
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
            Ajouter le produit et le service au panier
        </button>
    </div>
</div>
