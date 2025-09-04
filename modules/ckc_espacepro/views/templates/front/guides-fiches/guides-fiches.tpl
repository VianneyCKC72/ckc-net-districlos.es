{extends file="page.tpl"}

{block name="page_content"}

    <section>
        <h2 class="h2">Nos guides d'installation</h2>

        <div id="search_" class="">
            <form action="" method="get" class="" onsubmit="return false;">
                <input type="text" name="search_query_items" placeholder="Je recherche..."
                        class="store-search-input"/>
            </form>

         
        </div>
    </section>

    <section>

        {foreach from=$sections item=section}

            <div class="row">
                <div class="col-12">
                    <h2 class="title-section" >{$section.title}</h2>
                </div>
                {foreach from=$section.products item=product}
                    <div class="col-xs-6 col-sm-6 col-md-4 col-lg-3 mb-30 items_">
                        <div class="card_sm">
                            <div class="thumbnail">
                                    <img src="{$product.image}" alt="{$product.name}">
                                {* <img src="https://placehold.co/400x450" alt="{$product.name}"> *}
                            </div>
                            <div class="text_">
                                <span class="h6 title">{$product.name}</span>
                                <a class="btn_sm" href="{$product.document.download_url}" title="" target="_blank">{$btnText}</a>
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>
        
        {/foreach}
    </section>

    <div id="no-results" class="no-results" style="display: none; text-align: center; padding: 20px;">
        <p>Aucun produit ne correspond à votre recherche.</p>
    </div>

    

{/block}
