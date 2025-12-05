/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

new WOW().init();

document.addEventListener("keydown", function (event) {
    if (event.keyCode == 13) {
        event.preventDefault();
        return false;
    }
}, true);

function accessoryNotification() {
    $(document).on('click', '.options .configurator-action', function () {
        new Noty({
            text: "Accessoire sélectionné",
            type: "success",
            timeout: 500
        }).show();

        const target = $(this).parent().parent();

        $('html, body').animate({
            scrollTop: target.offset().top - 320
        }, 600);
    });
}


$(document).ready(function () {
    const interval = 300; // Intervalle en millisecondes

    Array.prototype.insert = function (index, item) {
        this.splice(index, 0, item);
    };

    accessoryNotification()

    $(document).on('click', '.first .configurator-action', function () {
        $('html, body').animate({
            scrollTop: $('.second').offset().top - 200
        }, 600);
    });

    if(window.innerWidth < 1200){
        window.addEventListener('scroll', function () {
            var addToCartBottom = document.querySelector('.add_to_cart_configurator_bottom');
            var productActions = document.querySelector('.step.second');
            var mobileSession = document.querySelector('.mobil_session');

            // Vérifie que les éléments existent avant de continuer
            if (!addToCartBottom || !productActions || !mobileSession) {
                return;
            }

            var productActionsOffset = productActions.offsetTop;
            var mobileSessionOffset = mobileSession.offsetTop;
            var isDisabled = productActions.classList.contains('disabled');
            var windowScroll = window.pageYOffset || document.documentElement.scrollTop;

            if (!isDisabled) {
                if (windowScroll > productActionsOffset && windowScroll < mobileSessionOffset) {
                    addToCartBottom.style.display = 'block';
                } else {
                    addToCartBottom.style.display = 'none';
                }
            }
        });
    }

    $("#voir_config").click(function () {
        $('html, body').animate({
            scrollTop: $(".mobil_session").offset().top
        });
    });

    $first = $(".first");
    $second = $(".second");
    $third = $(".third");
    $fourth = $(".fourth");
    $preview = $(".preview");
    $all = $(".first, .second, .third, .fourth, .preview");
    $session = $(".configurator-session");
    $products = $(".configurator-products-list div.list");
    $loader = $("#configurator-loader");

    // SELECTING FIRST MAIN CATEGORY
    $first.on('click', '.configurator-action', function (event) {
        event.preventDefault();

        $selected = $(this);
        $.ajax({
            url: window.location.href, method: "post", data: {
                ajax: true,
                id_category: $(this).data('id')
            }, dataType: 'json', success: function (response) {
                $first.find(".configurator-action").removeClass('selected');
                $selected.addClass('selected');
                $second.html(response.second);
                $third.html(response.third);
                $fourth.html(response.fourth);

                $second.show();
                $third.show();
                $fourth.show();
                $second.removeClass('disabled');
                $third.removeClass('disabled');
                $fourth.removeClass('disabled');
                $preview.removeClass('disabled');

                // Preview.render(response.preview);

                $session.show();
                Session.render(response);

                viewInformationAlum();
            }
        });
    });

    // Empêcher le clic sur les liens avec select de déclencher l'action normale (générique)
    $("body").on('click', '.portillon-jardin-action, .occultation-action, .soubassement-action', function (event) {
        // Si on clique sur le select, ne pas empêcher
        if ($(event.target).is('select') || $(event.target).closest('select').length > 0) {
            return;
        }
        // Si on clique sur le lien mais pas sur les SVG, empêcher le comportement par défaut
        if (!$(event.target).closest('svg').length) {
            event.preventDefault();
        }
    });

    // Gestion du select pour les produits avec déclinaisons (générique)
    $("body").on('change', '.portillon-jardin-select, .occultation-select, .soubassement-select', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var $select = $(this);
        var selectedValue = $select.val();
        var productId = $select.data('id');
        var dataName = $select.data('name');
        var $selectedOption = $select.find('option:selected');
        var idProductAttribute = $selectedOption.data('id-product-attribute');
        var priceFormatted = $selectedOption.data('price-formatted');
        var imageUrl = $selectedOption.data('image-url');
        var $action = $select.closest('.portillon-jardin-action, .occultation-action, .soubassement-action');
        var $priceContainer = $action.find('.portillon-jardin-price-action .portillon-jardin-price, .occultation-price-action .occultation-price, .soubassement-price-action .soubassement-price');
        var $imageContainer = $action.find('.portillon-jardin-image img, .occultation-image img, .soubassement-image img');
        var hiddenInputName = dataName;
        var isPortillon = dataName === 'portillon_id';
        var isOccultation = dataName === 'occultation_id';
        var isSoubassement = dataName === 'soubassement_id';
        var checkboxSelector = isPortillon ? '#checkbox_portillon' : (isOccultation ? '#checkbox_occultation' : '#checkbox_soubassement');
        
        if (selectedValue) {
            // Mettre à jour le champ hidden
            $('input[name="' + hiddenInputName + '"]').val(productId);
            
            // Mettre à jour l'image si disponible
            if (imageUrl && $imageContainer.length > 0) {
                $imageContainer.attr('src', imageUrl);
            }
            
            // Afficher et mettre à jour le prix
            if (priceFormatted) {
                $priceContainer.find('.price-value').text(priceFormatted);
                $priceContainer.show();
            }
            
            // Si le produit est déjà sélectionné, mettre à jour la configuration (changement de déclinaison)
            if ($action.hasClass('selected')) {
                throttle(function () {
                    refreshPrice(true);
                }, interval);
            } else {
                // Si le produit n'est pas encore sélectionné, le sélectionner automatiquement
                // Désélectionner tous les autres produits du même type
                $('.configurator-action[data-name="' + dataName + '"]').removeClass('selected');
                $action.addClass('selected');
                $(checkboxSelector).addClass('selected');
                
                // Ajouter à la configuration
                throttle(function () {
                    refreshPrice(true);
                }, interval);
            }
        }
    });

    // GETTING HEIGHTS AND COLORS DEPENDING ON TYPE CHOOSE
    $("body").on('click', '.configurator-action[data-name]', function (event) {
        // Ne pas traiter les produits avec select ici (gérés séparément)
        if ($(this).hasClass('portillon-jardin-action') || $(this).hasClass('occultation-action') || $(this).hasClass('soubassement-action')) {
            // Si on clique sur les SVG, gérer la sélection
            if ($(event.target).closest('svg').length) {
                event.preventDefault();
                var $action = $(this);
                var name = $action.data('name');
                var isPortillon = name === 'portillon_id';
                var isOccultation = name === 'occultation_id';
                var isSoubassement = name === 'soubassement_id';
                
                // Déterminer les sélecteurs selon le type
                var checkboxSelector = isPortillon ? '#checkbox_portillon' : (isOccultation ? '#checkbox_occultation' : '#checkbox_soubassement');
                var selectClass = isPortillon ? '.portillon-jardin-select' : (isOccultation ? '.occultation-select' : '.soubassement-select');
                var otherProductsSelector = '.configurator-action[data-name="' + name + '"]';
                
                if (!$action.hasClass('selected')) {
                    // Désélectionner tous les autres produits du même type (normaux et avec select)
                    $(otherProductsSelector).removeClass('selected');
                    
                    // Sélectionner le nouveau produit
                    $action.addClass('selected');
                    $(checkboxSelector).addClass('selected');
                    $('input[name="' + name + '"]').val($action.data('id'));
                    
                    // S'assurer qu'une déclinaison est sélectionnée (première par défaut)
                    var $select = $action.find(selectClass);
                    if (!$select.val() && $select.find('option').length > 0) {
                        $select.find('option:first').prop('selected', true);
                        $select.trigger('change');
                    } else if ($select.val()) {
                        // Si une déclinaison est déjà sélectionnée, déclencher refreshPrice pour ajouter à la configuration
                        throttle(function () {
                            refreshPrice(true);
                        }, interval);
                    }
                } else {
                    // Désélectionner le produit
                    $action.removeClass('selected');
                    $(checkboxSelector).removeClass('selected');
                    $('input[name="' + name + '"]').val('');
                    // Remettre la première déclinaison par défaut
                    var $select = $action.find(selectClass);
                    if ($select.find('option').length > 0) {
                        $select.find('option:first').prop('selected', true);
                    }
                    
                    // Retirer le produit de la configuration
                    throttle(function () {
                        refreshPrice(true);
                    }, interval);
                }
            }
            return;
        }

        event.preventDefault();

        var name = $(this).data('name');
        $('input[name="' + name + '"]').val($(this).data('id'));
        $('.configurator-action[data-name="' + name + '"]').removeClass('selected');
        $(this).addClass('selected');
        
        // Cocher le checkbox correspondant pour portillon et occultation
        if (name === 'portillon_id') {
            $('#checkbox_portillon').addClass('selected');
        } else if (name === 'occultation_id') {
            $('#checkbox_occultation').addClass('selected');
        }
        
        // Si on change de poteaux (encoches/clips), réinitialiser les soubassements sélectionnés
        if (name === 'poteaux') {
            // Désélectionner tous les soubassements
            $('.configurator-action[data-name="soubassement_id"]').removeClass('selected');
            $('#checkbox_soubassement').removeClass('selected');
            // Remettre la première déclinaison par défaut pour tous les selects de soubassement
            $('.soubassement-select').each(function() {
                if ($(this).find('option').length > 0) {
                    $(this).find('option:first').prop('selected', true);
                }
            });
            $('input[name="soubassement_id"]').val('');
        }

        if (name == 'type') {
            viewEspacement();
            $.ajax({
                    url: window.location.href, method: "post", data: {
                        ajax: true,
                        changeType: true,
                        forceChange: true,
                        type: $('input[name="type"]').val(),
                        espacement: $('#espacement').val()
                    },
                    dataType: 'json',
                    success: function (response) {
                        response.heights = ObjectToArray(response.heights);
                        response.colors = ObjectToArray(response.colors);
                        response.types = ObjectToArray(response.types);
                        response.poteaux = ObjectToArray(response.poteaux);

                        if (isAluminium()) {
                            $('input[name="hauteur"]').val(response.session.height);
                        }

                        if (Array.isArray(response.heights) && typeof response.heights.forEach === 'function') {
                            // La variable response est un tableau (ou objet semblable à un tableau)
                            // et elle accepte la fonction forEach
                            if (!response.hasError) {
                                $("#heights .row-items").html('');
                                $("#colors .row-items").html('');

                                response.heights.forEach(function (height) {
                                    html = '<div class="col-xs-6 col-md-3" style="margin-bottom: 40px">';
                                    html += '<a href="#" data-id="' + height.value + '" data-name="hauteur" class="' + (height.checked ? 'selected ' : '') + 'item-height configurator-action configurator-action-xs">';
                                    html += '<img src="/modules/ckc_configurator/views/images/hauteurs/' + response.session.id_category + '/' + height.name + '.png" class="img-responsive" alt="' + height.name + '" />';
                                    html += '<div class="title-img">' + height.name + '<svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected"><rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/><path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/></svg><svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected"><rect y="0.5" width="38" height="38" rx="10" fill="white"/><path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/></svg></div>';
                                    html += '</a>';
                                    html += '</div>';

                                    $("#heights .row-items").html($("#heights .row-items").html() + html);

                                });

                                response.colors.forEach(function (color) {
                                    html = '<div class="col-xs-6 col-md-3" style="margin-bottom: 40px">';
                                    html += '<a href="#" data-id="' + color.value + '" data-name="couleur" class="' + (color.checked ? 'selected ' : '') + 'configurator-action configurator-action-xs">';
                                    html += '<img src="/modules/ckc_configurator/views/images/couleurs/' + color.name + '.png" class="img-responsive" alt="' + color.name + '" />';
                                    html += '<div class="title-img">' + color.name + '<svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected"><rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/><path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/></svg><svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected"><rect y="0.5" width="38" height="38" rx="10" fill="white"/><path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/></svg></div>';
                                    html += '</a>';
                                    html += '</div>';

                                    $("#colors .row-items").html($("#colors .row-items").html() + html);
                                });

                                if ($('#poteaux').length > 0) {
                                    $("#poteaux .row-items").html('');

                                    response.poteaux.forEach(function (poteau) {
                                        html = '<div class="col-xs-6 col-xs-6 " style="margin-bottom: 40px">';
                                        html += '<a href="#" data-id="' + poteau.value + '" data-name="poteaux" class="item-height ' + (poteau.checked ? 'selected ' : '') + 'configurator-action configurator-action-xs">';
                                        html += '<img src="/modules/ckc_configurator/views/images/type_poteaux/' + poteau.name + '.jpg" class="img-responsive" alt="' + poteau.name + '" />';
                                        html += '<div class="title-img">' + poteau.name + '<svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected"><rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/><path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/></svg><svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected"><rect y="0.5" width="38" height="38" rx="10" fill="white"/><path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/></svg></div>';
                                        html += '</a>';
                                        html += '</div>';

                                        $("#poteaux .row-items").html($("#poteaux .row-items").html() + html);
                                    });
                                }

                                throttle(function () {
                                    refreshPrice();
                                    // Exécutez votre script Ajax ici
                                }, interval);
                            } else {
                                $('.configurator-action[data-name="hauteur"]').removeClass('selected');
                                $('.configurator-action[data-id="'+response.session.height+'"]').click()
                                // // On affiche la popup erreur
                                // $(".error-bg").show();
                                // $(".error-modal .error-text").html('');
                                // response.errors.forEach(function (error) {
                                //
                                //     $(".error-modal .error-text").html($(".error-modal .error-text").html() + error.msg)
                                //     if (error.action == 'changeType') {
                                //         $(".error-modal .error-buttons").show();
                                //     }
                                // });
                            }
                        }


                        // Preview.render(response.preview);
                        Session.render(response);
                    }
                }
            );
        } else {
            if ($('input[name="pose"]').val() === 'scellement-sur-platine' || $('input[name="pose"]').val() === 'scellement-sur-muret') {
                $(".soubassement").hide();
                $(".soubassement").parent().hide();
                $('#checkbox_soubassement').removeClass('selected')
            } else {
                $(".soubassement:not(.hide-row)").show();
                $(".soubassement:not(.hide-row)").parent().show();
            }

            if (name == 'portillon_id' || name == 'occultation_id') {
                // Ne pas traiter les produits avec select ici (gérés séparément)
                if ($(this).hasClass('portillon-jardin-action') || $(this).hasClass('occultation-action')) {
                    return;
                }
                var change = true;
            } else {
                var change = false;
            }
            throttle(function () {
                refreshPrice(change);
                // Exécutez votre script Ajax ici
            }, interval);

        }
    });

    function ObjectToArray(data){
        // Vérifie si c'est un Array
        if (Array.isArray(data)) {
            return data;
        } else if (typeof data === 'object') {
            // Si ce n'est pas un Array, mais un objet, transforme-le en Array
            return Object.values(data);
        }
        return data;
    }

    $(".configurator-change-type").click(function (event) {
        event.preventDefault();
        var forceChange = $(this).attr('name') == 'changeType';

        $.ajax({
            url: window.location.href, method: "post", data: {
                ajax: true, changeType: true, forceChange: true, type: $('input[name="type"]').val()
            }, dataType: 'json', success: function (response) {
                $("a[data-name='type']").removeClass("selected");
                $("a[data-name='type'][data-id='" + response.session.id_type + "']").addClass("selected");
                $('input[name="type"]').val(response.session.id_type);
                $('input[name="hauteur"]').val(response.session.height);
                $('input[name="couleur"]').val(response.session.color);


                $("#heights .row-items").html('');
                $("#colors .row-items").html('');

                response.heights.forEach(function (height) {
                    html = '<div class="col-md-2" style="margin-bottom: 40px">';
                    html += '<a href="#" data-id="' + height.value + '" data-name="hauteur" class="' + (height.checked ? 'selected ' : '') + 'configurator-action configurator-action-xs">';
                    html += '<img src="/modules/ckc_configurator/views/images/hauteurs/' + response.session.id_category + '/' + height.name + '.png" class="img-responsive" alt="' + height.name + '" />';
                    html += '<span>' + height.name + '</span>';
                    html += '</a>';
                    html += '</div>';

                    $("#heights .row-items").html($("#heights .row-items").html() + html);
                });


                response.colors.forEach(function (color) {
                    html = '<div class="col-xs-6 col-xs-6 " style="margin-bottom: 40px">';
                    html += '<a href="#" data-id="' + color.value + '" data-name="couleur" class="' + (color.checked ? 'selected ' : '') + 'configurator-action configurator-action-xs">';
                    html += '<div class="border"><img src="/modules/ckc_configurator/views/images/couleurs/' + color.name + '.png" class="img-responsive" alt="' + color.name + '" /></div>';
                    html += '<span style="text-transform: capitalize">' + color.name + '</span>';
                    html += '</a>';
                    html += '</div>';

                    $("#colors .row-items").html($("#colors .row-items").html() + html);
                });

                if ($('#poteaux').length > 0) {
                    $('input[name="poteaux"]').val(response.session.poteaux);

                    $("#poteaux .row-items").html('');
                    response.poteaux.forEach(function (poteau) {
                        html = '<div class="col-xs-6 col-xs-6 " style="margin-bottom: 40px">';
                        html += '<a href="#" data-id="' + poteau.value + '" data-name="poteaux" class="item-height ' + (poteau.checked ? 'selected ' : '') + 'configurator-action configurator-action-xs">';
                        html += '<img src="/modules/ckc_configurator/views/images/type_poteaux/' + poteau.name + '.jpg" class="img-responsive" alt="' + poteau.name + '" />';
                        html += '<span>' + poteau.name + '</span>';
                        html += '</a>';
                        html += '</div>';

                        $("#poteaux .row-items").html($("#poteaux .row-items").html() + html);
                    });
                }

                $(".error-bg").hide();
                // Preview.render(response.preview);
                Session.render(response);
            }
        });
    });

// QUERYING ADD TO CART
    $('.add-to-cart-bottom').on('click', function (event) {
        $("#addToCartAlias").click();
    });
    $("#addToCartAlias").on('click', function (event) {
        if($(".fourth .configurator-fourth").length){
            $(".options-form-service").submit();
        } else {
            $(".options-form").submit();
        }
        event.preventDefault();
    });

    $third.on('submit', '.options-form', function (event) {
        event.preventDefault();

        $loader.show();

        var grillage = {};
        $.each($('.core-form').serializeArray(), function () {
            grillage[this.name] = this.value;
        });

        var options = {};
        $.each($(this).serializeArray(), function () {
            options[this.name] = this.value;
        });

        $.ajax({
            url: window.location.href, method: "post", data: {
                ajax: true, addToCart: true, grillage: grillage, options: options,
            }, dataType: 'json', success: function (response) {
                if (response.success) {
                    window.location = '/panier?action=show';
                }
                $loader.hide();
            }
        });
    });

    $fourth.on('submit', '.options-form-service', function (event) {
        event.preventDefault();

        $loader.show();

        var grillage = {};
        $.each($('.core-form').serializeArray(), function () {
            grillage[this.name] = this.value;
        });

        var options = {};
        $.each($(this).serializeArray(), function () {
            options[this.name] = this.value;
        });

        $.ajax({
            url: window.location.href, method: "post", data: {
                ajax: true, addToCart: true, grillage: grillage, options: options,
            }, dataType: 'json', success: function (response) {
                if (response.success) {
                    localStorage.setItem("dealt_product_id", response.dealt_product_id);
                    localStorage.setItem("dealt_service_product_id", response.dealt_service_product_id);
                    localStorage.setItem("dealt_id_product_attribute", response.dealt_id_product_attribute);
                    window.location = '/panier?action=show';
                }
                $loader.hide();
            }
        });
    });

    $all.on('change', 'input[name="longueur"]', function (event) {
        event.preventDefault();

        throttle(function () {
            if (isAluminium()) {
                let inputLength = parseFloat($(event.target).val());
                let intervals = [2.09, 4.115, 6.14, 8.165, 10.19, 12.215, 14.24, 16.265, 18.29, 20.315, 22.34, 24.365, 26.39];

                let matchedLength = intervals.find(value => inputLength <= value) || Math.max(...intervals);
                $('input[name="longueur"]').val(matchedLength);
            }
            refreshPrice();
        }, interval);
    });


    $all.on('change', 'input[name="depart_mur"]', function (event) {
        event.preventDefault();
        refreshPrice();
    });

    $all.on('change', 'input[name="nombre_angle"]', function (event) {
        event.preventDefault();
        throttle(function () {
            refreshPrice();
            // Exécutez votre script Ajax ici
        }, interval);
    });

    $all.on('change', '#espacement', function (event) {
        event.preventDefault();
        throttle(function () {
            refreshPrice();
            // Exécutez votre script Ajax ici
        }, interval);
    });

    $all.on('click', '#sousbassement_hauteur', function (event) {
        refreshPrice(true);
    });

    $all.on('click', '#sousbassement_hauteur, input[name="sousbassement_couleur"], #checkbox_occultation, #checkbox_service, #checkbox_panneau_deco, #checkbox_portillon, #checkbox_soubassement', function (event) {
        event.preventDefault();

        // if id is not checkbox_soubassement
        if ($(this).attr('id') !== 'checkbox_soubassement' && $(this).attr('id') !== 'checkbox_panneau_deco' && $(this).attr('id') !== 'checkbox_portillon' && $(this).attr('id') !== 'checkbox_occultation'  && $(this).attr('id') !== 'checkbox_service') {
            var $item = $(this).parent();
        }else{
            var $item = $(this);
        }

        if (!$item.hasClass('selected')) {
            if ($(this).attr('id') !== 'checkbox_soubassement' && $(this).attr('id') !== 'checkbox_panneau_deco' && $(this).attr('id') !== 'checkbox_portillon' && $(this).attr('id') !== 'checkbox_occultation' && $(this).attr('id') !== 'checkbox_service') {
                $(this).parent().addClass('selected');
            }else{
                $(this).addClass('selected');
            }
            if ($item.next().hasClass('hide-row')) {
                $item.next().show();
            }
        } else {
            $item.removeClass('selected');
            if ($item.next().hasClass('hide-row')) {
                $item.next().hide();
            }
            // Si on désélectionne le checkbox portillon ou occultation, désélectionner tous les produits et retirer de la configuration
            if ($(this).attr('id') === 'checkbox_portillon') {
                // Désélectionner tous les portillons (normaux et avec select)
                $('.configurator-action[data-name="portillon_id"]').removeClass('selected');
                // Remettre la première déclinaison par défaut pour les selects
                $('.portillon-jardin-select').each(function() {
                    if ($(this).find('option').length > 0) {
                        $(this).find('option:first').prop('selected', true);
                    }
                });
                $('input[name="portillon_id"]').val('');
            }
            // Si on désélectionne le checkbox occultation, désélectionner toutes les occultations et retirer de la configuration
            if ($(this).attr('id') === 'checkbox_occultation') {
                // Désélectionner toutes les occultations (normales et avec select)
                $('.configurator-action[data-name="occultation_id"]').removeClass('selected');
                // Remettre la première déclinaison par défaut pour les selects
                $('.occultation-select').each(function() {
                    if ($(this).find('option').length > 0) {
                        $(this).find('option:first').prop('selected', true);
                    }
                });
                $('input[name="occultation_id"]').val('');
            }
            // Si on désélectionne le checkbox soubassement, désélectionner tous les soubassements et retirer de la configuration
            if ($(this).attr('id') === 'checkbox_soubassement') {
                // Désélectionner tous les soubassements (avec select)
                $('.configurator-action[data-name="soubassement_id"]').removeClass('selected');
                // Remettre la première déclinaison par défaut pour les selects
                $('.soubassement-select').each(function() {
                    if ($(this).find('option').length > 0) {
                        $(this).find('option:first').prop('selected', true);
                    }
                });
                $('input[name="soubassement_id"]').val('');
            }
        }

        $('.soubassement.hide-row').removeClass('selected')
        refreshPrice(true);
    });

    // Fonctions utilitaires pour générer le HTML réutilisable
    var HtmlGenerator = {
        // SVG icons réutilisables
        svgIconNotSelected: '<svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected"><rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/><path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/></svg>',
        svgIconSelected: '<svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected"><rect y="0.5" width="38" height="38" rx="10" fill="white"/><path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/></svg>',
        
        // Extraire le nom court et la description d'un produit
        parseProductName: function(productName) {
            var newName = productName.split(' : ')[0];
            var description = productName.replace(newName, '').replace(' :', '').split(', ').join('<br>');
            return { name: newName, description: description };
        },
        
        // Générer le HTML d'un produit avec select (générique pour portillon, occultation et soubassement)
        productWithSelect: function(product, selectedVariationId, isSelected, type) {
            type = type || 'portillon'; // 'portillon', 'occultation' ou 'soubassement'
            var prefix = type === 'portillon' ? 'portillon-jardin' : (type === 'occultation' ? 'occultation' : 'soubassement');
            var dataName = type === 'portillon' ? 'portillon_id' : (type === 'occultation' ? 'occultation_id' : 'soubassement_id');
            var selectName = type === 'portillon' ? 'portillon_jardin_variation' : (type === 'occultation' ? 'occultation_variation' : 'soubassement_variation');
            var actionClass = type === 'portillon' ? 'portillon-jardin-action' : (type === 'occultation' ? 'occultation-action' : 'soubassement-action');
            
            var selectedVariationData = null;
            product.variations.forEach(function(variation) {
                if (variation.id_product_attribute == selectedVariationId) {
                    selectedVariationData = variation;
                }
            });
            var defaultVariation = selectedVariationData || (product.variations.length > 0 ? product.variations[0] : null);
            
            var html = '<div class="col-md-12">';
            html += '<a href="#" data-id="' + product.id + '" data-name="' + dataName + '" class="configurator-action ' + actionClass + ' d-flex mb-4' + (isSelected ? ' selected' : '') + '">';
            
            // Div 1 : Image
            html += '<div class="' + prefix + '-image">';
            html += '<img style="width: 100px;" src="' + product.image_url + '" class="img-responsive" alt="' + product.name + '" />';
            html += '</div>';
            
            // Div 2 : Titre produit et select
            html += '<div class="' + prefix + '-content">';
            html += '<div class="' + prefix + '-title">';
            html += '<span class="name font-weight-bold">' + product.short_name + '</span>';
            if (product.product_details) {
                html += '<span class="' + prefix + '-info-icon">';
                html += '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';
                html += '<circle cx="12" cy="12" r="10"></circle>';
                html += '<line x1="12" y1="16" x2="12" y2="12"></line>';
                html += '<line x1="12" y1="8" x2="12.01" y2="8"></line>';
                html += '</svg>';
                html += '<span class="' + prefix + '-info-tooltip">';
                
                // Caractéristiques
                if (product.product_details.grouped_features && product.product_details.grouped_features.length > 0) {
                    html += '<section class="product-features">';
                    html += '<dl class="data-sheet">';
                    product.product_details.grouped_features.forEach(function(feature) {
                        html += '<dt class="name">' + feature.name + '</dt>';
                        html += '<dd class="value">' + feature.value.replace(/\n/g, '<br>') + '</dd>';
                    });
                    html += '</dl>';
                    html += '</section>';
                }
                
                html += '</span>';
                html += '</span>';
            }
            html += '</div>';
            html += '<select name="' + selectName + '" data-id="' + product.id + '" data-name="' + dataName + '" class="form-control ' + prefix + '-select">';
            var self = this;
            product.variations.forEach(function(variation, index) {
                var isSelectedOption = variation.id_product_attribute == selectedVariationId || (selectedVariationId == '' && index === 0);
                // Utiliser name_without_title si disponible, sinon name
                var variationName = variation.name_without_title || variation.name;
                var imageUrlAttr = variation.image_url ? ' data-image-url="' + variation.image_url + '"' : '';
                html += '<option value="' + variation.id_product_attribute + '" data-id-product-attribute="' + variation.id_product_attribute + '" data-price="' + variation.price + '" data-price-formatted="' + variation.price_formatted + '"' + imageUrlAttr + (isSelectedOption ? ' selected' : '') + '>' + variationName + '</option>';
            });
            html += '</select>';
            html += '</div>';
            
            // Div 3 : Prix et "+"
            html += '<div class="' + prefix + '-price-action">';
            if (defaultVariation && defaultVariation.price_formatted) {
                html += '<div class="' + prefix + '-price" style="font-size: 16px;"><span class="price-value">' + defaultVariation.price_formatted + '</span></div>';
            } else {
                html += '<div class="' + prefix + '-price" style="font-size: 16px; display: none;"><span class="price-value"></span></div>';
            }
            html += '<div>';
            html += this.svgIconNotSelected;
            // SVG sélectionné (fond vert pour portillon et occultation)
            html += '<svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected"><rect y="0.5" width="38" height="38" rx="10" fill="#79C046"/><path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="white" stroke="white"/></svg>';
            html += '</div>';
            html += '</div>';
            
            html += '</a>';
            html += '</div>';
            return html;
        },
        
        // Générer le HTML d'un portillon avec select (alias pour compatibilité)
        portillonWithSelect: function(product, selectedVariationId, isSelected) {
            return this.productWithSelect(product, selectedVariationId, isSelected, 'portillon');
        },
        
        // Générer le HTML d'un portillon normal
        portillonNormal: function(product) {
            var parsed = this.parseProductName(product.name);
            var html = '<div class="col-md-12">';
            html += '<a href="#" data-id="' + product.id + '" data-id-product-attribute="' + product.id_product_attribute + '" data-name="portillon_id" class="configurator-action d-flex mb-4">';
            html += '<img style="padding:unset;margin:0;" src="' + product.image_url + '" class="img-responsive" alt="' + product.name + '" />';
            html += '<div class="title-img" style="display: flex;justify-content: space-between;flex-direction: column;"><div class="infos-top"><span class="name">' + parsed.name + '</span>' + this.svgIconNotSelected + this.svgIconSelected + '</div><span style="font-size: 13px;line-height: 16px;">' + parsed.description + '</span></span></div>';
            html += '</a>';
            html += '</div>';
            return html;
        },
        
        // Générer le HTML d'une occultation (avec ou sans select)
        occultation: function(product, selectedVariationId, isSelected) {
            selectedVariationId = selectedVariationId || '';
            isSelected = isSelected || false;
            
            // Si le produit a un select, utiliser la fonction générique
            if (product.has_select) {
                return this.productWithSelect(product, selectedVariationId, isSelected, 'occultation');
            }
            
            // Sinon, comportement normal
            var parsed = this.parseProductName(product.name);
            var html = '<div class="col-md-12">';
            html += '<a href="#" data-id="' + product.id + '" data-id-product-attribute="' + product.id_product_attribute + '" data-name="occultation_id" class="configurator-action d-flex mb-4">';
            html += '<img style="padding:unset;margin:0;" src="' + product.image_url + '" class="img-responsive" alt="' + product.name + '" />';
            html += '<div class="title-img" style="display: flex;justify-content: space-between;flex-direction: column;width: 100%;"><div class="infos-top"><span class="name">' + parsed.name + '</span>' + this.svgIconNotSelected + this.svgIconSelected + '</div><span style="font-size: 13px;line-height: 16px;width: 100%;">' + parsed.description + '</span></span></div>';
            html += '</a>';
            html += '</div>';
            return html;
        },
        
        soubassement: function(product, selectedVariationId, isSelected) {
            selectedVariationId = selectedVariationId || '';
            isSelected = isSelected || false;
            
            // Si le produit a un select, utiliser la fonction générique
            if (product.has_select) {
                return this.productWithSelect(product, selectedVariationId, isSelected, 'soubassement');
            }
            
            // Sinon, comportement normal (ne devrait pas arriver pour les soubassements)
            return '';
        }
    };
    
    // Fonction pour détecter le portillon sélectionné avec select
    function getSelectedPortillonJardin() {
        var portillonJardinSelected = false;
        var portillonJardinSelect = null;
        $('.portillon-jardin-select').each(function() {
            var $action = $(this).closest('.portillon-jardin-action');
            if ($(this).val() != '' && $action.hasClass('selected')) {
                portillonJardinSelected = true;
                portillonJardinSelect = $(this);
                return false;
            }
        });
        return { selected: portillonJardinSelected, select: portillonJardinSelect };
    }

    // Fonction pour détecter l'occultation sélectionnée avec select
    function getSelectedOccultation() {
        var occultationSelected = false;
        var occultationSelect = null;
        $('.occultation-select').each(function() {
            var $action = $(this).closest('.occultation-action');
            if ($(this).val() != '' && $action.hasClass('selected')) {
                occultationSelected = true;
                occultationSelect = $(this);
                return false;
            }
        });
        return { selected: occultationSelected, select: occultationSelect };
    }

    // Fonction pour détecter le soubassement sélectionné avec select
    function getSelectedSoubassement() {
        var soubassementSelected = false;
        var soubassementSelect = null;
        $('.soubassement-select').each(function() {
            var $action = $(this).closest('.soubassement-action');
            if ($(this).val() != '' && $action.hasClass('selected')) {
                soubassementSelected = true;
                soubassementSelect = $(this);
                return false;
            }
        });
        return { selected: soubassementSelected, select: soubassementSelect };
    }

    function refreshPrice(change) {

        let grillage = {
            type: $(".configurator-form input[name='type']").val(),
            length: $(".configurator-form input[name='longueur']").val(),
            depart_mur: $(".configurator-form input[name='depart_mur']").val(),
            nombre_angle: $(".configurator-form input[name='nombre_angle']").val(),
            installation: $(".configurator-form input[name='pose']").val(),
            height: $(".configurator-form input[name='hauteur']").val(),
            color: $(".configurator-form input[name='couleur']").val(),
            espacement: $(".configurator-form input[name='espacement']").val(),
            poteaux: $(".configurator-form input[name='poteaux']").length > 0 ? $(".configurator-form input[name='poteaux']").val() : null,
        };

        var sousbassementCheckbox = $(".configurator-form.options-form .soubassement.form-title").hasClass('selected');
        // Vérifier si un soubassement avec select est explicitement sélectionné (classe 'selected')
        var soubassementSelect = getSelectedSoubassement();
        var sousbassement = soubassementSelect.selected || sousbassementCheckbox;
        
        // Vérifier si un portillon avec select est explicitement sélectionné (classe 'selected')
        var portillonJardin = getSelectedPortillonJardin();
        // Un portillon est actif seulement si un portillon est explicitement sélectionné (normal ou avec select)
        var portillon = $('.configurator-action.selected[data-name="portillon_id"]').length > 0 || portillonJardin.selected;
        // Vérifier si une occultation avec select est explicitement sélectionnée (classe 'selected')
        var occultationSelect = getSelectedOccultation();
        var occultation = $(".configurator-form.options-form .occultation.form-title").hasClass('selected');
        var panneau_deco = $("#checkbox_panneau_deco").hasClass('selected');
        var service = $('[data-name="service_id"]').hasClass('selected');

        if (grillage.espacement == 0 && grillage.type == 310) {
            grillage.espacement = 1;
        }

        if (portillon || occultation || panneau_deco || service || sousbassement) {
            change = true;
        }

        $.ajax({
            url: window.location.href, method: "post", data: {
                ajax: true, updateprice: true, grillage: grillage, sousbassement: {
                    active: sousbassement,
                    height: soubassementSelect.select && soubassementSelect.select.val() && soubassementSelect.select.closest('.soubassement-action').hasClass('selected')
                        ? soubassementSelect.select.data('id')
                        : ($('.configurator-action.selected[data-name="soubassement_id"]').length > 0
                            ? $('.configurator-action.selected[data-name="soubassement_id"]').data('id')
                            : ''),
                    color: soubassementSelect.select && soubassementSelect.select.val() && soubassementSelect.select.closest('.soubassement-action').hasClass('selected')
                        ? soubassementSelect.select.val()
                        : ($('.configurator-action.selected[data-name="soubassement_id"]').length > 0
                            ? $('.configurator-action.selected[data-name="soubassement_id"]').data('id-product-attribute')
                            : ''),
                }, occultation: {
                    active: occultation,
                    id: occultationSelect.select && occultationSelect.select.val() && occultationSelect.select.closest('.occultation-action').hasClass('selected')
                        ? occultationSelect.select.data('id') 
                        : ($('.configurator-action.selected[data-name="occultation_id"]').length > 0 
                            ? $('.configurator-action.selected[data-name="occultation_id"]').data('id') 
                            : $('input[name="occultation_id"]').val()),
                    id_product_attribute: occultationSelect.select && occultationSelect.select.val() && occultationSelect.select.closest('.occultation-action').hasClass('selected')
                        ? occultationSelect.select.val()
                        : ($('.configurator-action.selected[data-name="occultation_id"]').length > 0
                            ? $('.configurator-action.selected[data-name="occultation_id"]').data('id-product-attribute')
                            : ''),
                },
                panneau_deco: {
                    active: panneau_deco,
                    id: $('.configurator-action.selected[data-name="panneau_deco_id"]').data('id'),
                    id_product_attribute: $('.configurator-action.selected[data-name="panneau_deco_id"]').data('id-product-attribute'),
                },
                portillon: {
                    active: portillon,
                    id: portillonJardin.select && portillonJardin.select.val() && portillonJardin.select.closest('.portillon-jardin-action').hasClass('selected')
                        ? portillonJardin.select.data('id') 
                        : $('.configurator-action.selected[data-name="portillon_id"]').data('id'),
                    id_product_attribute: portillonJardin.select && portillonJardin.select.val() && portillonJardin.select.closest('.portillon-jardin-action').hasClass('selected')
                        ? portillonJardin.select.val()
                        : $('.configurator-action.selected[data-name="portillon_id"]').data('id-product-attribute'),
                },
                service : {
                    active: service,
                    id: $('[data-name="service_id"].selected').data('id'),
                }
            }, dataType: 'json', success: function (response) {
                if (!change) {
                    if (response.portillons.length > 0) {
                        $(".portillon .row").html('');
                        response.portillons.forEach(function (product, index) {
                            var html = '';
                            if (product.has_select && product.variations) {
                                var selectedVariation = response.selected_portillon_id_product_attribute || '';
                                var isSelected = response.selected_portillon_id == product.id;
                                html = HtmlGenerator.portillonWithSelect(product, selectedVariation, isSelected);
                            } else {
                                html = HtmlGenerator.portillonNormal(product);
                            }
                            $(".portillon .row").html($(".portillon .row").html() + html);
                        });

                        // Sélection automatique pour les portillons normaux
                        if ($('*[data-name="portillon_id"][data-id-product-attribute="' + response.selected_portillon_id_product_attribute + '"]').length > 0) {
                            $('*[data-name="portillon_id"][data-id-product-attribute="' + response.selected_portillon_id_product_attribute + '"]').trigger('click');
                        }
                        // Pour le portillon jardin, la sélection est déjà gérée par l'attribut "selected" dans le select
                    }

                    if (response.occultations.length > 0) {
                        $(".occultation .row").html('');
                        response.occultations.forEach(function (product, index) {
                            var selectedVariation = response.selected_occultation_id_product_attribute || '';
                            var isSelected = response.selected_occultation_id == product.id;
                            $(".occultation .row").html($(".occultation .row").html() + HtmlGenerator.occultation(product, selectedVariation, isSelected));
                        });
                        // Sélection automatique pour les occultations normales
                        if ($('*[data-name="occultation_id"][data-id-product-attribute="' + response.selected_occultation_id_product_attribute + '"]').length > 0) {
                            $('*[data-name="occultation_id"][data-id-product-attribute="' + response.selected_occultation_id_product_attribute + '"]').trigger('click');
                        }
                        // Pour les occultations avec select, la sélection est déjà gérée par l'attribut "selected" dans le select
                    }
                    if (response.sousbassement && response.sousbassement.length > 0) {
                        $(".soubassement.hide-row .row").html('');
                        response.sousbassement.forEach(function (product, index) {
                            var selectedVariation = response.selected_soubassement_id_product_attribute || '';
                            var isSelected = response.selected_soubassement_id == product.id;
                            
                            // Vérifier si la déclinaison sélectionnée existe toujours dans les variations filtrées
                            // Si elle n'existe plus (car filtrée selon le type de poteau), réinitialiser
                            if (selectedVariation && product.variations) {
                                var variationExists = product.variations.some(function(variation) {
                                    return variation.id_product_attribute == selectedVariation;
                                });
                                if (!variationExists) {
                                    selectedVariation = '';
                                    isSelected = false;
                                    // Désélectionner le soubassement si la déclinaison n'existe plus
                                    $('.configurator-action[data-name="soubassement_id"]').removeClass('selected');
                                    $('#checkbox_soubassement').removeClass('selected');
                                    $('input[name="soubassement_id"]').val('');
                                }
                            }
                            
                            $(".soubassement.hide-row .row").html($(".soubassement.hide-row .row").html() + HtmlGenerator.soubassement(product, selectedVariation, isSelected));
                        });
                        // Pour les soubassements avec select, la sélection est déjà gérée par l'attribut "selected" dans le select
                    }
                    if (response.panneaux_deco.length > 0) {
                        $(".panneau_deco .row").html('');
                        response.panneaux_deco.forEach(function (product, index) {
                            html = '<div class="col-xs-6 pl-0">';
                            html += '<a href="#" data-id="' + product.id + '" data-id-product-attribute="' + product.id_product_attribute + '" data-name="panneau_deco_id" class="configurator-action">';
                            html += '<img src="' + product.image_url + '" class="img-responsive" alt="' + product.name + '" />';
                            html += '<div class="title-img">' + product.name + '<svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected"><rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/><path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/></svg><svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected"><rect y="0.5" width="38" height="38" rx="10" fill="white"/><path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/></svg></div>';
                            html += '</a>';
                            html += '</div>';
                            $(".panneau_deco .row").html($(".panneau_deco .row").html() + html);
                        });

                        if ($('*[data-name="panneau_deco_id"][data-id-product-attribute="' + response.selected_panneau_deco_id_product_attribute + '"]').length > 0) {
                            $('*[data-name="panneau_deco_id"][data-id-product-attribute="' + response.selected_panneau_deco_id_product_attribute + '"]').trigger('click');
                        }
                    }
                }

                if (change && response.selected_portillon_id_product_attribute == "") {
                    if (response.portillons.length > 0) {
                        $(".portillon .row").html('');
                        response.portillons.forEach(function (product, index) {
                            var html = '';
                            if (product.has_select && product.variations) {
                                var selectedVariation = response.selected_portillon_id_product_attribute || '';
                                var isSelected = response.selected_portillon_id == product.id;
                                html = HtmlGenerator.portillonWithSelect(product, selectedVariation, isSelected);
                            } else {
                                html = HtmlGenerator.portillonNormal(product);
                            }
                            $(".portillon .row").html($(".portillon .row").html() + html);
                        });

                        // Sélection automatique pour les portillons normaux
                        if ($('*[data-name="portillon_id"][data-id-product-attribute="' + response.selected_portillon_id_product_attribute + '"]').length > 0) {
                            $('*[data-name="portillon_id"][data-id-product-attribute="' + response.selected_portillon_id_product_attribute + '"]').trigger('click');
                        }
                        // Pour le portillon jardin, la sélection est déjà gérée par l'attribut "selected" dans le select
                    }
                }

                if (change && response.selected_occultation_id_product_attribute == "") {
                    if (response.occultations.length > 0) {
                        $(".occultation .row").html('');
                        response.occultations.forEach(function (product, index) {
                            var selectedVariation = response.selected_occultation_id_product_attribute || '';
                            var isSelected = response.selected_occultation_id == product.id;
                            $(".occultation .row").html($(".occultation .row").html() + HtmlGenerator.occultation(product, selectedVariation, isSelected));
                        });
                        // Sélection automatique pour les occultations normales
                        if ($('*[data-name="occultation_id"][data-id-product-attribute="' + response.selected_occultation_id_product_attribute + '"]').length > 0) {
                            $('*[data-name="occultation_id"][data-id-product-attribute="' + response.selected_occultation_id_product_attribute + '"]').trigger('click');
                        }
                        // Pour les occultations avec select, la sélection est déjà gérée par l'attribut "selected" dans le select
                    }
                }

                let serviceTitles = $('#servicesPrev').text()
                /*console.log(response.serviceTitles)*/

                if (null !== response.serviceTitles && JSON.stringify(response.serviceTitles) != serviceTitles){
                    $('.fourth .hide-row > .row-items > .col-md-12').html('')
                    htmlService = '';
                    response.services.forEach(function (service, key) {
                        htmlService += '<a href="#" data-id="'+service.dealt_service_product_id+'"'
                        htmlService += ' data-name="service_id" class="configurator-action d-flex mb-4">'
                        htmlService += '<div class="title-img" style="display: flex;justify-content: space-between;flex-direction: column;width: 100%;">'
                        htmlService += '<div class="infos-top">'
                        htmlService += '<span class="name">'+response.serviceTitles[key]+'</span>'
                        htmlService += '<svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">'
                        htmlService += '<rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>'
                        htmlService += '<path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>'
                        htmlService += '</svg>'
                        htmlService += '<svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">'
                        htmlService += '<rect y="0.5" width="38" height="38" rx="10" fill="white"/>'
                        htmlService += '<path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/>'
                        htmlService += '</svg>'
                        htmlService += '</div>'

                        htmlService += '<span style="font-size: 13px;line-height: 16px;width: 100%;">'
                        htmlService +=  service.dealt_customer_commercial_description
                        htmlService += '</span>'
                        htmlService += '</div>'
                        htmlService += '</a>'
                    });

                    $('.fourth .hide-row > .row-items > .col-md-12').append(htmlService)
                    $('.fourth .form-title').removeClass('selected')

                    $('#servicesPrev').text(JSON.stringify(response.serviceTitles))
                } else if (change && null == response.serviceTitles && JSON.stringify(response.serviceTitles) != serviceTitles) {
                    htmlService = '<div class="title-img" style="display: flex;justify-content: space-between;flex-direction: column;width: 100%;">'
                    htmlService += '<div class="infos-top">'
                    htmlService += '<span class="name">Pas de services pour ce produit.</span>'
                    htmlService += '</div>'
                    htmlService += '</div>'

                    $('.fourth .hide-row > .row-items > .col-md-12').html('')
                    $('.fourth .hide-row > .row-items > .col-md-12').append(htmlService)
                    $('.fourth .form-title').removeClass('selected')
                    /*                    $('.fourth .hide-row').css('display', 'none')*/
                    $('#servicesPrev').text(JSON.stringify(response.serviceTitles))
                }

                viewInformationAlum();
                // Preview.render(response.preview);
                Session.render(response);

                if (response.sousbassement_id == 416) {
                    $('.configurator-preview-portillon img').css({"position": "relative", "top": "15px"});
                } else if (response.sousbassement_id == 806) {
                    //
                } else {
                    $('.configurator-preview-portillon img').css('position:relative; top:0;');
                }
            }
        });
    }

    $("body").on('click', '.configurator-action', function (event) {
        event.preventDefault();
        viewInformationAlum();
    });

    function viewInformationAlum() {
        const isAlu = isAluminium();

        var dataId = $(this).attr('data-id');

        // if (dataId == 310) {
        //     $('.viewEspacement').hide();
        //     var espacementValue = $('#espacement').val();
        //
        //     if (espacementValue === undefined || espacementValue == 0)
        //         $('#espacement').attr('value', 1);
        //
        // }

        var grillage_type = $(".configurator-form input[name='type']").val();

        if (grillage_type !== undefined) {
            if (grillage_type == 309) {
                $('.onlyAlu.displayEspacement').hide();
                $('.onlyAlu.displayDepartMur').show();
            }
            if (grillage_type == 310) {
                $('.onlyAlu.displayDepartMur').show();
                $('.onlyAlu.displayEspacement').hide();
            }
        } else {
            $('.displayEspacement').hide();
        }

        if (isAlu == false) {
            $('.displayEspacement').hide();
            $('.onlyAlu.displayEspacement').hide();
            $('.onlyAlu.displayDepartMur').hide();
            $('.viewDepartMur').hide();
            $('.viewNombreAngle').hide();
        } else {
            $('.viewDepartMur').show();
            $('.viewNombreAngle').show();
        }
    }


// GETTING SOUBASSEMENT COLORS
    $("body").on('change', '#sousbassement_hauteur', function () {
        $.ajax({
            url: window.location.href, method: "post", data: {
                ajax: true, soubassement: true, height: $('#sousbassement_hauteur').val()
            }, dataType: 'json', success: function (response) {
                $("#sousbassement_couleur_row").html('');
                response.colors.forEach(function (color, index) {
                    html = '<div class="col-xs-6">';
                    html += '<a href="#" data-id="' + color.id_product_attribute + '" data-name="sousbassement_couleur" class="configurator-action">';
                    html += '<img src="' + color.image_url + '" class="img-responsive" alt="' + color.name + '" />';
                    html += '<div class="title-img">' + color.name + '<svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected"><rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/><path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/></svg><svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected"><rect y="0.5" width="38" height="38" rx="10" fill="white"/><path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/></svg></div>';
                    html += '</a>';
                    html += '</div>';
                    $("#sousbassement_couleur_row").html($("#sousbassement_couleur_row").html() + html);
                    if (index == 0) {
                        $("input[name='sousbassement_couleur']").val(color.id_product_attribute)
                    }
                });


                throttle(function () {
                    refreshPrice();
                    // Exécutez votre script Ajax ici
                }, interval);

            }
        });
    });

    let isWaiting = false;

    function throttle(func, interval) {
        if (!isWaiting) {
            func();
            isWaiting = true;
            setTimeout(function () {
                isWaiting = false;
            }, interval);
        }
    }

    $("body").on('click', '.pose-item', function (event) {
        event.preventDefault();
        const isAlu = isAluminium();
        var id_installation = $(this).attr('data-id');
        if ("scellement-sur-platine" !== id_installation && isAlu) {
            $(".item-height").each(function () {
                $(this).show();
            })
        }
        if (isRigide()) {
            if ($('input[name="pose"]').val() === 'scellement-sur-platine') {
                $('#poteaux').hide()
            }else{
                $('#poteaux').show()
            }
        }

        viewEspacement();
    });

// UP OR DOWN QUANTITY
    $("body").on('click', '.select_number', function (event) {
        event.preventDefault();

        var action = $(this).data('action');
        var id = $(this).data('id');

        $.ajax({
            url: window.location.href, method: "post", data: {
                ajax: true, updateQuantity: true, action: action, id: id,
            }, dataType: 'json', success: function (response) {
                // Preview.render(response.preview);
                Session.render(response);
            }
        });
    });

    let Preview = {

        base_img: '<img class="img-responsive" alt="" src="/modules/ckc_configurator/views/images/',
        end_img: '" />',
        render: function (preview) {

            // if (preview !== false) {
            //     $(".configurator-preview-base").html(this.base_img + preview.base + this.end_img);
            //     $(".configurator-preview-panneau").html(this.base_img + preview.panneau + this.end_img);
            //     $(".configurator-preview-poteau").html(this.base_img + preview.poteau + this.end_img);
            //
            //     if (preview.jambe !== "jdf/") {
            //         $(".configurator-preview-jambe").html(this.base_img + preview.jambe + this.end_img);
            //     }else{
            //         $(".configurator-preview-jambe").html("")
            //     }
            //
            //     if (preview.occultation.length > 0) {
            //         $(".configurator-preview-occultation").html(this.base_img + preview.occultation + this.end_img);
            //     } else {
            //         $(".configurator-preview-occultation").html('');
            //     }
            //
            //     if (preview.portillon.length > 0) {
            //         $(".configurator-preview-portillon").html(this.base_img + preview.portillon + this.end_img);
            //     } else {
            //         $(".configurator-preview-portillon").html('');
            //     }
            // }
        }
    };

    let Session = {
        set: function (session, price) {
            let $type = $("#grillage_type");
            let $length = $("#grillage_length");
            let $depart_mur = $("#grillage_depart_mur");
            let $nombre_angle = $("#grillage_nombre_angle");
            let $installation = $("#grillage_installation");
            let $height = $("#grillage_height");
            let $color = $("#grillage_color");
            let $price = $("#price_value");
            let $secondPrice = $("#second_price");
            let $deconseil = $("#deconseil");
            let $espacement = $("#grillage_espacement");

            if (session.deconseil) {
                $deconseil.show();
            } else {
                $deconseil.hide();
            }

            if (session.type_name == null) {
                const selectedElement = $('.configurator-action.selected[data-name="type"]');

                if (selectedElement.length > 0) {

                    const titleElement = selectedElement.find('.title-img');

                    session.type_name = titleElement.text();
                }
            }

            $type.text(session.type_name);
            $length.text(session.length);
            $depart_mur.text(session.depart_mur);
            $nombre_angle.text(session.nombre_angle);
            $installation.text(session.installation_formatted);
            $height.text(session.height_formatted);
            $color.text(session.color_formatted);
            $espacement.text(session.espacement);

            $price.text(price);
            $secondPrice.text(price+' €');
        }, setProducts: function (products) {
            let session_products = [];
            let totalRouleau
            $products.html('');

            products.forEach(function (product) {

                if (product.name.includes('Grillage simple')) {
                    totalRouleau = product.quantity * 2
                }

                let newName = (product.name).split(' : ')[0]
                let description = (product.name).replace(newName, '').replace(' :', '').split(', ').join('<br>');

                html = "<li class='clearfix product-line' style='display: flex;align-items: center;flex-direction: column;' data-product='" + JSON.stringify(product) + "'>";
                html += "<div style='width: 100%'>";
                html += " <b>" + newName + "</b>";
                html += '</div>';
                html += "<div style='width: 100%'>";
                html += "<div style='float: left; width: 40%'>";
                html += "<p style='line-height: 16px;'>" + description + "</p>";
                html += "</div>";

                html += "<div style='float: left; margin-left: 2px; margin-right: 10px; width: 40%; display: flex; justify-content: center; align-items: center;'>";
                html += "<div class='custom-quantity-selector'>";
                if (!product.name.includes("Pose de") && !product.name.includes("Pose d'un") && !product.name.includes("Pose d'") && !product.name.includes("Installation d'un")) {

                    html += "<button type='button' class='select_number btn-quantity' data-action='less' data-id='" + product.id + "'>";
                    html += "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round' class='lucide lucide-minus'>";
                    html += "<path d='M5 12h14'></path>";
                    html += "</svg>";
                    html += "</button>";

                    html += "<input type='number' disabled name='qty' inputmode='numeric' pattern='[0-9]*' value='"+(product.name.includes('Barre de tension') ? totalRouleau : product.quantity)+"' min='1' class='quantity-input' aria-label='Quantité'/>";

                    html += "<button type='button' class='btn-quantity select_number' data-action='more' data-id='" + product.id + "'>";
                    html += "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round' class='lucide lucide-plus'>";
                    html += "<path d='M5 12h14'></path>";
                    html += "<path d='M12 5v14'></path>";
                    html += "</svg>";
                    html += "</button>";
                }
                html += "</div>";
                html += "</div>"; // Fermeture du div contenant les boutons et la quantité

                html += "<div style='font-size: 16px;'>" + product.price + "€</div>";
                html += '</div>';
                html += "</li>";
                $products.html($products.html() + html);
            });
        }, render: function (data) {
            this.set(data.session, data.price);
            this.setProducts(data.products);
        }
    };


});

function isAluminium() {
    if ($('.configurator-first .configurator-action.selected').attr('data-id') === "308") {
        return true;
    }
    return false;
}

function isRigide() {
    if ($('.configurator-first .configurator-action.selected').attr('data-id') === "173") {
        return true;
    }
    return false;
}

function viewEspacement() {
    var dataId = $('input[name="type"]').val();

    if (dataId == 309 || dataId == undefined) {
        $('.onlyAlu.displayDepartMur').show();
        $('.onlyAlu.displayEspacement').hide();
        $('.viewEspacement').hide();
    }

    if (dataId == 310) {
        $('.onlyAlu.displayEspacement').hide();
        $('.onlyAlu.displayDepartMur').show();

        $('.viewEspacement').hide();
        // var espacementValue = $('#espacement').val();
        // if (espacementValue === undefined || espacementValue == 0)
        //     $('#espacement').attr('value', 1);
    }

}

/*document.addEventListener('DOMContentLoaded', function () {
  if(window.innerWidth > 1200) {
      const stickyElement = document.querySelector('.mobil_session');
        const placeholder = document.createElement('div');
        let offsetTop = 0;
        let bottomLimit = 0;

        const updateOffsets = () => {
          offsetTop = stickyElement.offsetTop;
          bottomLimit = stickyElement.offsetParent.offsetTop + stickyElement.offsetParent.offsetHeight;
          placeholder.style.height = stickyElement.offsetHeight + 'px';
        };

        const stick = () => {
          const scrollY = window.scrollY;
          if ((scrollY > offsetTop - 200) && (scrollY + stickyElement.offsetHeight + 151 < bottomLimit)) {
            if (!stickyElement.classList.contains('is-sticky')) {
              stickyElement.classList.add('is-sticky');
              stickyElement.style.position = 'fixed';
              stickyElement.style.top = '151px';
              stickyElement.style.right = '0';
              stickyElement.style.width = stickyElement.offsetWidth + 'px';
              stickyElement.style.zIndex = '1002';
              stickyElement.parentNode.insertBefore(placeholder, stickyElement);
              placeholder.style.display = 'block';
            }
          } else {
            if (stickyElement.classList.contains('is-sticky')) {
              stickyElement.classList.remove('is-sticky');
              stickyElement.style.position = '';
              stickyElement.style.top = '';
              stickyElement.style.right = '';
              stickyElement.style.width = '';
              stickyElement.style.zIndex = '';
              if (placeholder.parentNode) {
                placeholder.style.display = 'none';
                placeholder.remove();
              }
            }
          }
        };

        // Initial call
        updateOffsets();
        stick();

        window.addEventListener('scroll', stick);
        window.addEventListener('resize', () => {
          updateOffsets();
          stick();
        });

        // Observe DOM changes (si un élément se montre/cache)
        const observer = new MutationObserver(() => {
          updateOffsets();
          stick();
        });

        observer.observe(document.body, { childList: true, subtree: true, attributes: true });

  }
});*/



