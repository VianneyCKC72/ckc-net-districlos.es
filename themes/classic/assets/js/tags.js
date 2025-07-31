/**
 * CKC Tags Management script
 * Date : 20/06/2022
 * Author : CKC-NET / Adrien CABAUD (adrien@ckc-net.com)
 *
 * Related files (added or edited) :
 * - /themes/auto_store/templates/catalog/_partials/miniatures/product.tpl
 * - /themes/auto_store/templates/catalog/_partials/miniatures/product-listgrid.tpl
 * - /themes/auto_store/templates/catalog/_partials/product-details.tpl
 * - /themes/auto_store/templates/checkout/order-confirmation.tpl
 * - /themes/auto_store/templates/_partials/footer.tpl
 * - /themes/auto_store/assets/js/tags.js
 * - /themes/auto_store/assets/js/lib/jquery.inview.min.js
 * - /themes/auto_store/assets/js/lib/js.cookie.min.js
 * - /modules/ckc_configurator/views/js/front.js
 * - /webservice/fbConversionGateway.php
 *
 * (c) 2022 CKC-NET
 */

/**
 * CKC Tags Management script
 * Date : 20/06/2022
 * Author : CKC-NET / Adrien CABAUD (adrien@ckc-net.com)
 *
 * Related files (added or edited) :
 * - /themes/auto_store/templates/catalog/_partials/miniatures/product.tpl
 * - /themes/auto_store/templates/catalog/_partials/miniatures/product-listgrid.tpl
 * - /themes/auto_store/templates/catalog/_partials/product-details.tpl
 * - /themes/auto_store/templates/checkout/order-confirmation.tpl
 * - /themes/auto_store/templates/_partials/footer.tpl
 * - /themes/auto_store/assets/js/tags.js
 * - /themes/auto_store/assets/js/lib/jquery.inview.min.js
 * - /themes/auto_store/assets/js/lib/js.cookie.min.js
 * - /modules/ckc_configurator/views/js/front.js
 * - /webservice/fbConversionGateway.php
 *
 * (c) 2022 CKC-NET
 */

// Constants
const currentPage = prestashop.page.page_name;
const loggedCustomer = prestashop.customer;
let loggedCustomerPhone = null;
let firstAddress = {};
const loggedCustomerState = (localStorage.getItem('customer_logged') === 'true');
const loggedCustomerSignup = (localStorage.getItem('customer_signup') === 'true');
// Defining customer phone (either phone or phone_mobile)
if(Object.keys(loggedCustomer.addresses).length > 0) {
    firstAddress = loggedCustomer.addresses[Object.keys(loggedCustomer.addresses)[0]];
    loggedCustomerPhone = firstAddress.phone || firstAddress.phone_mobile;
}


// Checkout steps
const checkoutSteps = {
    'checkout-personal-information-step': 2,
    'checkout-addresses-step': 3,
    'checkout-delivery-step': 4,
    'checkout-payment-step': 5
};

// Define common dataLayer structure
let dataLayerStructure = {
    'event': 'pageview',
    'google_tag_params': {
        'ecomm_pagetype': getPageType(),
        'ecomm_prodid': getProductIds(),
        'ecomm_totalvalue': getTotalValue()
    },
    'persister_firstname': loggedCustomer.firstname || '',
    'persister_firstname_hashed': loggedCustomer.firstname ? CryptoJS.SHA256(loggedCustomer.firstname).toString() : '',
    'persister_lastname': loggedCustomer.lastname || '',
    'persister_lastname_hashed': loggedCustomer.lastname ? CryptoJS.SHA256(loggedCustomer.lastname).toString() : '',
    'persister_email': loggedCustomer.email || '',
    'persister_email_hashed': loggedCustomer.email ? CryptoJS.SHA256(loggedCustomer.email).toString() : '',
    'persister_phone': loggedCustomerPhone ? loggedCustomerPhone : '',
    'persister_phone_hashed': loggedCustomerPhone ? CryptoJS.SHA256(loggedCustomerPhone).toString() : '',
    'persister_city': firstAddress.city || '',
    'persister_country': firstAddress.country_iso || '',
    'persister_postal_code': firstAddress.postcode || '',
    'userId': loggedCustomer.id || '',
    'userLogged': loggedCustomer.is_logged,
    'newClient': getNewClientStatus(),
    'conseiller': (typeof window.isAdminConnected !== 'undefined' && window.isAdminConnected)
};

// Push event on GTM datalayer
dataLayer.push(dataLayerStructure);

// Watch customer logged state change
if(!loggedCustomerState && loggedCustomer.is_logged) {
    localStorage.setItem('customer_logged', true);
    pushEvent('identify', {
        'signup': loggedCustomerSignup
    });
} else if(loggedCustomerState && !loggedCustomer.is_logged) {
    localStorage.setItem('customer_logged', false);
    localStorage.setItem('customer_signup', false);
}


// 1: Global events
executeEvent('IMP5');
executeEvent('ADD');
executeEvent('REM');

// 2: Page-specific events
switch(currentPage) {
    case 'index':
        executeEvent('IMP1');
        break;
    case 'category':
        executeEvent('IMP2');
        break;
    case 'product':
        executeEvent('IMP3');
        executeEvent('IMP4');
        executeEvent('DET');
        break;
    case 'cart':
        executeEvent('CHK1');
        break;
    case 'checkout':
        executeCheckoutEvents();
        break;
    case 'order-confirmation':
        executeEvent('PUR');
        break;
    case 'registration':
        localStorage.setItem('customer_signup', true);
        break;
    case 'configurator':
        executeEvent('CFG');
        break;
}

document.addEventListener('DOMContentLoaded', function() {
    $( document ).on( "ajaxSuccess", function( event, xhr, settings ) {
        var data = xhr.responseJSON;

        if (settings.url.includes('/panier') && settings.data && settings.data.includes('add=')) {
            var productData = getProductData(data.id_product, data.id_product_attribute);

            dataLayer.push({ ecommerce: null });
            dataLayer.push({
                event: 'add_to_cart',
                ecommerce: {
                    currency: 'EUR',
                    value: productData.price,
                    items: [
                        {
                            item_id: productData.id,
                            item_sku: productData.sku,
                            item_name: productData.name,
                            item_category: productData.category,
                            quantity: data.quantity,
                            price: productData.price,
                            discount: productData.discount || 0,
                        }
                    ]
                }
            });
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var contactForm = document.querySelector('.contact-form form');

    if (contactForm) {
        contactForm.addEventListener('submit', function (event) {
            var formData = new FormData(contactForm);
            var formObject = {};

            formData.forEach(function (value, key) {
                formObject[key] = value;
            });

            dataLayer.push({
                event: 'contact_form',
                objet: formObject['menu-899'],
                mail: formObject['email-668'] || '',
                phone: formObject['tel-534'] || ''
            });
        });
    }
});

function getProductData(productId, productAttributeId) {
    var productData = {};

    var productElement = document.querySelector('#product_page_product_id');
    if (productElement) {
        productData = {
            id: productElement.value,
            sku: productElement.value + (productAttributeId ? '_' + productAttributeId : ''),
            name: document.querySelector('.product-title').innerText.trim(),
            category: JSON.parse(document.querySelector('#product-details').dataset.product).category_name || '',
            price: getTotalValue() || 0,
            discount: getDiscount() || 0
        };
    }

    // Fallback in case the data is not found in the DOM
    if (!productData.id) {
        productData = {
            id: productId,
            sku: productId + (productAttributeId ? '_' + productAttributeId : ''),
            item_sku: productAttributeId,
            name: '',
            category: '',
            price: 0,
            discount: 0
        };
    }

    return productData;
}

// Execute event
function executeEvent(name, replay = false) {

    if(name === 'IMP1')
    {
        var newProductsContainer = document.querySelector('ul#newproduct-carousel');
        if (newProductsContainer) {
            var newProductsList = newProductsContainer.querySelectorAll('li.item');
            var products = [];

            newProductsList.forEach(function(el, position) {
                var data = el.querySelector('article.product-miniature').dataset;
                products.push({
                    'name': data.productName,
                    'id': data.idProduct,
                    'price': parseFloat(data.productPrice.replace(/\s/g,'').replace('€', '').replace(',', '.')),
                    'reference': data.productReference,
                    'category': data.productCategory,
                    'variant': data.idProductAttribute,
                    'list': 'Nouveautés',
                    'position': position + 1
                });
            });

            newProductsContainer.addEventListener('inview', function(event) {
                var isInView = event.detail[0];
                if (isInView) {
                    pushEvent('impression_nouveautés', {
                        'impressions': products
                    });
                    newProductsContainer.removeEventListener('inview', arguments.callee);
                }
            });
        }
    }
    else if(name === 'IMP2')
    {
        var productsList = document.querySelectorAll('.products > div.js-product');
        var products = [];
        var categoryPath = [];

        document.querySelectorAll('.breadcrumb li > a > span').forEach(function(el, i) {
            if (i !== 0) categoryPath.push(el.innerText);
        });

        productsList.forEach(function(el, position) {
            var data = el.querySelector('article.product-miniature').dataset;
            products.push({
                'name': data.productName,
                'id': data.idProduct,
                'price': parseFloat(data.productPrice.replace(/\s/g,'').replace('€', '').replace(',', '.')),
                'reference': data.productReference,
                //'category': data.productCategory,
                'category': categoryPath.join('/'),
                'variant': data.idProductAttribute,
                'list': 'Catégorie',
                'position': position + 1
            });
        });

        pushEvent('impression_catégorie', {
            'impressions': products
        });
    }
    else if(name === 'IMP3')
    {
        // var sameCategoryContainer = document.querySelector('ul#productscategory-carousel');
        // if (sameCategoryContainer) {
        //     var sameCategoryList = sameCategoryContainer.querySelectorAll('li.item');
        //     var products = [];
        //
        //     sameCategoryList.forEach(function(el, position) {
        //         var data = el.querySelector('article.product-miniature').dataset;
        //         products.push({
        //             'name': data.productName,
        //             'id': data.idProduct,
        //             'price': parseFloat(data.productPrice.replace(/\s/g,'').replace('€', '').replace(',', '.')),
        //             'reference': data.productReference,
        //             'category': data.productCategory,
        //             'variant': data.idProductAttribute,
        //             'list': 'Même catégorie',
        //             'position': position + 1
        //         });
        //     });
        //
        //     sameCategoryContainer.addEventListener('inview', function(event) {
        //         var isInView = event.detail[0];
        //         if (isInView) {
        //             pushEvent('impression_même_catégorie', {
        //                 'impressions': products
        //             });
        //             sameCategoryContainer.removeEventListener('inview', arguments.callee);
        //         }
        //     });
        // }
    }
    else if(name === 'IMP4')
    {
        var crossSellContainer = document.querySelector('ul#crosssell-carousel');
        if (crossSellContainer) {
            var crossSellList = crossSellContainer.querySelectorAll('li.item');
            var products = [];

            crossSellList.forEach(function(el, position) {
                var data = el.querySelector('article.product-miniature').dataset;
                products.push({
                    'name': data.productName,
                    'id': data.idProduct,
                    'price': parseFloat(data.productPrice.replace(/\s/g,'').replace('€', '').replace(',', '.')),
                    'reference': data.productReference,
                    'category': data.productCategory,
                    'variant': data.idProductAttribute,
                    'list': 'Similaire',
                    'position': position + 1
                });
            });

            crossSellContainer.addEventListener('inview', function(event) {
                var isInView = event.detail[0];
                if (isInView) {
                    pushEvent('impression_similaires', {
                        'impressions': products
                    });
                    crossSellContainer.removeEventListener('inview', arguments.callee);
                }
            });
        }
    }
    else if(name === 'IMP5')
    {
        document.addEventListener('ajaxSuccess', function(event) {
            var xhr = event.detail[0];
            var settings = event.detail[1];
            var data = event.detail[2];

            if (settings.url.indexOf('/recherche') >= 0) {
                var products = [];

                data.products.forEach(function(product, position) {
                    products.push({
                        'name': product.name,
                        'id': product.id_product,
                        'price': product.price_amount,
                        'reference': product.reference,
                        'category': product.category_name,
                        'variant': '',
                        'list': 'Recherche',
                        'position': position + 1
                    });
                });

                pushEvent('impression_search', {
                    'impressions': products
                });
            }
        });
    }
    else if(name === 'CLK1')
    {
        //
    }
    else if(name === 'DET')
    {
        var productDetailsData = JSON.parse(document.querySelector('#product-details').dataset.product);
        var productReference = document.getElementById('product_reference');

        pushEvent('detail_produit', {
            'actionField': {'list': getListName() },
            'detail': {
                'products': [{
                    'name': productDetailsData.name,
                    'id': productDetailsData.id_product,
                    'price': productDetailsData.price_amount,
                    'reference': productReference ? productReference.innerText : '',
                    'category': productDetailsData.category_name,
                    'variant': productDetailsData.id_product_attribute
                }]
            }
        }, 'ViewContent', {
            'currency': 'EUR',
            'content_name': productDetailsData.name,
            'content_type': 'product',
            'contents': [
                {
                    'id': productDetailsData.id_product,
                    'category': productDetailsData.category_name,
                    'item_price': productDetailsData.price_amount
                }
            ]
        });

        if (!replay) {
            // Set the event listener only on first load
            document.addEventListener('ajaxSuccess', function(event) {
                var xhr = event.detail[0];
                var settings = event.detail[1];
                var data = event.detail[2];

                // In case of variant change...
                if (settings.type === "POST" && settings.url.indexOf(location.pathname) >= 0) {
                    // ...replay event with new data
                    executeEvent('DET', true);
                }
            });
        }
    }
    else if(name === 'ADD')
    {
        document.addEventListener('ajaxSuccess', function(event) {
            var xhr = event.detail.xhr;
            var settings = event.detail.settings;
            var data = xhr.responseJSON;

            if (settings.url.includes('/panier') && settings.data.includes('&add=')) {
                pushEvent('ajout_panier', {
                    currencyCode: 'EUR',
                    add: {
                        actionField: { list: getListName() },
                        products: [{
                            name: '',
                            id: data.id_product,
                            price: '',
                            reference: '',
                            category: '',
                            variant: data.id_product_attribute,
                            position: '',
                            quantity: data.quantity
                        }]
                    }
                });
            }
        });
    }
    else if(name === 'REM')
    {
        document.addEventListener('ajaxSuccess', function(event) {
            var xhr = event.detail[0];
            var settings = event.detail[1];
            var data = event.detail[2];

            if (settings.url.indexOf('/panier?delete=1') >= 0) {
                pushEvent('retrait_panier', {
                    'currencyCode': 'EUR',
                    'remove': {
                        'actionField': { 'list': getListName() },
                        'products': [{
                            'name': '',
                            'id': data.id_product,
                            'price': '',
                            'reference': '',
                            'category': '',
                            'variant': data.id_product_attribute,
                            'position': '',
                            'quantity': data.quantity
                        }]
                    }
                });
            }
        });
    }
    else if(name === 'CHK1')
    {
        pushCheckoutEvent(1);
    }
    else if(name === 'CHK2')
    {
        pushCheckoutEvent(2);
    }
    else if(name === 'CHK3')
    {
        pushCheckoutEvent(3);
    }
    else if(name === 'CHK4')
    {
        pushCheckoutEvent(4);
    }
    else if (name === 'CHK4OPTION') {
        // Fired when carrier selection form is submitted
        document.querySelector('#js-delivery').addEventListener('submit', function() {
            var selectedDelivery = document.querySelector('input[name*="delivery_option"]:checked').id;
            var selectedDeliveryName = document.querySelector('label[for="' + selectedDelivery + '"] .carrier-name').textContent;

            pushEvent('checkoutOption_delivery', {
                'checkout_option': {
                    'actionField': {
                        'step': 4,
                        'option': selectedDeliveryName
                    }
                }
            }, 'chooseDelivery', {
                'delivery_method': selectedDeliveryName
            });
        });
    }
    else if(name === 'CHK5')
    {
        pushCheckoutEvent(5);
    }
    else if(name === 'CHK5OPTION')
    {
        document.querySelector('#payment-confirmation > div.ps-shown-by-js > button[type="submit"]').addEventListener('click', function(e) {
            var selectedPayment = document.querySelector('input[name="payment-option"]:checked').dataset.moduleName;
            var selectedPaymentName = '';

            switch(selectedPayment) {
                case 'credit_card':
                case 'local_payment_hipay':
                case 'sogecommerce':
                case 'payplug':
                    selectedPaymentName = 'CB';
                    break;
                case 'paypal':
                    selectedPaymentName = 'Paypal';
                    break;
                case 'ps_checkpayment':
                    selectedPaymentName = 'Chèque';
                    break;
                case 'ps_wirepayment':
                    selectedPaymentName = 'Virement';
                    break;
                case 'opartdevis':
                    selectedPaymentName = 'Devis';
                    break;
                default:
                    selectedPaymentName = 'Autre';
            }

            pushEvent('checkoutOption_paymentMethod', {
                'checkout_option': {
                    'actionField': {
                        'step': 5,
                        'option': selectedPaymentName
                    }
                }
            }, 'AddPaymentInfo', {
                'payment_method': selectedPaymentName
            });
        });
    }
    else if(name === 'PUR')
    {
        // Check if purchase event has already been sent
        var order = document.querySelector('section#content').dataset.order;
        order = JSON.parse(order);

        var expirationDate = localStorage.getItem('purchase_event_expiration');
        if (expirationDate && parseInt(expirationDate) > Date.now() && localStorage.getItem('purchase_event_expiration_id') === order.details.reference) {
            return;
        }else{
            localStorage.removeItem('purchase_event_expiration');
            localStorage.removeItem('purchase_event_expiration_id');
        }

        var orderProducts = [];
        var orderProductsFb = [];

        var amountTaxIncluded = parseFloat(order.amounts.totals.total_including_tax.amount);
        var amountTaxExcluded = parseFloat(order.amounts.totals.total_excluding_tax.amount);
        var amountTax = amountTaxIncluded - amountTaxExcluded;
        var amountShipping = parseFloat(order.amounts.subtotals.shipping.amount);
        var products = Object.values(order.products);
        products.forEach(function(data) {
            orderProducts.push({
                'price_tax_exc': data.product_price,
                'wholesale_price': parseFloat(data.wholesale_price),
                'name': data.product_name,
                'id': data.product_id,
                'price': data.product_price_wt,
                'reference': data.product_reference,
                'category': data.id_category_default,
                'variant': data.product_attribute_id,
                'quantity': parseInt(data.product_quantity),
                'item_sku': data.product_attribute_id
            });

            orderProductsFb.push({
                'id': data.product_id,
                'quantity': parseInt(data.product_quantity),
                'category': data.id_category_default,
                'item_price': data.product_price_wt
            });
        });

        pushEvent('purchase', {
            'currencyCode': 'EUR',
            'order_id': order.details.id,
            'purchase': {
                'actionField': {
                    'id': order.details.reference,
                    'affiliation': 'Boutique en ligne',
                    'revenue': amountTaxIncluded,
                    'tax': amountTax,
                    'shipping': amountShipping
                },
                'products': orderProducts
            }
        }, 'Purchase', {
            'currency': 'EUR',
            'value': amountTaxIncluded,
            'num_items': order.products_count,
            'content_type': 'product',
            'contents': orderProductsFb
        });

        expirationDate = Date.now() + 3600000;
        localStorage.setItem('purchase_event_expiration', expirationDate);
        localStorage.setItem('purchase_event_expiration_id', order.details.reference);
    }
    else if(name === 'CFG')
    {
        var products = [];

        document.querySelectorAll('button[name="addToCartAlias"]').forEach(function(button) {
            button.addEventListener('click', function(e) {
                document.querySelectorAll('.configurator-products-list ul li').forEach(function(el, position) {
                    var productData = JSON.parse(el.getAttribute('data-product'));
                    products.push({
                        'name': productData.name,
                        'id': productData.id,
                        'price': productData.price,
                        'quantity': productData.quantity
                    });
                });

                pushEvent('configurateur', {
                    'products': products
                });
            });
        });
    }
}

// Push event to datalayer
function pushEvent(eventName, eventData = null, fbEventName = null, fbEventData = null)
{
    let randomId = '11035' + Math.round(Date.now() / 1000);

    // Clear GTM
    window.dataLayer = window.dataLayer || [];
    dataLayer.push({ ecommerce: null });

    // Push event on GTM datalayer
    dataLayer.push({
        'event': eventName,
        'fb_event_id': randomId,
        'ecommerce': eventData,
        'user_data': {
            'firstname': loggedCustomer.firstname,
            'firstname_sha256': CryptoJS.SHA256(loggedCustomer.firstname).toString(),
            'lastname': loggedCustomer.lastname,
            'lastname_sha256': CryptoJS.SHA256(loggedCustomer.lastname).toString(),
            'email': loggedCustomer.email,
            'email_sha256': CryptoJS.SHA256(loggedCustomer.email).toString(),
            'phone': loggedCustomerPhone,
            'phone_sha256': CryptoJS.SHA256(loggedCustomerPhone).toString()
        }
    });

    // Facebook Conversion (if applicable)
    if(fbEventName)
    {
        let facebookCookies = getFacebookCookies();

        // Make Facebook Conversion API call
        facebookCall({
            'event_name': fbEventName,
            'event_time': null,
            'action_source': 'website',
            'event_id': randomId,
            'user_data': {
                'em': [
                    loggedCustomer.email
                ],
                'ph': [
                    loggedCustomerPhone
                ],
                'client_ip_address': null, // Defined in backend
                'client_user_agent': null, // Defined in backend
                'ln': [
                    loggedCustomer.lastname
                ],
                'fn': [
                    loggedCustomer.firstname
                ],
                'fbc': facebookCookies._fbc,
                'fbp': facebookCookies._fbp
            },
            'custom_data': fbEventData
        });
    }
}

function getPageType() {
    switch (currentPage) {
        case 'index':
            return 'home';
        case 'category':
            return 'category';
        case 'product':
            return 'product';
        case 'cart':
            return 'cart';
        case 'order-confirmation':
            return 'purchase';
        default:
            return 'other';
    }
}

function getProductIds() {
    if (currentPage === 'product') {
        const productElement = document.querySelector('#product_page_product_id');
        if (productElement) {
            return productElement.value;
        }
    } else if (currentPage === 'cart' || currentPage === 'checkout') {
        return prestashop.cart.products.map(product => product.id_product);
    }
    return [];
}

function getTotalValue() {
    if (currentPage === 'product') {
        const productElement = JSON.parse(document.querySelector('#product-details').dataset.product);
        if (productElement) {
            return productElement.price_amount;
        }
    } else if (currentPage === 'cart' || currentPage === 'checkout') {
        return prestashop.cart.totals.total.amount;
    }
    return 0;
}

function getDiscount() {
    if (currentPage === 'product') {
        const productElement = document.querySelector('#product');
        if (productElement) {
            const priceElement = document.querySelector('.regular-price');
            if (priceElement) {
                let priceText = priceElement.textContent || priceElement.innerText;
                priceText = priceText.replace('€', '').replace(',', '.').trim();
                return parseFloat(priceText) || 0;
            }
        }
    }

    return 0;
}

function getNewClientStatus() {
    if (loggedCustomer.is_logged) {
        return loggedCustomer.hasOrders;
    }
    return '';
}

function getFacebookCookies() {
    if (typeof Cookies === 'undefined') {
        console.warn('js-cookie non chargé');
        return {
            _fbc: getUrlParameter('fbclid') || null,
            _fbp: null
        };
    }

    return {
        _fbc: Cookies.get('_fbc') || getUrlParameter('fbclid'),
        _fbp: Cookies.get('_fbp')
    };
}

function facebookCall(data) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/webservice/fbConversionGateway.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify(data));
}

function getUrlParameter(sParam)
{
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }

    return null;
}

function getListName(clickedElement = null)
{
    if(currentPage === 'index') {
        // nouveautés
        // -- page_name = index
        return 'nouveautés';
    } else if(currentPage === 'category') {
        // catégorie
        // -- page_name = category
        return 'catégorie';
    } else if(currentPage === 'search') {
        // recherche
        // -- page_name = search
        return 'recherche';
    } else if(currentPage === 'checkout') {
        // checkout
        // -- page_name = checkout
        return 'checkout';
    } else if(currentPage === 'cart') {
        // panier
        // -- page_name = cart
        return 'panier';
    }
    else if(currentPage === 'product')
    {
        if(clickedElement) {
            if(clickedElement.closest('#productscategory-grid').length) {
                // même catégorie
                // -- page_name = product
                return 'même catégorie';
            }

            if(clickedElement.closest('#crosssell-carousel').length) {
                // similaires
                // -- page_name = product
                return 'similaires';
            }
        }

        // détail
        // -- page_name = product
        return 'détail';
    }
}

function executeCheckoutEvents() {
    var step = checkoutSteps[document.querySelector('.js-current-step').getAttribute('id')];

    if (step === 2) {
        localStorage.setItem('customer_signup', true);

        document.querySelector('.account-switch a').addEventListener('click', function() {
            localStorage.setItem('customer_signup', (this.getAttribute('href') === '#checkout-guest-form') ? true : false);
        });

        executeEvent('CHK2');
    } else if (step === 3) {
        executeEvent('CHK3');
    } else if (step === 4) {
        executeEvent('CHK4');
        executeEvent('CHK4OPTION');
    } else if (step === 5) {
        executeEvent('CHK5');
        executeEvent('CHK5OPTION');
    }
}

function pushCheckoutEvent(step) {
    var cart = prestashop.cart;
    var fbEventName = (step > 1) ? 'CheckoutStep' + step : 'InitiateCheckout';

    var products_gtm = [];
    var products_fb = [];

    cart.products.forEach(function(product, position) {
        products_gtm.push({
            'price_tax_exc': product.price_with_reduction_without_tax,
            'wholesale_price': '',
            'name': product.name,
            'id': product.id_product,
            'price': product.price_wt,
            'reference': product.reference,
            'category': product.category,
            'variant': product.id_product_attribute,
            'quantity': product.quantity
        });

        products_fb.push({
            'id': product.id_product,
            'quantity': product.quantity,
            'category': product.category,
            'item_price': product.price_wt
        });
    });

    pushEvent('checkout_step', {
        'currencyCode': 'EUR',
        'checkout': {
            'actionField': { 'step': step },
            'products': products_gtm
        }
    }, fbEventName, {
        'currency': 'EUR',
        'value': cart.totals.total.amount,
        'num_items': cart.products_count,
        'content_type': 'product',
        'contents': products_fb
    });
}
