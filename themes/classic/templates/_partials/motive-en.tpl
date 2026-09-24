{**
 * CKC Vianney — layer Motive anglais (même engine que le FR)
 *}
<script>
  var motive = {
    initParams: {
      xEngineId: '62d2c95c-235a-477a-aca1-2e6e440e9754',
      lang: '{$language.locale|default:'en-US'|escape:'javascript'}',
      currency: '{$currency.iso_code|escape:'javascript'}',
      triggerSelector: '#search_widget',
      isolated: true,
      cartUrl: '{$urls.pages.cart|escape:'javascript'}?action=show'
    },
    options: {
      showPrices: true,
      shopperPrices: true,
      priceRates: { min: 1, max: 1, static: 1 }
    },
    endpoint: '{url entity="module" name="motive" controller="front"}',
    motive_x_url: 'https://assets.motive.co/motive-x/v2/app.js'
  };
</script>
<link id="motive-layer-js" rel="modulepreload" href="https://assets.motive.co/motive-x/v2/app.js" as="script" crossorigin="anonymous">
<script src="https://assets.motive.co/front-loader/prestashop/v1.js"></script>
