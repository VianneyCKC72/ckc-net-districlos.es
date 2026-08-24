<dl class="order-payment-infos">
    <dt>{l s='Amount' mod='ckc_paywithphone'}</dt>
    <dd>{$total|escape:'html':'UTF-8'}</dd>
    {if $store_name}
        <dt>{l s='Store' mod='ckc_paywithphone'}</dt>
        <dd>{$store_name|escape:'html':'UTF-8'}</dd>
    {/if}
    <dt>{l s='Phone' mod='ckc_paywithphone'}</dt>
    <dd>{$ckc_paywithphonePhone|escape:'html':'UTF-8'}</dd>
</dl>
