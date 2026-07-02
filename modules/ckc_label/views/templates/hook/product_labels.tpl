{if isset($ckc_labels) && $ckc_labels|count > 0}
    {foreach from=$ckc_labels key=key item=label}
        <div class="discount_type_flag" style="
                background-color: {$label.background_color};
                padding: 5px;
                margin: 5px 0;
                text-align: center;
                border-radius: 4px;
                display: flex;
                width: auto;
                font-size: 12px;
                position: absolute;
                top: {$key * 28}px;
                left: 5px;
                z-index: 9;">
            <span class="discount-percentage" style="position: unset;display: flex;margin:auto;color: {$label.color};">
                {$label.text|escape:'html':'UTF-8'}
                {if isset($label.show_discount_percent) && $label.show_discount_percent && $ckc_labels_product.has_discount && $ckc_labels_product.discount_type === 'percentage'}
                    {$ckc_labels_product.discount_percentage}
                {/if}
            </span>
        </div>
    {/foreach}
{elseif $ckc_labels_product.has_discount}
    <div class="discount_type_flag" style="
            background-color: #ff144e;
            color: #fff;
            padding: 5px;
            margin: 5px 0;
            text-align: center;
            border-radius: 4px;
            display: flex;
            width: auto;
            font-size: 12px;
            position: absolute;
            top: 0;
            left: 5px;
            z-index: 99;">
            <span class="discount-percentage" style="position: unset;display: flex;margin:auto;">
                {if isset($ckc_label_show_discount_percent) && $ckc_label_show_discount_percent && $ckc_labels_product.discount_type === 'percentage'}
                    {$ckc_labels_product.discount_percentage}
                {/if}
            </span>
    </div>
{/if}
