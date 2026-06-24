{extends file="page.tpl"}

{block name="page_content"}

    <section>
        <h2 class="h2">{l s='Nos guides d\'installation et fiches techniques' d='Shop.Theme.Customespacepro'}</h2>

        <div id="search_" class="">
            <form action="" method="get" class="" onsubmit="return false;">
                <input type="text" name="search_query_items" placeholder="{l s='Je recherche...' d='Shop.Theme.Customespacepro'}"
                        class="store-search-input"/>
            </form>
        </div>
    </section>

    <section>
    {* <pre>
        {$sections|var_dump}
    </pre> *}
        {foreach from=$sections item=section}
            <div class="row">
                <div class="col-12">
                    <h2 class="title-section">{$section.title_section}</h2>
                </div>

                {foreach from=$section.products item=product}
                    <div class="col-xs-6 col-sm-6 col-md-4 col-lg-3 mb-30 items_">
                        <div class="card_sm">
                            <div class="thumbnail">
                                <img src="{$product.image}" alt="{$product.name}">
                            </div>

                            <div class="text_">
                                <span class="h6 title">{$product.name}</span>

                                <a class="btn_sm"
                                href="{$link->getModuleLink('ckc_espacepro', 'guidesfiches', ['download_product' => $product.id])}">
                                    {l s='Télécharger tous les documents' d='Shop.Theme.Customespacepro'}
                                </a>
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>
        {/foreach}
    </section>

    <div id="no-results" class="no-results" style="display: none; text-align: center; padding: 20px;">
        <p>{l s='Aucun produit ne correspond à votre recherche.' d='Shop.Theme.Customespacepro'}</p>
    </div>

{/block}