$(document).on("dp-loaded", function () {
    let price = $('.product-price.price').html()
    price = price.replace('&nbsp;€', '');
    price = price.replace(' ', '');
    price = price.replace(',', '.');
    price = parseFloat(price);
    alert(price)
});
