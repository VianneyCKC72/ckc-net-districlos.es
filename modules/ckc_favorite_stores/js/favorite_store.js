document.addEventListener("DOMContentLoaded", function () {
    var endpoint =
        typeof ckcFavoriteStoreUrl !== "undefined" && ckcFavoriteStoreUrl
            ? ckcFavoriteStoreUrl
            : "/module/ckc_favorite_stores/favoritestore";

    prepareDetailButton();

    $(document).on("click", ".setStore", function (e) {
        e.preventDefault();

        var $btn = $(this);
        var id_store = getShopId($btn);
        if (!id_store) {
            return;
        }

        if ($btn.hasClass("selected-1") || $btn.find("svg").hasClass("add-favori")) {
            updateUi(null);
            postFavorite("remove", id_store);
            return;
        }

        updateUi(id_store);
        postFavorite("add", id_store);
    });

    function prepareDetailButton() {
        if (typeof ckcFavoriteStoreId === "undefined" || !ckcFavoriteStoreId) {
            return;
        }
        $(".magasin-detail a.favoris").each(function () {
            var $el = $(this);
            $el.addClass("setStore selected-0 shopId-" + ckcFavoriteStoreId);
        });
    }

    function getShopId($btn) {
        var id = null;
        $.each(($btn.attr("class") || "").split(/\s+/), function (i, cls) {
            if (cls.indexOf("shopId-") === 0) {
                id = cls.replace("shopId-", "");
            }
        });
        if (!id && typeof ckcFavoriteStoreId !== "undefined" && ckcFavoriteStoreId) {
            id = String(ckcFavoriteStoreId);
        }
        return id;
    }

    function isListing() {
        return $("body").hasClass("cms-id-11");
    }

    function isDetail() {
        return $(".magasin-detail").length > 0;
    }

    function updateUi(id_store) {
        $(".setStore").each(function () {
            var $el = $(this);
            var selected = !!id_store && $el.hasClass("shopId-" + id_store);
            $el.toggleClass("selected-1", selected).toggleClass("selected-0", !selected);
            if (isDetail()) {
                $el.find("svg").toggleClass("add-favori", selected);
            }
            if (isListing()) {
                if (selected) {
                    $el.hide().next().css("display", "block");
                } else {
                    $el.show().next().css("display", "none");
                }
            }
        });
    }

    function postFavorite(action, id_store) {
        $.post(endpoint, { action: action, id_store: id_store }, null, "json");
    }

    if (isListing() || isDetail()) {
        $.getJSON(endpoint, { action: "get" }).done(function (resp) {
            if (resp && resp.id_store) {
                updateUi(resp.id_store);
            }
        });
    }
});
