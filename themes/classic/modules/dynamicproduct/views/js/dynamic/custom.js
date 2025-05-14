$(document).on("dp-loaded", function () {
    let price = $('.product-price.price').html()
    price = price.replace('&nbsp;€', '');
    price = price.replace(',', '.');
    price = price.replace(/\s/g, '');
    let price_integer = price.split('.')[0];
    let price_decimal = price.split('.')[1];
    $('.product-price.price').html('<span class="price-integer">' + price_integer + '</span><span class="price-decimal">,' + price_decimal + '</span></span>');
});
