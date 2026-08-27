<div class="dealt-offer-box dealt-promo-box">
    <div class="dealt-offer-content d-flex align-items-center">
        <div class="dealt-toggle-text" style="width: 70%;">
            <div class="dealt-title-row">
                <span class="dealt-offer-title">
                    {l s='Besoin d\'une pose ?' d='Shop.Theme.Customdealt'}
                </span>
            </div>
            <span class="dealt-offer-subtitle" style="font-size: 13px;">
                {l s='Faites appel à un installateur et économisez [1]10%[/1] en passant par nos [2]kits[/2]' sprintf=['[1]' => '<strong>', '[/1]' => '</strong>', '[2]' => '<strong>', '[/2]' => '</strong>'] d='Shop.Theme.Customdealt'}
            </span>
        </div>
        <div class="dealt-promo-cta" style="text-align: end;">
            <a href="{$promo_kit_link|escape:'html':'UTF-8'}" 
               class="btn-primary btn-promo-dealt"
               target="_self"
               rel="noopener noreferrer">
                {l s='Voir nos kits' d='Shop.Theme.Customdealt'}
            </a>
        </div>
    </div>
</div>
