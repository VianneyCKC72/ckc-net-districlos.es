/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
$(window).ready(function() {
    const tabs = [
        "menuContent",
        "headerContent",
        "cartContent",
        "categoryContent",
        "productContent",
        "devisContent",
        "promoPageContent"
    ];
    const adminLink = moduleAdminLink.replace(/&amp;/g, "&");

    function getTabFromUrl() {
        const page = new URL(window.location.href).searchParams.get("page");
        return tabs.indexOf(page) !== -1 ? page : null;
    }

    window.vMenu = new Vue({
        el: "#modulecontent",
        data: {
            selectedTabName: tabs.indexOf(currentPage) !== -1 ? currentPage : "menuContent",
            popStateHandler: null
        },
        methods: {
            makeActive: function(item){
                if (tabs.indexOf(item) === -1 || this.selectedTabName === item) {
                    return;
                }

                this.selectedTabName = item;
                const separator = adminLink.indexOf("?") === -1 ? "?" : "&";
                window.history.pushState(
                    { campaignTab: item },
                    "",
                    adminLink + separator + "page=" + encodeURIComponent(item)
                );
            },
            isActive : function(item){
                return this.selectedTabName === item;
            }
        },
        mounted: function() {
            const vm = this;
            this.popStateHandler = function() {
                const page = getTabFromUrl();
                if (page) {
                    vm.selectedTabName = page;
                }
            };
            window.addEventListener("popstate", this.popStateHandler);
        },
        beforeDestroy: function() {
            window.removeEventListener("popstate", this.popStateHandler);
        }
    });
});
