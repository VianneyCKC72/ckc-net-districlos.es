/**
 * 2007-2021 ETS-Soft
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 wesite only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 * @author ETS-Soft <etssoft.jsc@gmail.com>
 * @copyright 2007-2021 ETS-Soft
 * @license Valid for 1 website (or project) for each purchase of license
 * International Registered Trademark & Property of ETS-Soft
 */
$(function () {

        $(document).on('mouseup', function (e) {
            const $activeItem = $('.mm_extra_item.active');
            if ($activeItem.length && !$activeItem.is(e.target) && $activeItem.has(e.target).length === 0) {
                $activeItem.removeClass('active');
            }
        });

        // Gestion du clic sur le bouton submit
        $(document).on('click', '.mm_extra_item button[type="submit"]', function () {
            const $item = $(this).closest('.mm_extra_item');
            if ($item.hasClass('mm_display_search_default')) return;

            if (!$item.hasClass('active')) {
                $item.addClass('active');
            } else if ($(this).prev('input').val() == 0) {
                $item.removeClass('active');
            }
            return false;
        });

        displayHeightTab();
        if ($('.ets_mm_megamenu.sticky_enabled').length > 0 && $(window).width() > 768) {
            var sticky_navigation_offset_top = $('.ets_mm_megamenu.sticky_enabled').offset().top;
            var headerFloatingHeight = $('.ets_mm_megamenu.sticky_enabled').height() + ($('#header').length > 0 ? parseInt($('.ets_mm_megamenu.sticky_enabled').css('marginTop').replace('px', '')) + parseInt($('.ets_mm_megamenu.sticky_enabled').css('marginBottom').replace('px', '')) : 0);
            var oldHeaderMarginBottom = $('#header').length > 0 ? parseInt($('#header').css('marginBottom').replace('px', '')) : 0;
            var windowWidth = $(window).width();
            var sticky_navigation = function () {
                if (!$('.ets_mm_megamenu').hasClass('sticky_enabled'))
                    return false;
                var scroll_top = $(window).scrollTop();

                if (scroll_top > sticky_navigation_offset_top) {
                    $('.ets_mm_megamenu.sticky_enabled').addClass('scroll_heading');
                    if ($('#header').length > 0) {
                        $('#header').css({'marginBottom': headerFloatingHeight + 'px'});
                    }
                    if (windowWidth > 1200 && $('.ets_mm_megamenu.sticky_enabled').hasClass('scroll_heading')) {
                        $('.mm_modal_filter, ul.mm_menus_ul').css('top', ''); // Enlever 'top'
                        $('.mm_modal_filter, ul.mm_menus_ul').css('left', ''); // Enlever 'left'
                    }
                } else {
                    $('.ets_mm_megamenu.sticky_enabled').removeClass('scroll_heading');
                    if ($('#header').length > 0)
                        $('#header').css({'marginBottom': oldHeaderMarginBottom + 'px'});
                }
            };
            sticky_navigation();
            $(window).scroll(function () {
                sticky_navigation();
            });
            if ($(window).width() < 768 && !$('body').hasClass('disable-sticky'))
                $('body').addClass('disable-sticky');
            $(window).on('resize', function (e) {
                if ($(window).width() < 768 && !$('body').hasClass('disable-sticky'))
                    $('body').addClass('disable-sticky');
                else if ($(window).width() >= 768 && $('body').hasClass('disable-sticky'))
                    $('body').removeClass('disable-sticky');
            });
        }
        $(function () {

            function adjustMenuPosition() {
                // Vérifier si le menu doit être ajusté
                if ($('.ets_mm_megamenu').hasClass('scroll_heading') || !$('.ets_mm_megamenu.sticky_enabled').length) {
                    return;
                }

                const windowWidth = $(window).width();
                const scrollTop = $(window).scrollTop();
                let topOffset, leftOffset;

                // Définition de topOffset selon la largeur de l'écran
                if (windowWidth < 768) {
                    topOffset = 0;
                } else if (windowWidth <= 991) {
                    topOffset = 108;
                } else if (windowWidth <= 1006) {
                    topOffset = 150;
                } else if (windowWidth <= 1200) {
                    topOffset = 200;
                } else {
                    topOffset = 150;
                }

                // Ajustement dynamique en fonction du scroll
                if (scrollTop > 0 && scrollTop < 140.5) {
                    topOffset -= scrollTop - 32;
                } else if (scrollTop === 0) {
                    if (windowWidth < 768) {
                        topOffset = 0;
                    } else if (windowWidth <= 1200) {
                        topOffset = 150;
                    } else {
                        topOffset = 150;
                    }
                }

                leftOffset = '0'; // Toujours aligné à gauche

                // Application des styles
                $('.mm_modal_filter, ul.mm_menus_ul').css({
                    'top': `${topOffset}px`,
                    'left': leftOffset,
                    width: windowWidth < 1520 ? '100%' : '100%' // ou même fixe : '100%'
                });
            }

            // Appliquer l'ajustement au chargement, au défilement et au redimensionnement
            $(window).on('scroll resize', adjustMenuPosition);
            adjustMenuPosition();
        });


        $(window).load(function () {
            if ($('.ets_mn_submenu_full_height').length > 0) {
                var ver_sub_height = $('.ets_mn_submenu_full_height').height();
                $('.ets_mn_submenu_full_height').find('.mm_columns_ul').css("min-height", ver_sub_height);
            }
        });

        if ($('.mm_columns_ul_tab_content').length > 0 && $('body#index').length > 0) {
            $('.mm_columns_ul_tab_content').addClass('active').prev('.arrow').removeClass('closed').addClass('opened');

        }


        $(window).resize(function () {
            $('.mm_menus_ul:not(.ets_mm_all_show_resize)').removeClass('ets_mn_active');
        });

        $(document).on('click', '.mm_has_sub > .arrow', function () {
            var wrapper = $(this).parent().next('.mm_columns_ul');

            if ($(this).hasClass('closed')) {
                $('.mm_columns_ul').removeClass('active');
                $('.mm_has_sub > .arrow').removeClass('opened');
                $('.mm_has_sub > .arrow').addClass('closed');
                var btnObj = $(this);
                btnObj.removeClass('closed');
                btnObj.addClass('opened');
                wrapper.stop(true, true).addClass('active');
                $('.mm_columns_contents_ul.active').removeClass('active');
                $('.mm_tab_li_content.opened').removeClass('opened').addClass('closed');
                $('.mm_menus_ul.active').addClass('mm_menus_ul_medium').removeClass('mm_menus_ul_big');
            } else {
                var btnObj = $(this);
                btnObj.removeClass('opened');
                btnObj.addClass('closed');
                wrapper.stop(true, true).removeClass('active');
                $('.mm_columns_contents_ul.active').removeClass('active');
                $('.mm_tab_li_content.opened').removeClass('opened').addClass('closed');
                $('.mm_menus_ul.active').removeClass('mm_menus_ul_medium').removeClass('mm_menus_ul_big');
            }

        });
        $('.transition_slide:not(.changestatus) li.mm_menus_li').hover(function () {
            if ($(window).width() >= 768) {
                $(this).find('.mm_columns_ul').show();
            }
        }, function () {
            if ($(window).width() >= 768) {
                $(this).find('.mm_columns_ul').hide();
            }
        });


        $('.ybc-menu-toggle .ybc-menu-button-toggle_icon, .ybc-menu-toggle .main_menu_label, .ybc-menu-toggle div.mm_modal_filter').on('click', function () {
            var wrapper = $('.mm_menus_ul');

            if ($(this).closest('.ybc-menu-toggle').hasClass('closed')) {
                var btnObj = $(this).closest('.ybc-menu-toggle');
                btnObj.removeClass('closed').addClass('opened');
                $('body').addClass('menu-opened');
                if($(this).hasClass('ybc-menu-button-toggle_icon')){
                    btnMobileCustom = $('.close_menu .mm_menus_back');
                    btnMobileCustom.addClass('opened').removeClass('closed');
                }

                wrapper.stop(true, true).addClass('active');
                $('#netreviewsWidgetNum28990widget03av').hide();

                // Vérifie la présence de .transition_slide.transition_default
                if ($('.transition_slide.transition_default').length !== 0) {
                    wrapper.show();
                }
            } else {
                var btnObj = $(this).closest('.ybc-menu-toggle');
                btnObj.removeClass('opened').addClass('closed');
                $('body').removeClass('menu-opened');

                wrapper.stop(true, true).removeClass('active');
                $('#netreviewsWidgetNum28990widget03av').show();

                if ($('.transition_slide.transition_default').length !== 0) {
                    wrapper.hide();
                }
            }
        });

        $(document).on('click', 'body.menu-opened #wrapper', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var wrapper = $('.mm_menus_ul');
            var $this = $('.menu-button.d-none.d-md-block .ybc-menu-toggle')

            if ($this.hasClass('opened')) {
                var btnObj = $this;
                btnObj.removeClass('opened').addClass('closed');
                $('body').removeClass('menu-opened');

                wrapper.stop(true, true).removeClass('active');
                $('#netreviewsWidgetNum28990widget03av').show();

                if ($('.transition_slide.transition_default').length !== 0) {
                    wrapper.hide();
                }
            }
        });

        $('.ybc-menu-vertical-button, .mm_menus_back').on('click', function () {
            var wrapper = $('.mm_menus_ul');
            if ($(this).hasClass('closed')) {
                var btnObj = $(this);
                btnObj.removeClass('closed');
                btnObj.addClass('opened');
                $('body').addClass('menu-opened');
                wrapper.stop(true, true).addClass('active');
                $('#netreviewsWidgetNum28990widget03av').hide()
                if ($('.transition_slide.transition_default').length != '') {
                    wrapper.show();
                }
            } else {
                var btnObj = $(this);
                btnObj.removeClass('opened');
                btnObj.addClass('closed');
                if($(this).hasClass('mm_menus_back')){
                    btnMainCustom = $('.menu-button.d-md-none .ybc-menu-toggle');
                    btnMainCustom.removeClass('opened').addClass('closed');
                }
                $('body').removeClass('menu-opened');
                $('#netreviewsWidgetNum28990widget03av').show()

                wrapper.stop(true, true).removeClass('active');
                if ($('.transition_slide.transition_default').length != '') {
                    wrapper.hide();
                }
            }
        });
        $('.close_menu').on('click', function () {
            $(this).parent().prev().removeClass('opened');
            $(this).parent().prev().addClass('closed');
            $('body').removeClass('menu-opened');
            $(this).parent().stop(true, true).removeClass('active');
        });
        //Active menu
        if ($('.ets_mm_megamenu').hasClass('enable_active_menu') && $('.mm_menus_ul > li').length > 0) {
            var currentUrl = window.location.href;
            $('.mm_menus_ul > li').each(function () {
                if ($(this).find('a[href="' + currentUrl + '"]').length > 0) {
                    $(this).addClass('active');
                    return false;
                }
            });
        }
        if ($('.mm_breaker').length > 0 && $('.mm_breaker').prev('li').length > 0) {
            $('.mm_breaker').prev('li').addClass('mm_before_breaker');
        }
        if ($(window).width() < 768) {
            if (!$('.mm_tab_li_content').closest('.mm_tabs_li').hasClass('open')) {
                $('.mm_tab_li_content').closest('.mm_columns_ul_tab').find('.mm_tabs_li').removeClass('open');
                $('.mm_tab_li_content').closest('.mm_tabs_li').addClass('open');
                $('.mm_tab_li_content').closest('.mm_columns_ul').removeClass('mm_tab_no_content');
                if (!$('.mm_tab_li_content').next('.mm_columns_contents_ul').length) {
                    $('.mm_tab_li_content').closest('.mm_columns_ul').addClass('mm_tab_no_content');
                }
                displayHeightTab();
            }
        } else {
            $('.mm_tab_li_content').hover(function () {
                if (!$(this).closest('.mm_tabs_li').hasClass('open')) {
                    $(this).closest('.mm_columns_ul_tab').find('.mm_tabs_li').removeClass('open');
                    $(this).closest('.mm_tabs_li').addClass('open');
                    $(this).closest('.mm_columns_ul').removeClass('mm_tab_no_content');
                    if (!$(this).next('.mm_columns_contents_ul').length) {
                        $(this).closest('.mm_columns_ul').addClass('mm_tab_no_content');
                    }
                    displayHeightTab();
                }
            }, function () {
                if (!$(this).closest('.mm_tabs_li').hasClass('ver_alway_hide')) {
                    $(this).closest('.mm_tabs_li').removeClass('open');
                }

            });
        }

        $('.mm_menus_li:not(.menu_ver_alway_show_sub )').hover(function () {
            $('.menu_ver_alway_show_sub .mm_columns_ul_tab_content').removeClass('active');
        }, function () {
            $('.menu_ver_alway_show_sub .mm_columns_ul_tab_content').addClass('active');
        });

        if ($('.clicktext_show_submenu').length <= 0) {
            $(document).on('click touchstar', '.mm_tab_li_content', function (evt) {
                var btnObj = $(this), wrapper = $(this).next();
                if (!btnObj.find('.mm_tab_toggle_title a').is(evt.target)) {
                    if (btnObj.hasClass('closed')) {
                        $('.mm_tab_li_content').removeClass('opened');
                        $('.mm_tab_li_content').addClass('closed');
                        $('.mm_columns_contents_ul').removeClass('active');
                        btnObj.removeClass('closed');
                        btnObj.addClass('opened');
                        wrapper.stop(true, true).addClass('active');
                        if($(this).next().length > 0){
                            $('.mm_menus_ul.active').removeClass('mm_menus_ul_medium');
                            $('.mm_menus_ul.active').addClass('mm_menus_ul_big');
                        }
                    } else {
                        btnObj.removeClass('opened');
                        btnObj.addClass('closed');
                        wrapper.stop(true, true).removeClass('active');
                        $('.mm_menus_ul.active').addClass('mm_menus_ul_medium');
                        $('.mm_menus_ul.active').removeClass('mm_menus_ul_big');
                    }
                }
            });
            $(document).on('click touchstar', '.back_menu_parent, .back_menu_parent_li', function (evt) {
                if ($(this).hasClass('back_menu_parent_li')) {
                    var btnObj = $(this).parent().prev();
                    var wrapper = $(this).parent();
                } else {
                    var btnObj = $(this).parent().parent().prev();
                    var wrapper = $(this).parent().parent();
                }
                btnObj.removeClass('opened');
                btnObj.addClass('closed');
                wrapper.stop(true, true).removeClass('active');
                $('.mm_menus_ul.active').addClass('mm_menus_ul_medium');
                $('.mm_menus_ul.active').removeClass('mm_menus_ul_big');
            });
        }

    }
)
;

function autoChangeStatus() {
    if ($('.ets_mm_megamenu').hasClass('changestatus')) {
        $('.ets_mm_megamenu').removeClass('changestatus');
        $('#index .menu_ver_alway_show_sub .arrow').addClass('opened').removeClass('closed');
        $('#index .menu_ver_alway_show_sub .mm_columns_ul_tab_content').addClass('active');
    } else {
        $('.ets_mm_megamenu').addClass('changestatus');
        $('.menu_ver_alway_show_sub .mm_columns_ul_tab_content').removeClass('active');
        $('.menu_ver_alway_show_sub .arrow').removeClass('opened').addClass('closed');
    }
}

function itemClickMenu($this) {
    var btnObj = $($this).next('.arrow');
    var wrapper = btnObj.next();
    if (!btnObj.length) {
        var btn_temp = $($this).closest('.mm_tab_li_content').first();
        var wrapper = btn_temp.next();
        if (btn_temp.hasClass('closed')) {
            $('.mm_tab_li_content').removeClass('opened');
            $('.mm_tab_li_content').addClass('closed');
            $('.mm_tab_li_content + .mm_columns_contents_ul').removeClass('active');
            btn_temp.removeClass('closed');
            btn_temp.addClass('opened');
            wrapper.stop(true, true).addClass('active');


        } else {
            btn_temp.removeClass('opened');
            btn_temp.addClass('closed');
            wrapper.stop(true, true).removeClass('active');
        }
    } else {
        if (btnObj.hasClass('closed')) {
            $('.mm_has_sub > .arrow').removeClass('opened');
            $('.mm_has_sub > .arrow').addClass('closed');
            $('.mm_columns_ul').removeClass('active');

            btnObj.removeClass('closed');
            btnObj.addClass('opened');
            wrapper.stop(true, true).addClass('active');
        } else {
            btnObj.removeClass('opened');
            btnObj.addClass('closed');
            wrapper.stop(true, true).removeClass('active');
        }
    }
}

function clickTextShowMenu() {
    if ($('.clicktext_show_submenu').length > 0) {
        $('.clicktext_show_submenu li.has-sub').each(function () {
            $(this).find('a').first().on('click', function (e) {
                if ($(window).width() <= 767) {
                    e.preventDefault();
                    var btnObj = $(this).next('.arrow');
                    var wrapper = btnObj.next();
                    if (btnObj.hasClass('closed')) {
                        btnObj.removeClass('closed');
                        btnObj.addClass('opened');
                        wrapper.stop(true, true).addClass('active');
                    } else {
                        btnObj.removeClass('opened');
                        btnObj.addClass('closed');
                        wrapper.stop(true, true).removeClass('active');
                    }
                }
            });

        });

    }
    if ($('.clicktext_show_submenu').length > 0) {
        $('.clicktext_show_submenu li.has-sub').each(function () {
            $(this).find('a').first().on('click', function (e) {

                if ($('.ets_mm_megamenu').hasClass('changestatus') && $(window).width() > 767) {
                    e.preventDefault();
                    var btnObj = $(this).next('.arrow');
                    var wrapper = btnObj.next();
                    if (btnObj.hasClass('closed')) {
                        btnObj.removeClass('closed');
                        btnObj.addClass('opened');
                        wrapper.stop(true, true).addClass('active');
                    } else {
                        btnObj.removeClass('opened');
                        btnObj.addClass('closed');
                        wrapper.stop(true, true).removeClass('active');
                    }
                }
            });
        });
    }

    if ($('.clicktext_show_submenu').length > 0) {
        $('.clicktext_show_submenu li.mm_tabs_has_content > div').each(function () {
            $(this).find('a').first().on('click', function (e) {
                if ($(window).width() <= 767) {
                    e.preventDefault();
                    itemClickMenu(this);
                }
            });

        });
    }
    if ($('.clicktext_show_submenu').length > 0) {
        $('.clicktext_show_submenu li.mm_tabs_has_content > div').each(function () {
            $(this).find('a').first().on('click', function (e) {
                if ($('.ets_mm_megamenu').hasClass('changestatus') && $(window).width() > 767) {
                    e.preventDefault();
                    itemClickMenu(this);
                }
            });
        });
    }
    if ($('.clicktext_show_submenu').length > 0) {
        $('.clicktext_show_submenu li.mm_has_sub > a').each(function () {
            $(this).on('click', function (e) {
                if ($(window).width() <= 767) {
                    e.preventDefault();
                    itemClickMenu(this);
                }
            });

        });

    }
    if ($('.clicktext_show_submenu').length > 0) {
        $('.clicktext_show_submenu li.mm_has_sub > a').each(function () {
            $(this).on('click', function (e) {
                if ($('.ets_mm_megamenu').hasClass('changestatus') && $(window).width() > 767) {
                    e.preventDefault();
                    itemClickMenu(this);
                }
            });
        });
    }
    if ($('.clicktext_show_submenu').length > 0) {
        $('.mm_tab_has_child > .mm_tab_toggle_title').on('click', function (e) {
            if ($(this).find('a').length <= 0) {
                if ($('.ets_mm_megamenu').hasClass('changestatus') || $(window).width() > 767) {
                    var btnObj = $(this).parents('.mm_tab_li_content'),
                        wrapper = $(this).parents('.mm_tab_li_content').next();

                    if (btnObj.hasClass('closed')) {
                        $('.mm_tab_li_content').removeClass('opened');
                        $('.mm_tab_li_content').addClass('closed');
                        $('.mm_tab_li_content + .mm_columns_contents_ul').removeClass('active');

                        btnObj.removeClass('closed');
                        btnObj.addClass('opened');
                        wrapper.stop(true, true).addClass('active');
                    } else {
                        btnObj.removeClass('opened');
                        btnObj.addClass('closed');
                        wrapper.stop(true, true).removeClass('active');
                    }
                }
            }
        });
    }
}


$(document).on('click', '.ets_mm_categories .has-sub .arrow', function (e) {
    e.stopPropagation();
    var wrapper = $(this).next('.ets_mm_categories');
    if ($(this).hasClass('closed')) {
        var btnObj = $(this);
        btnObj.removeClass('closed');
        btnObj.addClass('opened');
        wrapper.stop(true, true).addClass('active');
    } else {
        var btnObj = $(this);
        btnObj.removeClass('opened');
        btnObj.addClass('closed');
        wrapper.stop(true, true).removeClass('active');
    }

});

function displayHeightTab() {
    if ($('.mm_tabs_li.open .mm_columns_contents_ul').length) {
        $('.mm_tabs_li.open .mm_columns_contents_ul').each(function () {
            $(this).closest('.mm_columns_ul_tab').css('height', $(this).height('px'));
        });
    }
}

$(document).ready(function () {

    var ETS_MM_ACTIVE_BG_GRAY = $('.ets_mm_megamenu').attr('data-bggray');
    $('.ets_mm_megamenu').removeClass('bg_submenu');
    if (typeof ETS_MM_ACTIVE_BG_GRAY !== "undefined" && ETS_MM_ACTIVE_BG_GRAY) {
        $('.ets_mm_megamenu .mm_menus_ul > li.mm_has_sub').mouseenter(function () {
            $('.ets_mm_megamenu').addClass('bg_submenu');
        })
            .mouseleave(function () {
                $('.ets_mm_megamenu').removeClass('bg_submenu');
            });
    }

    clickTextShowMenu();

    $('.toggle_menu').click(function () {
        autoChangeStatus();
    });

    $('.mm_menus_li iframe').hover(function () {
        $(this).parents('.mm_menus_li').addClass('menu_hover');
    }, function () {
        $(this).parents('.mm_menus_li').removeClass('menu_hover');
    });

    $(".arrow-sub-menu-mobile-hide").click(function(){
        if(!$(this).hasClass('arrow-sub-menu-mobile-show')){
            $(this).addClass('arrow-sub-menu-mobile-show')
            $(this).parent().next().addClass('ets_mm_block_content_show')
        } else {
            $(this).removeClass('arrow-sub-menu-mobile-show')
            $(this).parent().next().removeClass('ets_mm_block_content_show')
        }
    })
})
