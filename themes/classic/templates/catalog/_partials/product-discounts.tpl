{**
 * Tableau remises quantité — même style que le bandeau packs (ap5-ml-degressive).
 *}
{if $product.quantity_discounts}
  <div class="ap5-ml-degressive product-discounts js-product-discounts" role="region" aria-label="{l s='Prix dégressifs' d='Shop.Theme.Catalog'}">
    <div class="ap5-ml-degressive__cta">
      <strong><span class="ap5-ml-degressive__cta-line">Prix</span><span class="ap5-ml-degressive__cta-line">dégressifs&nbsp;!</span></strong>
    </div>
    <div class="ap5-ml-degressive__scroll">
      {block name='product_discount_table'}
        <table class="ap5-ml-degressive__table">
          <thead>
            <tr>
              <th scope="row">{l s='Quantité' d='Shop.Theme.Catalog'}</th>
              {foreach from=$product.quantity_discounts item='quantity_discount'}
                <td data-discount-type="{$quantity_discount.reduction_type}"
                    data-discount="{$quantity_discount.real_value}"
                    data-discount-quantity="{$quantity_discount.quantity}">
                  dès {$quantity_discount.quantity}
                </td>
              {/foreach}
            </tr>
          </thead>
          <tbody>
            <tr>
              <th scope="row">{$configuration.quantity_discount.label}</th>
              {foreach from=$product.quantity_discounts item='quantity_discount'}
                <td data-discount-type="{$quantity_discount.reduction_type}"
                    data-discount="{$quantity_discount.real_value}"
                    data-discount-quantity="{$quantity_discount.quantity}">
                  {$quantity_discount.discount}
                </td>
              {/foreach}
            </tr>
          </tbody>
        </table>
      {/block}
    </div>
  </div>
{/if}
