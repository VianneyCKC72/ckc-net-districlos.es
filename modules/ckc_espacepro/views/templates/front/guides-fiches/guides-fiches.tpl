{extends file="page.tpl"}

{block name="page_content"}
    
    {foreach from=$sections item=section}

        <section>
            <div class="row">
                <div class="col-12">
                    <h2 class="title-section" >{$section.title}</h2>
                </div>
                {foreach from=$section.products item=product}
                    <div class="col-xs-6 col-sm-6 col-md-4 col-lg-3 mb-30">
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
        </section>
    
    {/foreach}

{/block}
