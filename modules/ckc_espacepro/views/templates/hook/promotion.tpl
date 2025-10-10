{*
* Template pour afficher une promotion spéciale aux utilisateurs professionnels
*}

<div class="ckc-promo-banner ckc-promo-{$promo_type}" id="ckc-promo-banner">
    <div class="ckc-promo-container">
        <div class="ckc-promo-content">
            <div class="ckc-promo-icon">
                {if $promo_type == 'pro'}
                    <i class="material-icons">business_center</i>
                {else}
                    <i class="material-icons">star</i>
                {/if}
            </div>
            <div class="ckc-promo-text">
                <h3 class="ckc-promo-title">
                    {l s='Bonjour %s !' sprintf=[$customer_firstname] mod='ckc_espacepro'} 
                    <span class="ckc-promo-badge">{$promo_title}</span>
                </h3>
                <p class="ckc-promo-message">{$promo_message}</p>
                <div class="ckc-promo-code-container">
                    <span class="ckc-promo-code-label">{l s='Code promo :' mod='ckc_espacepro'}</span>
                    <span class="ckc-promo-code" data-code="{$promo_code}">{$promo_code}</span>
                    <button class="ckc-promo-copy-btn" onclick="copyPromoCode('{$promo_code}')" title="{l s='Copier le code' mod='ckc_espacepro'}">
                        <i class="material-icons">content_copy</i>
                    </button>
                </div>
            </div>
            <div class="ckc-promo-discount">
                <span class="ckc-promo-percent">{$promo_discount}</span>
                <span class="ckc-promo-off">{l s='de réduction' mod='ckc_espacepro'}</span>
            </div>
        </div>
        <button class="ckc-promo-close" onclick="closePromoBanner()" title="{l s='Fermer' mod='ckc_espacepro'}">
            <i class="material-icons">close</i>
        </button>
    </div>
</div>

<script>
function copyPromoCode(code) {
    navigator.clipboard.writeText(code).then(function() {
        // Afficher un feedback visuel
        const btn = document.querySelector('.ckc-promo-copy-btn');
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<i class="material-icons">check</i>';
        btn.classList.add('copied');
        
        setTimeout(function() {
            btn.innerHTML = originalIcon;
            btn.classList.remove('copied');
        }, 2000);
        
        // Optionnel : afficher une notification
        if (typeof prestashop !== 'undefined' && prestashop.emit) {
            prestashop.emit('showSuccessMessage', {
                message: '{l s='Code promo copié !' mod='ckc_espacepro'}'
            });
        }
    }).catch(function(err) {
        console.error('Erreur lors de la copie : ', err);
        // Fallback pour les navigateurs plus anciens
        const textArea = document.createElement('textarea');
        textArea.value = code;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
    });
}

function closePromoBanner() {
    const banner = document.getElementById('ckc-promo-banner');
    banner.style.animation = 'slideUp 0.3s ease-out forwards';
    setTimeout(function() {
        banner.style.display = 'none';
        // Sauvegarder dans le localStorage pour ne pas réafficher pendant la session
        localStorage.setItem('ckc_promo_closed', Date.now());
    }, 300);
}

// Vérifier si la bannière a été fermée récemment (dans les 24h)
document.addEventListener('DOMContentLoaded', function() {
    const closedTime = localStorage.getItem('ckc_promo_closed');
    if (closedTime) {
        const now = Date.now();
        const dayInMs = 24 * 60 * 60 * 1000; // 24 heures
        if (now - closedTime < dayInMs) {
            document.getElementById('ckc-promo-banner').style.display = 'none';
        }
    }
});
</script>
