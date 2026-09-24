{* CKC Vianney — lien kit dans la langue courante (mappings Dealt figés en FR) *}
{if $promo_kit_link|strstr:'/441-'}
  {assign var='promo_kit_link' value=$link->getCategoryLink(441)}
{elseif $promo_kit_link|strstr:'/216-'}
  {assign var='promo_kit_link' value=$link->getCategoryLink(216)}
{elseif $promo_kit_link|strstr:'/93-'}
  {assign var='promo_kit_link' value=$link->getCategoryLink(93)}
{elseif $promo_kit_link|strstr:'/1073-'}
  {assign var='promo_kit_link' value=$link->getProductLink(1073)}
{elseif $promo_kit_link|strstr:'/1079-'}
  {assign var='promo_kit_link' value=$link->getProductLink(1079)}
{elseif $promo_kit_link|strstr:'/1168-'}
  {assign var='promo_kit_link' value=$link->getProductLink(1168)}
{/if}

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
