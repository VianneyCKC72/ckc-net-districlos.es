<div id="blockcart-modal" class="modal fade cart-pro-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="h4 mb-0" id="myModalLabel">
                    <img src="/themes/classic/assets/img/ico-panier.svg" alt="Panier">
                    {l s='Product successfully added to your shopping cart' d='Shop.Theme.Checkout'}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' d='Shop.Theme.Global'}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-x">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                <div class="row">

                    {*{if $pro.env == 1}*}

                    {include file='module:ps_shoppingcart/_partials/modal-pro.tpl'
                    product=$product
                    cart=$cart
                    configuration=$configuration
                    urls=$urls
                    }

                    {*                    {else}

                                            {include file='module:ps_shoppingcart/_partials/modal-part.tpl'
                                            product=$product
                                            cart=$cart
                                            configuration=$configuration
                                            urls=$urls
                                            }

                                        {/if}*}


                </div>
            </div>

            {hook h='displayCartModalFooter' product=$product}

        </div>
    </div>
</div>
