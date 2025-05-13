{$formCategory}
<div class="panel">
    <input type="hidden" value="" id="categoryFieldContent" name="categoryFieldContent">
    <div class="col-sm category-selection-container">
        <div class="d-block">
            <h4>{l s='Select Categories where to show the promo content :' mod='ckcadcampaignconfigurator'}</h4>
            <div class="input-group mb-3 category-search-container">
                <input type="text" id="idInput" class="form-control" placeholder="Search for a product">
                <div id="suggested-categories-container"></div>
            </div>
            {* <input type="hidden" value="" id="selectedProducts" name="selectedProducts"> *}
        </div>
        <div class="d-block selected-categories-container">
            <h4>{l s='Selected Categories :' mod='ckcadcampaignconfigurator'}</h4>
            <div id="selectedIds"></div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var modulePath = "{$modulePath}";
    var allCategories = {$allCategories}
    var categorySelection = {$categorySelection}

    if (categorySelection != 0) {
        var selectedIds = categorySelection
        updateIdsDisplay();
    } else {
        var selectedIds = []
    }

    $("#idInput").on("input", function() {
        let query = $(this).val();
        if(query.length > 0) {
            $.ajax({
                type: "POST",
                url: modulePath,
                data: {literal}{
                    controller : 'AdminAjaxCkcadcampaignconfigurator',
                    "query": query,
                    action: "SearchCategory",
                    ajax : true
                }{/literal},
                success: function(responseData){
                    let decodedData = JSON.parse(responseData)
                    console.log(decodedData)
                    if(decodedData.length > 0) {
                        $("#suggested-categories-container").empty()
                        for (decodedProduct of decodedData) {
                            let element = '<span class="suggested-product" data-id="'+decodedProduct.id_category+'"><span class="product-name">'+decodedProduct.name+'</span><div class="btn btn-primary addIdButton" id="addIdButton">+</div></span><br>'
                            $("#suggested-categories-container").append(element)
                        }
                    }
                }
            })
        }
    });

    $(document).on("click", ".addIdButton", function(e) {
        e.preventDefault();
        console.log(selectedIds)
        let productId = $(this).parent().attr("data-id");
        let productName = $(this).prev().text()

        let product = {
            id_category : productId,
            name : productName
        }
        let ifExists = selectedIds.findIndex(e => e.id_category == product.id_category);
        if (ifExists < 0) {
            selectedIds.push(product);
            updateIdsDisplay();
        }
    });

    $(document).on("click", ".remove", function() {
        let idToRemove = $(this).parent().attr("data-id");
        selectedIds = selectedIds.filter(product => {
            return product.id_category != idToRemove
        } );
        updateIdsDisplay();
    });

    function updateIdsDisplay() {
        $("#selectedIds").empty();
        selectedIds.forEach((product) => {
            elementTag = '<span class="tag" data-id="'+product.id_category+'">'+product.name+'<span class="remove">✖</span></span>'
            $("#selectedIds").append(elementTag)
        });
        let selectedJson = []
        for(selectedId of selectedIds) {
            selectedJson.push(JSON.stringify(parseInt(selectedId.id_category,10)))
        }
        $("#CATEGORY_LIST").val(selectedJson.join(","));
    }

</script>