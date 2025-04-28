<div class="panel">
    <h3 style="margin-top: 1px !important; border: none; margin-bottom: 5px; margin-left: -7px;" class="h3-ckctitle ckch3"><i class="icon icon-link"></i> {l s='LIENS VERS LES AUTRES MODULES DE PROMOTION' mod='ckcadcampaignconfigurator'}</h3>
    Lien du module <strong>Ever Popup</strong> : <strong><a href="{Context::getContext()->link->getAdminLink('AdminModules', true, [], ['configure' => 'everpspopup'])}" target="_blank">{Context::getContext()->link->getAdminLink('AdminModules', true, [], ['configure' => 'everpspopup'])}</a></strong>
    <br>
    <br>
    Lien du module <strong>TM - Image slider</strong> : <strong><a href="{Context::getContext()->link->getAdminLink('AdminModules', true, [], ['configure' => 'tm_imageslider'])}" target="_blank">{Context::getContext()->link->getAdminLink('AdminModules', true, [], ['configure' => 'tm_imageslider'])}</a></strong>
    <br>
    <br>
    Lien vers <strong>Règles panier</strong> : <strong><a href="{Context::getContext()->link->getAdminLink('AdminCartRules', true, [], [])}" target="_blank">{Context::getContext()->link->getAdminLink('AdminCartRules', true, [], [])}</a></strong>
    <br>
    <br>
    Lien du module <strong>Module de gestion d'étiquettes</strong> : <strong><a href="{Context::getContext()->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckc_label'])}" target="_blank">{Context::getContext()->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckc_label'])}</a></strong>
    <br>
    <br>
</div>
<div>
    {$form_main}
    </div>
    <div class="panel">
        <h3 style="margin-top: 1px !important; border: none; margin-bottom: 5px; margin-left: -7px;" class="h3-ckctitle ckch3"><i class="icon icon-link"></i> {l s='CAMPAIGN CONTENT CONFIGURATION' mod='ckcadcampaignconfigurator'}</h3>
        <div id="modulecontent" class="clearfix">
            <div id="ckcadcampaignconfigurator-menu">
                <div class="col-lg-12">
                    <div class="list-group" v-on:click.prevent>
                        <a href="#" class="list-group-item col-lg-2" v-bind:class="{ 'active': isActive('menuContent') }" v-on:click="makeActive('menuContent')"><i class="material-icons">collections_bookmark</i> {l s='Menu Content' mod='ckcadcampaignconfigurator'}</a>
                        <a href="#" class="list-group-item col-lg-2" v-bind:class="{ 'active': isActive('headerContent') }" v-on:click="makeActive('headerContent')"><i class="material-icons">folder_open</i> {l s='Header Content' mod='ckcadcampaignconfigurator'}</a>
                        <a href="#" class="list-group-item col-lg-2" v-bind:class="{ 'active': isActive('cartContent') }" v-on:click="makeActive('cartContent')"><i class="material-icons">insert_drive_file</i> {l s='Cart Content' mod='ckcadcampaignconfigurator'}</a>
                        <a href="#" class="list-group-item col-lg-2" v-bind:class="{ 'active': isActive('categoryContent') }" v-on:click="makeActive('categoryContent')"><i class="material-icons">folder_open</i> {l s='Category Content' mod='ckcadcampaignconfigurator'}</a>
                        <a href="#" class="list-group-item col-lg-2" v-bind:class="{ 'active': isActive('productContent') }" v-on:click="makeActive('productContent')"><i class="material-icons">folder_open</i> {l s='Product Content' mod='ckcadcampaignconfigurator'}</a>
                        <a href="#" class="list-group-item col-lg-2" v-bind:class="{ 'active': isActive('devisContent') }" v-on:click="makeActive('devisContent')"><i class="material-icons">folder_open</i> {l s='Devis Content' mod='ckcadcampaignconfigurator'}</a>
                        <a href="#" class="list-group-item col-lg-2" v-bind:class="{ 'active': isActive('promoPageContent') }" v-on:click="makeActive('promoPageContent')"><i class="material-icons">folder_open</i> {l s='Promo Page Content' mod='ckcadcampaignconfigurator'}</a>
                    </div>
                </div>
            </div>

            <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>


            {* list your admin tpl *}
            <div id="menuContent" class="ckcadcampaignconfigurator_menu addons-hide">
                {$formMenu}
            </div>

            <div id="headerContent" class="ckcadcampaignconfigurator_menu addons-hide">
                <div style="display: none">{$formHeader}</div>
                <br>
                Vous pouvez configurer les textes promo pour Header dans le module <strong>CKC Bannière promotionnelle</strong> :
                <br>
                <br>
                Lien du module : <strong><a href="{Context::getContext()->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckcpromotionbanner'])}" target="_blank">{Context::getContext()->link->getAdminLink('AdminModules', true, [], ['configure' => 'ckcpromotionbanner'])}</a></strong>
            </div>

            <div id="cartContent" class="ckcadcampaignconfigurator_menu addons-hide">
                <table class="table">
                    <thead>
                    <tr>
                        <th class="fixed-width-xs text-left">ID</th>
                        <th class="fixed-width-lg title_box text-left">Nom Promo</th>
                        <th class="fixed-width-md title_box text-left">Type</th>
                        <th class="fixed-width-md title_box text-left">Status</th>
                        <th class="fixed-width-xs text-right" style="padding-right: 95px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                        {if $cartPromos}
                            {foreach $cartPromos as $cartPromo }
                                <tr>
                                <td class="text-left">{$cartPromo.id}</td>
                                <td class="text-left">{$cartPromo.text}</td>
                                <td>
                                    {if $cartPromo.is_fixed}
                                        Fixed
                                    {else}
                                        Range
                                    {/if}    
                                </td>
                                <td>
                                {if $cartPromo.active}
                                    Active
                                {else}
                                    Disabled
                                {/if}    
                            </td>
                                <td>
                                    <div class="btn-group-action">
                                        <div class="btn-group pull-right">
                                            <a href="{$current_url}&amp;create_edit_new_promo&campaignId={$idCampaign}&promoId={$cartPromo.id}" class="btn btn-default">
                                                <i class="icon-pencil"></i> {l s='Edit' d='Admin.Actions'}
                                            </a>
                                            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                                <span class="caret"></span>&nbsp;
                                            </button>
                                            <ul class="dropdown-menu">
                                            <li>
                                                <a href="{$current_url}&amp;create_edit_new_promo&campaignId={$idCampaign}&promoId={$cartPromo.id}">
                                                    <i class="icon-pencil"></i> {l s='Edit' d='Admin.Actions'}
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{$current_url}&amp;delete_cart_promo&campaignId={$idCampaign}&promoId={$cartPromo.id}"
                                                    onclick="return confirm('{l s='Do you really want to delete this promo?' d='Modules.Facetedsearch.Admin'}');">
                                                    <i class="icon-trash"></i> {l s='Delete' d='Admin.Actions'}
                                                </a>
                                            </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                                </tr>
                                {/foreach}
                            {else}
                                <tr>
                                    <td>No cart promos</td>
                                </tr>
                        {/if}
                    </tbody>
                </table>
                {if $idCampaign}
                <div class="panel-footer">
                        <a class="btn btn-default pull-right" href="{$current_url}&amp;create_edit_new_promo&campaignId={$idCampaign}"><i class="process-icon-plus"></i> {l s='Add new promo' mod='ckcadcampaignconfigurator'}</a>
                </div>
                {/if}
            </div>

            <div id="categoryContent" class="ckcadcampaignconfigurator_menu addons-hide">
                {$formCategory}
                <input type="hidden" value="" id="categoryFieldContent" name="categoryFieldContent">
                <div class="col-sm category-selection-container">
                    <div class="d-block">
                        <h4>{l s='Select Categories where to show the promo content :' mod='ckcadcampaignconfigurator'}</h4>
                        <div class="input-group mb-3 category-search-container">
                            <input type="text" id="idInput" class="form-control" placeholder="{l s='Search for a category' mod='ckcadcampaignconfigurator'}">
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
            
            <div id="productContent" class="ckcadcampaignconfigurator_menu addons-hide">
                <table class="table">
                    <thead>
                    <tr>
                        <th class="fixed-width-xs text-left">ID</th>
                        <th class="fixed-width-lg title_box text-left">Nom Promo Produit</th>
                        <th class="fixed-width-md title_box text-left">Date Add</th>
                        <th class="fixed-width-xs text-right" style="padding-right: 95px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                        {if $productPromos}
                            {foreach $productPromos as $productPromo }
                            <tr>
                            <td class="center">{$productPromo.id}</td>
                            <td class="text-left">{$productPromo.text}</td>
                            <td>{$productPromo.date_add}</td>
                            <td>
                                <div class="btn-group-action">
                                    <div class="btn-group pull-right">
                                    <a href="{$current_url}&amp;create_edit_new_product_promo&campaignId={$idCampaign}&promoId={$productPromo.id}" class="btn btn-default">
                                        <i class="icon-pencil"></i> {l s='Edit' d='Admin.Actions'}
                                    </a>
                                    <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <span class="caret"></span>&nbsp;
                                    </button>
                                    <ul class="dropdown-menu">
                                    <li>
                                        <a href="{$current_url}&amp;create_edit_new_product_promo&campaignId={$idCampaign}&promoId={$productPromo.id}">
                                            <i class="icon-pencil"></i> {l s='Edit' d='Admin.Actions'}
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{$current_url}&amp;delete_product_promo&campaignId={$idCampaign}&promoId={$productPromo.id}"
                                            onclick="return confirm('{l s='Do you really want to delete this promo?' d='Modules.Facetedsearch.Admin'}');">
                                            <i class="icon-trash"></i> {l s='Delete' d='Admin.Actions'}
                                        </a>
                                    </li>
                                    </ul>
                                    </div>
                                </div>
                            </td>
                            </tr>
                            {/foreach}
                        {else}
                            <tr>
                                <td>No product promos</td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
                {if $idCampaign}
                <div class="panel-footer">
                        <a class="btn btn-default pull-right" href="{$current_url}&amp;create_edit_new_product_promo&campaignId={$idCampaign}"><i class="process-icon-plus"></i> {l s='Add new product promo' mod='ckcadcampaignconfigurator'}</a>
                </div>
                {/if}
            </div>

            <div id="devisContent" class="ckcadcampaignconfigurator_menu addons-hide">
                {$formDevis}
            </div>

            <div id="promoPageContent" class="ckcadcampaignconfigurator_menu addons-hide">
                {$formPromoPage}
            </div>
            </div>
    </div>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<script type="text/javascript">
    var base_url = "{$ps_base_dir|escape:'htmlall':'UTF-8'}";
    var isPs17 = "{$isPs17|escape:'htmlall':'UTF-8'}";
    var moduleName = "{$module_name|escape:'htmlall':'UTF-8'}";
    var currentPage = "{$currentPage|escape:'htmlall':'UTF-8'}";
    var moduleAdminLink = "{$current_url|escape:'htmlall':'UTF-8'}";
    var ps_version = "{$isPs17|escape:'htmlall':'UTF-8'}";
    var modulePath = "{$modulePath}";
    var allCategories = {$allCategories}
/*    var queryCategories = */
    var selectedClients = "{$selectedClients}".split(',')
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

    $("input.selectionClientGroup").each(function(){
        let valueInput = $(this).attr('id').slice(13)
        if(selectedClients.includes(valueInput)){
            $(this).prop('checked', true)
        }
        $(this).val(valueInput)
    })

    $('<button type="submit" value="1" name="deleteCampaignImage" class="btn btn-default btn-delete-img-campaign">Supprimer l\'image</button>').insertAfter($("#LOGO_PATH-images-thumbnails"))

    /*    $("#categoryContent .form-wrapper").append('<label class="control-label col-lg-4">Selected categories</label><div id="selectedIds"></div>')

        if (queryCategories != 0) {
            var selectedIds = queryCategories
            updateIdsDisplay();
        } else {
            var selectedIds = []
        }

        $(document).on("change", "#categoryContent select", function() {
                let selectedElement = $(this).val()[0]
                for (category of allCategories) {
                    if (category.id_category == selectedElement) {
                        let ifExists = selectedIds.findIndex(e => e.id_category == category.id_category);
                        if (ifExists < 0) {
                            selectedIds.push(category);
                            updateIdsDisplay();
                        }
                    }
                }

            })

            function updateIdsDisplay() {
                    $("#selectedIds").empty();
                    selectedIds.forEach((category) => {
                        elementTag = '<span class="tag" data-id="'+category.id_category+'">'+category.name+'<span class="remove">✖</span></span>'
                        $("#categoryContent .form-wrapper #selectedIds").append(elementTag)
                    });
                    let selectedJson = []
                    for(selectedId of selectedIds) {
                        selectedJson.push(JSON.stringify(selectedId.id_category))
                    }
                    $("#categoryFieldContent").val(selectedJson.join(","));
            }

            $(document).on("click", ".remove", function() {
                    let idToRemove = $(this).parent().attr("data-id");
                    selectedIds = selectedIds.filter(category => {
                        return category.id_category != idToRemove
                    });
                    updateIdsDisplay();
            });*/


    $("button[name=saveNewCampaign]").click(function(e) {
        e.preventDefault()

        let id = $("#ID").val()
        if($("#ACTIVE_on").prop( "checked" )==true){
            var isActiveCampaign = 1
        } else {
            var isActiveCampaign = 0
        }
        
        let titleCampaign = $('#TITLE').val()
        // let clientGroup = $("select#CLIENT_GROUP").find("option:selected").val()
        let clientArr = []
        $("input.selectionClientGroup").each(function(){
            if($(this).is(':checked')){
                clientArr.push($(this).val())
            }
        })
        let clientGroup = clientArr.join(',')
        let startingDate = $("#DATE_START").val()
        let endingDate = $("#DATE_END").val()
        let logoPath = $("#LOGO_PATH-name").val()
        let logoFile = $("#LOGO_PATH")[0].files[0];
        let francoImpact = $("#FRANCO_IMPACT").val()

        if($("#MENU_ACTIVE_on").prop('checked')==true){
            var isActiveMenu = 1
        } else {
            var isActiveMenu = 0
        }
        let menuImgPath = $("#MENU_IMG-name").val()
        let menuImgFile = $("#MENU_IMG").prop('files')[0];
        let menuLink = $("#MENU_LINK").val()

        if($("#HEADER_ACTIVE_on").prop( "checked" )==true){
            var isActiveHeader = 1
        } else {
            var isActiveHeader = 0
        }
        let headertext = $("#HEADER_TEXT").val()
        let headerBackColor = $("#color_0").val()

        if($("#CATEGORY_ACTIVE_on").prop( "checked" )==true){
            var isActiveCategory = 1
        } else {
            var isActiveCategory = 0
        }
        let categoryText = $("#CATEGORY_TEXT_ifr").contents().find("body").html()
        let categoryBackColor = $("#color_1").val()
        if($("#CATEGORY_SHOW_ALL_on").prop( "checked" )==true){
            var isAllCategories = 1
        } else {
            var isAllCategories = 0
        }

        let devisText = $("#DEVIS_TEXT_ifr").contents().find("body").html()
        let devisColor = $("#color_2").val()

        let promoPageText = $("#PROMO_PAGE_TEXT_ifr").contents().find("body").html()

        let selectedCategories = $("#CATEGORY_LIST").val()

        if(titleCampaign.length <= 0 ) {
            // console.log("Title is absent")
            $(".bootstrap.custom-alert").remove()
            $("#content .bootstrap:nth-child(2)").after('<div class="bootstrap custom-alert"><div class="module_error alert alert-danger"><button type="button" class="close" data-dismiss="alert">×</button>Title is absent.</div></div>')
        } else if (startingDate == endingDate) {
            // console.log("Startind date and ending date are the same")
            $(".bootstrap.custom-alert").remove()
            $("#content .bootstrap:nth-child(2)").after('<div class="bootstrap custom-alert"><div class="module_error alert alert-danger"><button type="button" class="close" data-dismiss="alert">×</button>Startind date and ending date are the same</div></div>')
        } else {
                var dataMain = new FormData()
            dataMain.append('controller', 'AdminAjaxCkcadcampaignconfigurator')
            dataMain.append('ajax', true)
            dataMain.append('isActiveCampaign', isActiveCampaign)
            dataMain.append('titleCampaign', titleCampaign)
            dataMain.append('clientGroup', clientGroup)
            dataMain.append('startingDate', startingDate)
            dataMain.append('endingDate', endingDate)
            dataMain.append('logoPath', logoPath)
            dataMain.append('logoFile', logoFile)
            dataMain.append('francoImpact', francoImpact)
            dataMain.append('isActiveMenu', isActiveMenu)
            dataMain.append('menuImgPath', menuImgPath)
            dataMain.append('menuImgFile', menuImgFile)
            dataMain.append('menuLink', menuLink)
            dataMain.append('isActiveHeader', isActiveHeader)
            dataMain.append('headertext', headertext)
            dataMain.append('headerBackColor', headerBackColor)
            dataMain.append('isActiveCategory', isActiveCategory)
            dataMain.append('categoryText', categoryText)
            dataMain.append('categoryBackColor', categoryBackColor)
            dataMain.append('isAllCategories', isAllCategories)
            dataMain.append('devisText', devisText)
            dataMain.append('devisColor', devisColor)
            dataMain.append('promoPageText', promoPageText)
            dataMain.append('selectedCategories', selectedCategories)

            if('' == id) {
                dataMain.append('action', 'CreateCampaign')
            } else {
                
                dataMain.append('action', 'SaveCampaignInfo')
                dataMain.append('ID', id)
            }

            // for (var key of dataMain.entries()) {
            //         console.log(key[0] + ', ' + key[1]);
            //     }

                $.ajax({
                        type: "POST",
                        url: modulePath,
                        data : dataMain,
                        processData: false,
                        contentType: false,
                        success: function(responseData){
                            if (responseData !== '') {

                                let href = $(location).attr('href') + '&edit_campaign&ID=' + responseData;

                                window.location.href = href;
                            }
                        }
                })
        }




    });

</script>