{foreach from=$productsPackUnique item=productPack name=productPack_list}
    {if !empty($productPack.attributes.groups)}
    <div class="row mb-5">
        <div class="col-md-2 position-relative d-flex">
            {assign var=productPackImageIds value="`$productPack.id_product`-`$productPack.image.id_image`"}
            <div style="padding: 20px;box-shadow: 0 10px 20px #0000000d;width: 100%;" class="d-flex">
                <img src="{$pmlink->getImageLink($productPack.presentation.link_rewrite, $productPackImageIds, $imageFormatProductSlideshow)}" alt="{$productPack.image.legend}" style="width: auto;max-height: 190px;max-width: 100%;margin: auto;">
            </div>
            <div class="position-absolute w-100 d-flex" style="bottom: -16px !important;">
                <span class="text-center m-auto" style="padding: 5px 20px;background-color: #79C144;width: 83px;color: #fff;font-weight: 600;font-size: 16px;">x{$productPack.quantity}</span>
            </div>
        </div>
        <div class="col-md-10 content-kit-description">
            <h3 class="h5 text-uppercase fs-16">{$productPack.presentation.name}</h3>
            {if !empty($productPack.attributes.groups)}
                <p class="mb-2 text-muted ap5-kit-combination-label">
                    {foreach from=$productPack.attributes.groups item=group name=ap5KitAttrGroups}
                        {if isset($group.default) && isset($group.attributes[$group.default])}{$group.attributes[$group.default].name|escape:'html':'UTF-8'}{if !$smarty.foreach.ap5KitAttrGroups.last}, {/if}{/if}
                    {/foreach}
                </p>
            {/if}
            <div class="product-description">{$productPack.presentation.description_short nofilter}</div>
            <div class="mt-10">
                <a class="btn btn-primary add_to_cart" href="{$productPack.presentation.link}">Voir le produit</a>
            </div>
            {block name='product_reviews'}
                {hook h='displayProductListReviews' product=$productPack.presentation}
            {/block}
        </div>
    </div>
    {/if}
{/foreach}
