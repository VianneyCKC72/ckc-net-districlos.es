/**
 * @copyright Studio Kiwik 2014 2019
 * @see http://licences.studio-kiwik.fr/responsivegallery
 */
 
$(function(){
    if(typeof kiwik === 'undefined'){
        return;
    }
    if(typeof kiwik.responsivegallery === 'undefined'){
        return;
    }

    //Functions
    function getPresentScreenConfiguration(){
        var galleryWidth =$(kiwik.responsivegallery.GALLERY_SELECTOR).width();
        if(galleryWidth == 0)
            galleryWidth = $('body').width();
        var result = null;
        for(var id in kiwik.responsivegallery.SEUILS){
            if(galleryWidth < kiwik.responsivegallery.SEUILS[id].maxWidth){
                result = kiwik.responsivegallery.SEUILS[id];
                break;
            }
        }
        result.galleryWidth = galleryWidth;
        return result;
    }

    function refreshPositions(){
        //récupération du seuil actuel
        var options = getPresentScreenConfiguration();

        //on place toutes les images par rapport à ça
        var items = $(kiwik.responsivegallery.GALLERY_SELECTOR + ' li');
        var count = 0;
        var itemWidth = parseInt(options.galleryWidth / options.nbPerLine, 10) - kiwik.responsivegallery.HORIZONTAL_MARGIN*2;

        var hauteurMax = 0;

        //on retire toutes les colonnes de tous les elements pour faire un gros reset
        items.attr('data-column',false);

        items.each(function(){
            //REFRESH DU BLOC
            $(this).css('width', itemWidth + 'px');

            //on choisi dans quelle colonne on le met.
            var highest_bottom = null;
            var objectJustAbove = null;
            var column_to_put = 0;
            for(var i = 0 ; i < options.nbPerLine; i++) {
                var current_lowest_bottom = null;
                var currentObjectAbove = null;
                $(kiwik.responsivegallery.GALLERY_SELECTOR + ' li[data-column='+i+']').each(function(){
                    var item_bottom = parseInt($(this).css('top'),10) + $(this).height();
                    if(current_lowest_bottom==null || item_bottom > current_lowest_bottom) {
                        current_lowest_bottom = item_bottom;
                        currentObjectAbove = $(this);
                    }
                });
                if((highest_bottom==null&&currentObjectAbove!=null) || current_lowest_bottom < highest_bottom) {
                    column_to_put = i;
                    highest_bottom = current_lowest_bottom;
                    objectJustAbove = currentObjectAbove;
                }
            }
            $(this).css('left', ( column_to_put/*(count%options.nbPerLine)*/*(itemWidth+kiwik.responsivegallery.HORIZONTAL_MARGIN*2) + kiwik.responsivegallery.HORIZONTAL_MARGIN) + 'px');
            $(this).attr('data-column', column_to_put);
            $(this).css('height', $(this).find('img').height()+'px');
            if(objectJustAbove == null)
                $(this).css('top', '0px');
            else{
                //var objectJustAbove = $( items.get(count-options.nbPerLine) );
                var previousOffset = parseInt(objectJustAbove.css('top'),10) + objectJustAbove.height();
                $(this).css('top', (previousOffset+kiwik.responsivegallery.VERTICAL_MARGIN*2) + 'px');
            }


            hauteurMax = Math.max(hauteurMax, parseInt($(this).css('top'),10) + $(this).height());

            count++;
        });
        //on adapte la gallery a la hauteur max qu'on vient de créer
        $(kiwik.responsivegallery.GALLERY_SELECTOR).height(hauteurMax);

    }

    var loaderHtml = '<div id="gallery-loader"></div>';
    var isLoading = true;

    //Events and handlings
    $(function(){

        $('a.fancy-group').fancybox({
            helpers : {
                title: {
                    type: 'outside'
                }
            },
        	padding:0
        });

        $(kiwik.responsivegallery.GALLERY_SELECTOR).after(loaderHtml);

        $(window).on('resize', function(){
            refreshPositions();
        });

        //infinite scrolling
        var offset = 0;
        function refreshOffset(){
            offset = $(kiwik.responsivegallery.GALLERY_SELECTOR + '> li:last').offset();
        }

        $(window).scroll(function(){
            refreshOffset();
            if( offset != undefined && offset.top-$(window).height() <= $(window).scrollTop() && !isLoading ){
                isLoading = true;
                kiwik.responsivegallery.RG_CURRENT_PAGE++;
                //fix pour les url qui ne sont pas réécrites
                kiwik.responsivegallery.ajax_link = kiwik.responsivegallery.ajax_link.replace(/&amp;/g,'&');
                $.get(kiwik.responsivegallery.ajax_link, {'ajax':1, 'id_gallery':kiwik.responsivegallery.current_gallery, 'page':kiwik.responsivegallery.RG_CURRENT_PAGE}, function(data){
                    if($(data).length != 0){
                        $('#gallery-loader').slideDown();
                        $(kiwik.responsivegallery.GALLERY_SELECTOR).append(data);
                        preloadPage(kiwik.responsivegallery.RG_CURRENT_PAGE);
                    }
                });
            }
        });
        preloadPage(1);
    });

    function showPage(page){
        $('#gallery-loader').slideUp();
        $(kiwik.responsivegallery.GALLERY_SELECTOR + ' li.page-'+page).fadeIn(kiwik.responsivegallery.FADEIN_SPEED);
        // buildInnerTexts(page);
        refreshPositions();
        isLoading=false;
    }

    function preloadPage(page){
        var nbLoaded = 0;
        if($(kiwik.responsivegallery.GALLERY_SELECTOR + ' li.page-'+page+' img').length == 0)
            $('#gallery-loader').slideUp();

        var $imgs = $(kiwik.responsivegallery.GALLERY_SELECTOR + ' li.page-'+page+' img');
        var total = $imgs.length;

        function onImgDone(){
            nbLoaded++;
            if(nbLoaded >= total){
                showPage(page);
            }
        }

        $imgs.each(function(){
            if(!this.complete){
                $(this).on('load error', onImgDone);
            }
            else{
                onImgDone();
            }
        });
    }

});