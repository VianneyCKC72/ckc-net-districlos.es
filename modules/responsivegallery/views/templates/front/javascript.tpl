<!--{*
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 *}-->

<script type="text/javascript">
    if(typeof kiwik === "undefined"){
        kiwik = {ldelim}{rdelim};
    }
    if(typeof kiwik.responsivegallery ==="undefined"){
        kiwik.responsivegallery = {ldelim}{rdelim};
    }
    //Configuration
    kiwik.responsivegallery.GALLERY_SELECTOR="#gallery";
    kiwik.responsivegallery.FADEIN_SPEED = {$RG_FADEIN_SPEED|intval};
    kiwik.responsivegallery.VERTICAL_MARGIN = {$RG_VERTICAL_MARGIN|intval};
    kiwik.responsivegallery.HORIZONTAL_MARGIN = {$RG_HORIZONTAL_MARGIN|intval};
    kiwik.responsivegallery.RG_CURRENT_PAGE = {$RG_CURRENT_PAGE|intval};
    kiwik.responsivegallery.FANCYBOX_ENABLED = true;
    kiwik.responsivegallery.RG_LEGEND_ON_PHOTO = {$RG_LEGEND_ON_PHOTO|intval};
    kiwik.responsivegallery.ajax_link = '{$ajax_link|escape:'html':'UTF-8'}';
    //Configuration des différents seuils
    kiwik.responsivegallery.SEUILS = {ldelim}
        0:{ldelim}'maxWidth':{$RG_BREAKPOINT_1|intval}, 'nbPerLine':{$RG_NB_ITEM_1|intval}{rdelim},
        1:{ldelim}'maxWidth':{$RG_BREAKPOINT_2|intval}, 'nbPerLine':{$RG_NB_ITEM_2|intval}{rdelim},
        2:{ldelim}'maxWidth':{$RG_BREAKPOINT_3|intval}, 'nbPerLine':{$RG_NB_ITEM_3|intval}{rdelim},
        3:{ldelim}'maxWidth':{$RG_BREAKPOINT_4|intval}, 'nbPerLine':{$RG_NB_ITEM_4|intval}{rdelim},
        4:{ldelim}'maxWidth':{$RG_BREAKPOINT_5|intval}, 'nbPerLine':{$RG_NB_ITEM_5|intval}{rdelim}
        {rdelim};

</script>