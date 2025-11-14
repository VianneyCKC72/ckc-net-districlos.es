{extends file='page.tpl'}

{block name='head_seo_title'}
    {$meta_title}
{/block}

{block name='page_title'}
    {$meta_title}
{/block}


{block name='page_content'}
<div id="ckc-realisation-galleries">
    {if isset($galleries) && $galleries}
        {foreach from=$galleries item=$gallery key=$key}
        <div class="gallery-single{if ($gallery[0].count_color % 2 == 0)} gallery-even-color{/if}">
            <div class="gallery-inside-content-container">
                <span class="title">{$gallery[0].category_title}</span>
                {if count($gallery) > 4}
                    <div class="gallery-content" id="ckc-realisation-images">
                        {foreach from=$gallery item=$image key=$key}
                            {if $key < 4}
                                <a class="image-single col-md-3 col-sm-6 col-xs-12" href="{$urls.base_url}modules/ckc_realisation/uploads/{$id_shop}/{$image.filename}" target="_blank" data-pswp-width="{$image.width}"
                                   data-pswp-height="{$image.height}">
                                    <img src="{$urls.base_url}modules/ckc_realisation/uploads/{$id_shop}/{$image.filename}" alt="{$image.alt}">
                                    <div class="image-info-container">
                                        <div class="image-info-product-name">{$image.product_name}</div>
                                        <div class="image-info-product-author">
                                            {if $image.firstname != ''}
                                                {$image.firstname} {substr($image.lastname,0,1)}.{if isset($image.city) && $image.city != ''} - {$image.city}{/if}
                                            {/if}
                                        </div>
                                        <span class="btn-primary-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-up-right"><path d="M13 5H19V11"></path><path d="M19 5L5 19"></path></svg></span>
                                    </div>
                                </a>
                            {else}
                                <a class="image-single col-md-3 col-sm-6 col-xs-12 image-single-invisible" href="{$urls.base_url}modules/ckc_realisation/uploads/{$id_shop}/{$image.filename}" target="_blank" data-pswp-width="{$image.width}"
                                   data-pswp-height="{$image.height}">
                                    <img src="{$urls.base_url}modules/ckc_realisation/uploads/{$id_shop}/{$image.filename}" alt="{$image.alt}">
                                    <div class="image-info-container">
                                        <div class="image-info-product-name">{$image.product_name}</div>
                                        <div class="image-info-product-author">
                                            {if $image.firstname != ''}
                                                {$image.firstname} {substr($image.lastname,0,1)}.{if isset($image.city) && $image.city != ''} - {$image.city}{/if}
                                            {/if}
                                        </div>
                                        <span class="btn-primary-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-up-right"><path d="M13 5H19V11"></path><path d="M19 5L5 19"></path></svg></span>
                                    </div>
                                </a>
                            {/if}
                        {/foreach}
                    </div>
                    <div class="image-gallery-accordeon btn btn-primary">Voir toutes les réalisations</div>
                    <div class="image-gallery-accordeon-hide btn btn-primary">Réduire</div>
                {else}
                    <div class="gallery-content" id="ckc-realisation-images">
                        {foreach from=$gallery item=$image key=$key}
                            <a class="image-single col-md-3 col-sm-6 col-xs-12" href="{$urls.base_url}modules/ckc_realisation/uploads/{$id_shop}/{$image.filename}" target="_blank" data-pswp-width="{$image.width}"
                               data-pswp-height="{$image.height}">
                                <img src="{$urls.base_url}modules/ckc_realisation/uploads/{$id_shop}/{$image.filename}" alt="{$image.alt}">
                                <div class="image-info-container">
                                    <div class="image-info-product-name">{$image.product_name}</div>
                                    <div class="image-info-product-author">
                                        {if $image.firstname != ''}
                                            {$image.firstname} {substr($image.lastname,0,1)}.{if isset($image.city) && $image.city != ''} - {$image.city}{/if}
                                        {/if}
                                    </div>
                                    <span class="btn-primary-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-up-right"><path d="M13 5H19V11"></path><path d="M19 5L5 19"></path></svg></span>
                                </div>
                            </a>
                        {/foreach}
                    </div>
                {/if}
            </div>
        </div>
        {/foreach}
    {else}
        <div>Pas d'images des réalisations.</div>
    {/if}
</div>

    <script src="/modules/ckc_realisation/views/js/photoswipe.umd.min.js"></script>
    <script src="/modules/ckc_realisation/views/js/photoswipe-lightbox.umd.min.js"></script>
    <link rel="stylesheet" href="/modules/ckc_realisation/views/css/photoswipe.css">

    <script type="text/javascript">
        const lightbox = new PhotoSwipeLightbox({
            // may select multiple "galleries"
            gallery: '#ckc-realisation-images',

            // Elements within gallery (slides)
            children: '.image-single',

            // setup PhotoSwipe Core dynamic import
            pswpModule: PhotoSwipe
        });
        lightbox.init();
    </script>
{/block}