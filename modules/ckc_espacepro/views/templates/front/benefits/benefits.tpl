{extends file="page.tpl"}

{block name="page_content"}

    {* Section des bénéfices *}
    <section class="benefits-section p-b-100">
        <img class="position-absolute image-bg z-n1" src="modules/ckc_espacepro/views/img/dessins.svg" alt="">
        <div class="container px-0 z-0">
            <div class="row">
            <div class="col-12">
                <span class="d-block h2 m-b-3">Les avantages pro</span>
            </div>
                {foreach from=$benefits item=card}
                    <div class="col-12 col-sm-6 col-lg-4 m-b-65">
                        {include file="module:ckc_espacepro/views/templates/front/benefits/_partials/card.tpl" card=$card}
                    </div>
                {/foreach}
            </div>
        </div>
    </section>

    {* Section des services *}
    <section class="services-section p-b-100">
        <div class="container px-0">
            <div class="row">
                <div class="col-12">
                    <span class="d-block h2 m-b-3">Les services qu'on vous propose</span>
                </div>
                {foreach from=$services item=card}
                    <div class="col-12 col-sm-6 col-lg-4 m-b-65">
                        {include file="module:ckc_espacepro/views/templates/front/_partials/card.tpl" card=$card}
                    </div>
                {/foreach}
            </div>
        </div>
    </section>


    {* section profitez de nos remises sur la quantité *}
    <section class="fluid_container bg_primary p-t-33 p-b-33 m-b-100">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <span class="d-block fz-23 font-weight-bold m-b-3 text-white">Profitez de nos remises sur la quantité !</span>
                </div>
            </div>
            {hook h='CETemplate' id=38}
        </div>
    </section>

    {* catégories de produits *}
    <section>
        <div class="container px-0">
            <div class="row">
                 <div class="col-12">
                    <span class="d-block h2 m-b-3">Plus de 4500 produits en stock</span>
                </div>
            </div>
            {hook h='CETemplate' id=6}
        </div>
    </section>
               

{/block}
