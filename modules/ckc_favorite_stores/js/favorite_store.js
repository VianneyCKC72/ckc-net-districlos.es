document.addEventListener("DOMContentLoaded", function () {
    $(".setStore").click(function (e) {
        e.preventDefault()
        var id_store = null;

        var classList = $(this).attr("class").split(/\s+/);
        $.each(classList, function (index, item) {
            if (item.includes("shopId-")) {
                id_store = item.replace("shopId-", "");
            }
        });

        $(".setStore").each(function () {
            var classList = $(this).attr("class").split(/\s+/);
            $(this).removeClass("selected-1");
            $(this).removeClass("selected-0");
            $(this).addClass("selected-0");
            if (!$('body').hasClass('cms-id-11')) {
                $(this).show();
                $(this).next().css("display", "none");
            }
        });
        $(this).removeClass("selected-1");
        $(this).removeClass("selected-0");
        $(this).addClass("selected-1");
        if (!$('body').hasClass('cms-id-11')) {
            $(this).hide();
            $(this).next().css("display", "block");
        }
        setStore(id_store, $(this));
    });

    function setStore(id_store, el) {
        $.post(
            "/module/ckc_favorite_stores/favoritestore",
            {
                action: "add",
                id_store: id_store,
            },
            null,
            "json"
        ).then(function (resp) {
            if (!$('body').hasClass('cms-id-11')) {
                addSelectShopBtn();
            }else{
                displaySelectedStore(id_store)
            }
            
        });
    }
    
    function displaySelectedStore(id_store) {
        $(".setStore").each(function () {
            if ($(this).hasClass('shopId-' + id_store)) {
                $(this).removeClass("selected-0").addClass("selected-1");
            }
        });
    }
    
    function getStore() {
        $.ajax({
            url: "/module/ckc_favorite_stores/favoritestore",
            type: "GET",
            data: { action: "get" },
            dataType: "json"
        }).then(function (resp) {
            if (resp.id_store) {
                displaySelectedStore(resp.id_store)
            }
        });
    }

    function addSelectShopBtn() {
        $(".setStore").each(function (index) {
            if ($(this).hasClass("selected-1")) {
                $(this).hide();
                $(this).next().css("display", "block");
            } else {
                $(this).show();
                $(this).next().css("display", "none");
            }
        });
    }
    
    if ($('body').hasClass('cms-id-11')) {
        getStore()
    }
});
