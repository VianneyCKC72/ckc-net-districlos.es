$(function (){$(document).on('mouseup',function (e){const $activeItem=$('.mm_extra_item.active');if ($activeItem.length&&!$activeItem.is(e.target)&&$activeItem.has(e.target).length===0){$activeItem.removeClass('active');}});$(document).on('click','.mm_extra_item button[type="submit"]',function (){const $item=$(this).closest('.mm_extra_item');if ($item.hasClass('mm_display_search_default'))return;if (!$item.hasClass('active')){$item.addClass('active');}else if ($(this).prev('input').val()==0){$item.removeClass('active');}return false;});displayHeightTab();const $megamenu=$('.ets_mm_megamenu.sticky_enabled');const $header=$('#header');if ($megamenu.length&&$(window).width() > 768){const offsetTop=$megamenu.offset().top;const headerMarginTop=parseInt($megamenu.css('marginTop'))||0;const headerMarginBottom=parseInt($megamenu.css('marginBottom'))||0;const headerFloatingHeight=$megamenu.outerHeight()+headerMarginTop+headerMarginBottom;const oldHeaderMarginBottom=parseInt($header.css('marginBottom'))||0;const handleSticky=()=>{const scrollTop=$(window).scrollTop();const isSticky=scrollTop > offsetTop;$megamenu.toggleClass('scroll_heading',isSticky);if ($header.length){$header.css('marginBottom',isSticky?headerFloatingHeight:oldHeaderMarginBottom);}if ($(window).width() > 1200&&isSticky){$('.mm_modal_filter,ul.mm_menus_ul').css({top:'',left:''});}};handleSticky();$(window).on('scroll',handleSticky);}const toggleStickyBody=()=>{const isMobile=$(window).width() < 768;$('body').toggleClass('disable-sticky',isMobile);};toggleStickyBody();$(window).on('resize',toggleStickyBody);$(function (){function adjustMenuPosition(){const $megamenu=$('.ets_mm_megamenu');const $menus=$('.mm_modal_filter,ul.mm_menus_ul');if ($megamenu.hasClass('scroll_heading')||!$megamenu.hasClass('sticky_enabled'))return;const width=$(window).width();const scrollTop=$(window).scrollTop();let topOffset=0;if (width > 1200){topOffset=150;}else if (width > 1006){topOffset=200;}else if (width > 991){topOffset=150;}else if (width >=768){topOffset=108;}if (scrollTop > 0&&scrollTop < 140.5){topOffset-=(scrollTop-32);}$menus.css({top:`${topOffset}px`,left:'0',width:'100%'});}$(window).on('scroll resize',adjustMenuPosition);adjustMenuPosition();$(window).on('scroll resize',adjustMenuPosition);adjustMenuPosition();});$(window).load(function (){if ($('.ets_mn_submenu_full_height').length > 0){var ver_sub_height=$('.ets_mn_submenu_full_height').height();$('.ets_mn_submenu_full_height').find('.mm_columns_ul').css("min-height",ver_sub_height);}});if ($('.mm_columns_ul_tab_content').length > 0&&$('body#index').length > 0){$('.mm_columns_ul_tab_content').addClass('active').prev('.arrow').removeClass('closed').addClass('opened');}$(window).resize(function (){$('.mm_menus_ul:not(.ets_mm_all_show_resize)').removeClass('ets_mn_active');});$(document).on('click','.mm_has_sub > .arrow',function (){const $arrow=$(this);const $wrapper=$arrow.parent().next('.mm_columns_ul');const $menu=$('.mm_menus_ul.active');const closeAll=()=>{$('.mm_columns_ul').removeClass('active');$('.mm_has_sub > .arrow').removeClass('opened').addClass('closed');$('.mm_columns_contents_ul.active').removeClass('active');$('.mm_tab_li_content.opened').removeClass('opened').addClass('closed');$menu.removeClass('mm_menus_ul_big mm_menus_ul_medium');};if ($arrow.hasClass('closed')){closeAll();$arrow.removeClass('closed').addClass('opened');$wrapper.stop(true,true).addClass('active');$menu.addClass('mm_menus_ul_medium');}else{$arrow.removeClass('opened').addClass('closed');$wrapper.stop(true,true).removeClass('active');$menu.removeClass('mm_menus_ul_medium mm_menus_ul_big');$('.mm_tab_li_content.opened').removeClass('opened').addClass('closed');$('.mm_columns_contents_ul.active').removeClass('active');}});$('.transition_slide:not(.changestatus)li.mm_menus_li').hover(function (){if ($(window).width() >=768){$(this).find('.mm_columns_ul').show();}},function (){if ($(window).width() >=768){$(this).find('.mm_columns_ul').hide();}});$(document).on('click','.ybc-menu-toggle .ybc-menu-button-toggle_icon,.ybc-menu-toggle .main_menu_label,.ybc-menu-toggle .mm_modal_filter',function (){const $toggle=$(this).closest('.ybc-menu-toggle');const $menu=$('.mm_menus_ul');const $reviewWidget=$('#netreviewsWidgetNum28990widget03av');const $transition=$('.transition_slide.transition_default');const isOpening=$toggle.hasClass('closed');if(isOpening){$toggle.removeClass('closed').addClass('opened');}else{$toggle.removeClass('opened').addClass('closed');}$('body').toggleClass('menu-opened',isOpening);$menu.stop(true,true).toggleClass('active',isOpening);$reviewWidget.toggle(!isOpening);if ($(this).hasClass('ybc-menu-button-toggle_icon')){$('.close_menu .mm_menus_back').removeClass('opened closed').addClass(isOpening?'opened':'closed');}if ($transition.length){$menu.toggle(isOpening);}});$(document).on('click','body.menu-opened #wrapper',function (e){e.preventDefault();e.stopPropagation();const $toggle=$('.menu-button.d-none.d-md-block .ybc-menu-toggle');const $menu=$('.mm_menus_ul');const $reviewWidget=$('#netreviewsWidgetNum28990widget03av');const $transition=$('.transition_slide.transition_default');if ($toggle.hasClass('opened')){$toggle.removeClass('opened').addClass('closed');$('body').removeClass('menu-opened');$menu.stop(true,true).removeClass('active');$reviewWidget.show();if ($transition.length){$menu.hide();}}});$(document).on('click','.ybc-menu-vertical-button,.mm_menus_back',function (){const $btn=$(this);const $menu=$('.mm_menus_ul');const $reviewWidget=$('#netreviewsWidgetNum28990widget03av');const $transition=$('.transition_slide.transition_default');const isOpening=$btn.hasClass('closed');if(isOpening){$btn.removeClass('closed').addClass('opened');}else{$btn.removeClass('opened').addClass('closed');}$('body').toggleClass('menu-opened',isOpening);$menu.stop(true,true).toggleClass('active',isOpening);$reviewWidget.toggle(!isOpening);if ($transition.length) $menu.toggle(isOpening);if (!isOpening&&$btn.hasClass('mm_menus_back')){$('.ybc-menu-toggle').removeClass('opened').addClass('closed');}});$(document).on('click','.close_menu',function (){const $menuWrapper=$(this).parent();const $toggle=$menuWrapper.prev();$toggle.removeClass('opened').addClass('closed');$('body').removeClass('menu-opened');$menuWrapper.stop(true,true).removeClass('active');});if ($('.ets_mm_megamenu').hasClass('enable_active_menu')){const currentUrl=window.location.href;$('.mm_menus_ul > li').each(function (){if ($(this).find(`a[href="${currentUrl}"]`).length){$(this).addClass('active');return false;}});}$('.mm_breaker').prev('li').addClass('mm_before_breaker');const isMobile=$(window).width() < 768;const $firstTab=$('.mm_tab_li_content').first();if (isMobile){const $tabParent=$firstTab.closest('.mm_tabs_li');const $tabContainer=$firstTab.closest('.mm_columns_ul');if (!$tabParent.hasClass('open')){$firstTab.closest('.mm_columns_ul_tab').find('.mm_tabs_li').removeClass('open');$tabParent.addClass('open');$tabContainer.removeClass('mm_tab_no_content');if (!$firstTab.next('.mm_columns_contents_ul').length){$tabContainer.addClass('mm_tab_no_content');}displayHeightTab();}}else{$(document).on('mouseenter','.mm_tab_li_content',function (){const $this=$(this);const $tabParent=$this.closest('.mm_tabs_li');const $tabContainer=$this.closest('.mm_columns_ul');if (!$tabParent.hasClass('open')){$this.closest('.mm_columns_ul_tab').find('.mm_tabs_li').removeClass('open');$tabParent.addClass('open');$tabContainer.removeClass('mm_tab_no_content');if (!$this.next('.mm_columns_contents_ul').length){$tabContainer.addClass('mm_tab_no_content');}displayHeightTab();}});$(document).on('mouseleave','.mm_tab_li_content',function (){const $tabParent=$(this).closest('.mm_tabs_li');if (!$tabParent.hasClass('ver_alway_hide')){$tabParent.removeClass('open');}});}$(document).on('mouseenter','.mm_menus_li:not(.menu_ver_alway_show_sub)',function (){$('.menu_ver_alway_show_sub .mm_columns_ul_tab_content').removeClass('active');});$(document).on('mouseleave','.mm_menus_li:not(.menu_ver_alway_show_sub)',function (){$('.menu_ver_alway_show_sub .mm_columns_ul_tab_content').addClass('active');});if (!$('.clicktext_show_submenu').length){$(document).on('click touchstart','.mm_tab_li_content',function (e){const $tab=$(this);const $submenu=$tab.next();if ($tab.find('.mm_tab_toggle_title a').is(e.target)) return;const isOpening=$tab.hasClass('closed');$('.mm_tab_li_content').removeClass('opened').addClass('closed');$('.mm_columns_contents_ul').removeClass('active');$tab.toggleClass('opened closed',isOpening);$submenu.stop(true,true).toggleClass('active',isOpening);const $menu=$('.mm_menus_ul.active');$menu.toggleClass('mm_menus_ul_big',isOpening);$menu.toggleClass('mm_menus_ul_medium',!isOpening);});$(document).on('click touchstart','.back_menu_parent,.back_menu_parent_li',function (){const $btn=$(this).hasClass('back_menu_parent_li')?$(this).parent().prev():$(this).parent().parent().prev();const $wrapper=$btn.next();$btn.removeClass('opened').addClass('closed');$wrapper.stop(true,true).removeClass('active');const $menu=$('.mm_menus_ul.active');$menu.addClass('mm_menus_ul_medium').removeClass('mm_menus_ul_big');});}});function autoChangeStatus(){const $menu=$('.ets_mm_megamenu');const $arrows=$('#index .menu_ver_alway_show_sub .arrow');const $tabs=$('#index .menu_ver_alway_show_sub .mm_columns_ul_tab_content');const isActive=$menu.hasClass('changestatus');$menu.toggleClass('changestatus',!isActive);$arrows.toggleClass('opened',!isActive).toggleClass('closed',isActive);$tabs.toggleClass('active',!isActive);}function itemClickMenu($this){const $trigger=$($this);const $arrow=$trigger.next('.arrow');const $wrapper=$arrow.length?$arrow.next():$trigger.closest('.mm_tab_li_content').next();const isTab=!$arrow.length;if (isTab){const $tab=$trigger.closest('.mm_tab_li_content');const isOpening=$tab.hasClass('closed');$('.mm_tab_li_content').removeClass('opened').addClass('closed');$('.mm_tab_li_content+.mm_columns_contents_ul').removeClass('active');$tab.toggleClass('opened closed',isOpening);$wrapper.stop(true,true).toggleClass('active',isOpening);}else{const isOpening=$arrow.hasClass('closed');$('.mm_has_sub > .arrow').removeClass('opened').addClass('closed');$('.mm_columns_ul').removeClass('active');$arrow.toggleClass('opened closed',isOpening);$wrapper.stop(true,true).toggleClass('active',isOpening);}}function clickTextShowMenu(){const isMobile=$(window).width() <=767;const hasStatus=$('.ets_mm_megamenu').hasClass('changestatus');if (!$('.clicktext_show_submenu').length)return;function toggleSubMenu($btn){const $arrow=$btn.next('.arrow');const $wrapper=$arrow.next();const isOpening=$arrow.hasClass('closed');$arrow.toggleClass('opened closed',isOpening);$wrapper.stop(true,true).toggleClass('active',isOpening);}function toggleTabMenu($btn){const $tab=$($btn).parents('.mm_tab_li_content');const $wrapper=$tab.next();const isOpening=$tab.hasClass('closed');$('.mm_tab_li_content').removeClass('opened').addClass('closed');$('.mm_tab_li_content+.mm_columns_contents_ul').removeClass('active');$tab.toggleClass('opened closed',isOpening);$wrapper.stop(true,true).toggleClass('active',isOpening);}$('.clicktext_show_submenu li.has-sub > a').on('click',function (e){if (isMobile||hasStatus){e.preventDefault();toggleSubMenu($(this));}});$('.clicktext_show_submenu li.mm_tabs_has_content > div a').on('click',function (e){if (isMobile||hasStatus){e.preventDefault();toggleTabMenu(this);}});$('.clicktext_show_submenu .mm_tab_has_child > .mm_tab_toggle_title').on('click',function (){if (!$(this).find('a').length&&(isMobile||hasStatus)){toggleTabMenu(this);}});}$(document).on('click','.ets_mm_categories .has-sub .arrow',function (e){e.stopPropagation();const $arrow=$(this);const $subCatWrapper=$arrow.next('.ets_mm_categories');const isOpening=$arrow.hasClass('closed');$arrow.toggleClass('opened closed',isOpening);$subCatWrapper.stop(true,true).toggleClass('active',isOpening);});function displayHeightTab(){$('.mm_tabs_li.open .mm_columns_contents_ul').each(function (){const $tab=$(this).closest('.mm_columns_ul_tab');$tab.css('height',$(this).outerHeight());});}$(document).ready(function (){const $menu=$('.ets_mm_megamenu');const bgGrayEnabled=$menu.data('bggray');$menu.removeClass('bg_submenu');if (bgGrayEnabled){$menu.find('.mm_menus_ul > li.mm_has_sub')
    .on('mouseenter',()=> $menu.addClass('bg_submenu'))
    .on('mouseleave',()=> $menu.removeClass('bg_submenu'));}clickTextShowMenu();$('.toggle_menu').on('click',autoChangeStatus);$(document).on('mouseenter','.mm_menus_li iframe',function (){$(this).closest('.mm_menus_li').addClass('menu_hover');}).on('mouseleave','.mm_menus_li iframe',function (){$(this).closest('.mm_menus_li').removeClass('menu_hover');});$(document).on('click','.arrow-sub-menu-mobile-hide',function (){$(this).toggleClass('arrow-sub-menu-mobile-show');$(this).parent().next().toggleClass('ets_mm_block_content_show');});});

$(document).ready(function () {
    var isMobile = $(window).width() <= 768;
    
    // Styles CSS dynamiques (images desktop + liens "Voir")
    if (!$('#megamenu-custom-styles').length) {
        $('<style id="megamenu-custom-styles">')
            .text('@media (min-width: 769px) { .mm_tab_li_content .mm_tab_toggle_title img { display: none !important; } } ' +
                  'a[href*="Voir"]:not([data-image]) { text-decoration: underline !important; }')
            .appendTo('head');
    }
    
    // Gestion du clic sur le lien de premier niveau
    $('.item_first_layer.mm_has_sub > a').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var arrow = this.nextElementSibling;
        if (arrow && arrow.classList.contains('arrow')) {
            arrow.click();
        }
        return false;
    });

    // Fonction utilitaire pour rendre un élément cliquable en mobile
    function makeClickable(selector, arrowSelector, ignoreSelectors) {
        $(selector).each(function() {
            var elem = this;
            var $arrow = $(elem).find(arrowSelector).first();
            if (!$arrow.length) return;
            
            $(elem).css({ 'cursor': 'pointer', 'width': '100%', 'display': 'flex', 'justify-content': 'space-between', 'align-items': 'center' });
            
            elem.addEventListener('click', function(e) {
                if ($(e.target).closest(ignoreSelectors).length) return;
                if ($(e.target).closest(selector).length || e.target === elem) {
                    e.preventDefault();
                    e.stopPropagation();
                    $arrow[0].click();
                }
            }, true);
        });
    }
    
    // En mobile : rendre tous les niveaux cliquables
    if (isMobile) {
        makeClickable('.mobile_noclic.mm_menus_li', '.item_first_layer .arrow', '.arrow, .mm_columns_ul');
        makeClickable('.mm_tab_li_content', '.arrow', '.arrow, .mm_columns_contents_ul');
        makeClickable('.ets_mm_block .h4', '.arrow', '.arrow, .ets_mm_block_content');
    }

    // Gestion des onglets desktop et liens sans sous-menu
    $('.mm_tab_li_content').each(function() {
        var $link = $(this).find('> .mm_tab_name > .mm_tab_toggle_title > a.mm-link-menu-sub');
        if (!$link.length) return;
        
        var $submenu = $(this).next('.mm_columns_contents_ul');
        
        if ($submenu.length && !isMobile) {
            $link[0].onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.nextElementSibling.click();
                return false;
            };
        } else if (!$submenu.length) {
            var href = $link[0].href;
            this.onclick = function(e) {
                if (e.target.tagName !== 'A') {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    location.href = href;
                    return false;
                }
            };
        }
    });
    
    // Changement d'image au survol (desktop uniquement)
    if (!isMobile) {
        $('.mm_columns_contents_ul').each(function() {
            var $submenu = $(this);
            var $imageContainer = $submenu.find('.mm_subcat_img.promoted').first();
            
            if ($imageContainer.length) {
                var defaultImage = $imageContainer.css('background-image');
                $imageContainer.data('default-image', defaultImage);
                
                $submenu.find('.ets_mm_block .h4 > a, .ets_mm_block_content a').each(function() {
                    var $link = $(this);
                    var imageUrl = $link.attr('data-image');
                    
                    if (imageUrl) {
                        new Image().src = imageUrl; // Préchargement
                        
                        $link.on('mouseenter', function() {
                            $imageContainer.css('background-image', 'url("' + imageUrl + '")');
                        }).on('mouseleave', function() {
                            $imageContainer.css('background-image', defaultImage);
                        });
                    }
                });
                
                $submenu.on('mouseleave', function() {
                    $imageContainer.css('background-image', defaultImage);
                });
            }
        });
    }
});