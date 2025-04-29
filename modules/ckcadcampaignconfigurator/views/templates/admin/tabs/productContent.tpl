{$formProduct}
<div class="panel">
    <h3 style="margin-top: 1px !important; border: none; margin-bottom: 5px; margin-left: -7px;" class="h3-ckctitle ckch3"><i class="icon icon-link"></i> {l s='PRODUCT SELECTION' mod='ckcadcampaignconfigurator'}</h3>
    <div class="col-sm product-selection-container">
            <div class="d-block">
                <h4>Select Products where to show the promo content :</h4>
                <div class="input-group mb-3 product-search-container">
                    <input type="text" id="idInput" class="form-control" placeholder="Search for a product">
                    <div id="suggested-products-container"></div>
                </div>
                {* <input type="hidden" value="" id="selectedProducts" name="selectedProducts"> *}
            </div>
            <div class="d-block selected-products-container">
                <h4>Selected Products :</h4>
                <div id="selectedIds"></div>
            </div>
    </div>
</div>
<script type="text/javascript">

    var modulePath = "{$modulePath}";
    var moduleAdminLink = "{$moduleAdminLink|escape:'htmlall':'UTF-8'}";
    var productSelection = {$productSelection}

    $(document).ready(function() {

        if (productSelection != 0) {
            var selectedIds = productSelection
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
                        action: "SearchProduct",
                        ajax : true
                    }{/literal},
                    success: function(responseData){
                        let decodedData = JSON.parse(responseData)
                        console.log(decodedData)
                        if(decodedData.length > 0) {
                            $("#suggested-products-container").empty()
                            for (decodedProduct of decodedData) {
                                let element = '<span class="suggested-product" data-id="'+decodedProduct.id_product+'"><span class="product-name">'+decodedProduct.name+' ( Category : '+decodedProduct.category+' )</span><div class="btn btn-primary addIdButton" id="addIdButton">+</div></span><br>'
                                $("#suggested-products-container").append(element)
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
                id_product : productId,
                name : productName
            }
            let ifExists = selectedIds.findIndex(e => e.id_product == product.id_product);
            if (ifExists < 0) {
                selectedIds.push(product);
                updateIdsDisplay();
            }
        });

        $(document).on("click", ".remove", function() {
            let idToRemove = $(this).parent().attr("data-id");
            selectedIds = selectedIds.filter(product => {
                return product.id_product != idToRemove
            } );
            updateIdsDisplay();
        });

        function updateIdsDisplay() {
            $("#selectedIds").empty();
            selectedIds.forEach((product) => {
                elementTag = '<span class="tag" data-id="'+product.id_product+'">'+product.name+'<span class="remove">✖</span></span>'
                $("#selectedIds").append(elementTag)
            });
            let selectedJson = []
            for(selectedId of selectedIds) {
                selectedJson.push(JSON.stringify(parseInt(selectedId.id_product,10)))
            }
            $("#PRODUCT_SELECTION").val(selectedJson.join(","));
        }
    })

</script>