<span class="h6 mb-2">{l s='Caractéristiques techniques' d='Shop.Theme.Customcatalog'}</span>

<div id="product-features-show">

  {block name='product_features'}
    {if $product.grouped_features}
      <section class="product-features mt-4">
        <p class="h6">{l s='Data sheet' d='Shop.Theme.Catalog'}</p>
        <dl class="data-sheet">
          {foreach from=$product.grouped_features item=feature name=foo}
            {if $smarty.foreach.foo.index < 3}
            <dt class="name">{$feature.name}</dt>
            <dd class="value">{$feature.value|escape:'htmlall'|nl2br nofilter}</dd>
            {/if}
          {/foreach}
        </dl>
      </section>
    {/if}
  {/block}



  <div class="text-left">
      <a href="#block-description-long" class="text-bold text-right">+ {l s='Lire d\'avantage' d='Shop.Theme.Customcatalog'}</a>
  </div> 
</div>
