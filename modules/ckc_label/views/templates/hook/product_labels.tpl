{if isset($ckc_labels) && $ckc_labels|count > 0}
    {foreach from=$ckc_labels key=key item=label}
        <div class="discount_type_flag ckc-label-flag" style="background-color:{$label.background_color|escape:'html':'UTF-8'};color:{$label.color|escape:'html':'UTF-8'};top:{$key * 42 + 10}px">
            <span class="discount-percentage">
                {$label.text nofilter}
                {if $label.show_discount_percent && $ckc_labels_product.has_discount && $ckc_labels_product.discount_type === 'percentage'}
                    {$ckc_labels_product.discount_percentage|escape:'html':'UTF-8'}
                {/if}
            </span>
        </div>
    {/foreach}
{elseif $ckc_labels_product.has_discount && $ckc_labels_product.discount_type === 'percentage'}
    <div class="discount_type_flag ckc-label-flag" style="background-color:#ff144e;color:#fff;top:10px">
        <span class="discount-percentage">
            {$ckc_labels_product.discount_percentage|escape:'html':'UTF-8'}
        </span>
    </div>
{/if}
