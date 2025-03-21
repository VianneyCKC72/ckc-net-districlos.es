$(document).ready(function () {
    if ($('body').hasClass('page-addresses')) {

        $(".badge-empty-green").click(function(){

            let addressId = $(this).next().val()

            $.ajax({
                type: "POST",
                url: 'controller/action',
                data: {
                    controller :'front',
                    action :'postProcess',
                    "addressId": addressId,
                    ajax : true
                },
                success: function(responseData){
                    if (responseData == "success"){
                        location.reload();
                    }
                }
            })
        })
    }
});