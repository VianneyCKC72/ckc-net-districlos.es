{**
 * Galerie pack — images filtrables par couleur (packImageColors / packSelectedColorId).
 *}
<div class="images-container product-images-grid mb-5 mb-lg-0" data-pack-color-filter="1">
  {block name='product_cover'}
    <div class="product-images-grid__track">
      {foreach from=$product.images key=key item=image}
        {assign var=imgId value=$image.id_image|intval}
        {assign var=colorId value=0}
        {if isset($packImageColors[$imgId])}{assign var=colorId value=$packImageColors[$imgId]|intval}{/if}
        {* Masquer uniquement les images liées à une AUTRE couleur (garder base / sans binding) *}
        {assign var=hideColor value=($packSelectedColorId|default:0) && $colorId && $colorId != $packSelectedColorId}
        <div class="product-images-grid__item{if $key == 0} product-images-grid__item--first{/if}{if $hideColor} is-hidden-by-color{/if}"
             data-color-attribute-id="{if $colorId}{$colorId}{/if}">
          {if $key == 0}
            {if !$product.from_europe}
              <div class="pastille_fr">
                <img src="/img/pastille_produit_france.svg" alt="{l s='Fabriqué en France' d='Shop.Theme.Customcatalog'}" class="pastille_fr_icon pastille-fr-icon-page-produit">
              </div>
            {/if}
            <img class="tag-web-price" src="/themes/classic/assets/img/tag-price.svg" loading="lazy" alt="">
          {/if}
          <a href="{$image.bySize.large_default.url}" class="js-product-image-zoom" rel="nofollow">
            <picture>
              {if !empty($image.bySize.large_default.sources.avif)}
                <source srcset="{$image.bySize.large_default.sources.avif}" type="image/avif">{/if}
              {if !empty($image.bySize.large_default.sources.webp)}
                <source srcset="{$image.bySize.large_default.sources.webp}" type="image/webp">{/if}
              <img
                class="img-cover rounded-4"
                src="{$image.bySize.large_default.url}"
                alt="{if !empty($image.legend)}{$image.legend}{else}{$product.name}{/if}"
                {if $key != 0}loading="lazy"{/if}
              />
            </picture>
          </a>
        </div>
      {/foreach}
    </div>
    {hook h='displayAfterProductThumbs' product=$product}
  {/block}
</div>
