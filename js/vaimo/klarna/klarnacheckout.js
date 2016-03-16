/** Actual Klarna Checkout functions * */
/** ================================ * */
var klarnaResponsive;

function getCookie(name) {
    var re = new RegExp(name + "=([^;]+)");
    var value = re.exec(document.cookie);
    return (value != null) ? unescape(value[1]) : null;
};

function refreshCheckout(data) {
    var klarnaCart                 = document.getElementById("klarna_sidebar"), //"klarna_wrapper");
    klarnaContainer         = document.getElementById("klarna_container"),
    klarnaLoader               = document.getElementById("klarna_loader"),
    klarnaMsg                   = document.getElementById("klarna_msg"),
    klarnaMsgContent           = document.getElementById("klarna_msg_content"),
    klarnaCartHtml             = document.getElementById("klarna_cart_reload"),
    klarnaHtml                 = document.getElementById("klarna_checkout_reload"),
    klarnaQtyInput             = typeof window.klarnaQtyInput != 'undefined'    ? window.klarnaQtyInput : null,
    klarnaQty                  = typeof window.klarnaQtyInputQuantity != 'undefined' ? window.klarnaQtyInputQuantity : null;;
    
    var obj = JSON.parse(data);
    if (obj.redirect_url) {
        window.location.href = obj.redirect_url;
    } else if (obj.success) {
        if (getCookie("klarnaAddShipping") != 1) {
            //klarnaMsgContent.innerHTML = obj.success;
            //fadeIn(klarnaMsg);
            console.log(obj.success);
        } else {
            document.cookie = 'klarnaAddShipping=0; expires=-1;';
        }
        
        var klarnaCartValue = '';
        if (klarnaCartHtml) {
            klarnaCartValue = klarnaCartHtml.value;
        }
        
        vanillaAjax(
            klarnaCartValue,
            '',
            updateSections, '', '', false
        );
    } else if (obj.error) {
        window.reloadKlarnaIFrameFlag = false;
        klarnaMsgContent.innerHTML = obj.error;
        klarnaMsg.className += " error";
        fadeIn(klarnaMsg);
        klarnaMsg.focus();
        if (klarnaQtyInput) {
            klarnaQtyInput.value = klarnaQty;
            delete(window.klarnaQtyInput);
            delete(window.klarnaQtyInputQuantity);
        }
        hideLoader();
    }
};

function updateSections(results) {
    var klarnaCart                 = document.getElementById("klarna_sidebar"), //"klarna_wrapper");
    klarnaContainer         = document.getElementById("klarna_container"),
    klarnaLoader               = document.getElementById("klarna_loader"),
    klarnaMsg                   = document.getElementById("klarna_msg"),
    klarnaMsgContent           = document.getElementById("klarna_msg_content"),
    klarnaCartHtml             = document.getElementById("klarna_cart_reload"),
    klarnaHtml                 = document.getElementById("klarna_checkout_reload"),
    klarnaCheckout             = document.getElementById("klarna_checkout"),
    klarnaTotals               = document.getElementById("klarna_totals"),
    klarnaCheckoutContainer = document.getElementById('klarna-checkout-container');
    
    var objHtml = JSON.parse(results),
        matrixRateFeeEl = document.getElementById('s_method_matrixrate_matrixrate_free');

    // Redraw layout if is in responsive mode
    if (klarnaResponsive.getLayout() === 'two-column') {
        // Create a node for the fetched block
        var tempEl = document.createElement('span');
        tempEl.innerHTML = objHtml.update_sections.html;

        klarnaResponsive.drawLayoutForElement(tempEl.firstChild);
    }
    else { // Otherwise just replace the old sidebar with the new one
        document.getElementById('klarna_default').innerHTML = objHtml.update_sections.html;
    }

    if (getCookie("klarnaDiscountShipping") == 1) {
        document.cookie = 'klarnaDiscountShipping=0; expires=0;';
        if (matrixRateFeeEl && matrixRateFeeEl.innerHTML.length > 0 && matrixRateFeeEl.checked){
            matrixRateFeeEl.checked = true;
        }
        massUpdateCartKlarna(["shipping"], '', '');
    }

    fadeIn(document.getElementById("klarna_totals"));
    bindCheckoutControls();
    reloadKlarnaIFrame();
};

function reloadKlarnaIFrame(results) {
    if (window.reloadKlarnaIFrameRequested == false) {
        window.reloadKlarnaIFrameRequested = true;
        var klarnaHtml = document.getElementById("klarna_checkout_reload");
        var endReload = setInterval(function(){
            if (window.klarnaAjaxRunning == 0 && window.klarnaAjaxArray.length == 0 && window.reloadKlarnaIFrameRequested) {
                if (window.reloadKlarnaIFrameFlag) {
                    vanillaAjax(klarnaHtml.getAttribute('value'), dataString,
                        function(data) {
                            klarnaCheckoutResume();
                            hideLoader();
                            window.clearInterval(endReload);
                        },
                        function(data) {
                            window.clearInterval(endReload);
                            alert("The checkout could not be properly updated. Please reload the page.");
                        },
                        function(data) {
                            window.clearInterval(endReload);
                            alert("The checkout could not be properly updated or got no response from the server. Please reload the page.");
                        }
                    );
                }
            }
        }, 500);
    }
};

function updateCartKlarna(type) {
    var klarnaCart                 = document.getElementById("klarna_sidebar"),
        klarnaContainer         = document.getElementById("klarna_container"),
        klarnaLoader               = document.getElementById("klarna_loader"),
        klarnaMsg                   = document.getElementById("klarna_msg"),
        klarnaMsgContent           = document.getElementById("klarna_msg_content"),
        klarnaCartHtml             = document.getElementById("klarna_cart_reload"),
        klarnaHtml                 = document.getElementById("klarna_checkout_reload"),
        klarnaCheckout             = document.getElementById("klarna_checkout"),
        klarnaTotals               = document.getElementById("klarna_totals"),
        klarnaCheckoutContainer = document.getElementById('klarna-checkout-container');

    klarnaMsg.style.display = 'none';
    klarnaMsg.className = klarnaMsg.className.replace( /(?:^|\s)error(?!\S)/g , '' );

    // Checks what part that triggered the updateCartKlarna()
    var formID = null;
    switch (type) {
        case 'cart':
            formID = document.getElementById('klarna_cart');
            break;
        case 'shipping':
            formID = document.getElementById('klarna_shipping');
            break;
        case 'coupon':
            formID = document.getElementById('klarna_coupon');
            break;
        case 'giftcard':
            formID = document.getElementById('giftcard-form');
            break;
        case 'giftcard-remove':
            formID = document.getElementById('giftcard-form');
            ajaxUrl = input;
            break;
        case 'reward':
            formID = document.getElementById('klarna-checkout-reward');
            break;
        case 'customer_balance':
            formID = document.getElementById('klarna-checkout-customer-balance')
    }
    
    if (formID === null) { return; }

    var dataString = formID.serialize(false);
    if (typeof ajaxUrl === "undefined") {
        var ajaxUrl = formID.getAttribute("action");
    }

    vanillaAjax(ajaxUrl, dataString,
        refreshCheckout,
        function(data) {
            alert(data);
        },
        function(data) {
            alert(data);
        },
        false
    );
    
     setTimeout(function() { // Fade out the "alert" after 3,5 seconds
        fadeOut(klarnaMsg);
        klarnaMsg.className = klarnaMsg.className.replace( /(?:^|\s)error(?!\S)/g , '' );
    }, 3500)
};

function showLoader() {
    var klarnaLoader = document.getElementById("klarna_loader");
    fadeIn(klarnaLoader);
}

function hideLoader() {
    var klarnaLoader = document.getElementById("klarna_loader");
    fadeOut(klarnaLoader);
}

function massUpdateCartKlarna(typeArray, reloadIFrame) {
    showLoader();
    window.reloadKlarnaIFrameFlag = true;
    window.reloadKlarnaIFrameRequested = false;
    for (i = 0; i < typeArray.length; i++) {
        if (i == typeArray.length-1 && reloadIFrame) { 
            window.reloadKlarnaIFrameFlag = true;
        }
        updateCartKlarna(typeArray[i]);
    }
};

/** Bindings * */

function bindCheckoutControls() {

    // Helpfull element variables
    var
        removeItemElement = document.getElementsByClassName('remove-item'),
        subtrackItemElement = document.getElementsByClassName('subtract-item'),
        addItemElement = document.getElementsByClassName('add-item'),
        qtyInputList = document.getElementsByClassName('qty-input'),
        shippingMethods = document.getElementsByName('shipping_method');

    // Bind newsletter checkbox
    if (document.getElementById('klarna-checkout-newsletter')) {
        document.getElementById('klarna-checkout-newsletter').onchange = function() {
            var url = document.getElementById('klarna-checkout-newsletter-url').value;
            var type = Number(document.getElementById('klarna-checkout-newsletter-type').value);
            var checked = false;
            switch (type) {
                case 1:
                    checked = this.checked ? 1 : 0;
                    break;
                case 2:
                    checked = this.checked ? 0 : 1;
                    break;
            }
            this.disabled = 'disabled';
            vanillaAjax(url, 'subscribe_to_newsletter=' + checked, function(){
                document.getElementById('klarna-checkout-newsletter').disabled = '';
            }, '', '', true);
        };
    };

    // Reward
    if (document.getElementsByName('use_reward_points')[0]) {
        document.getElementsByName('use_reward_points')[0].onchange = function() {
            massUpdateCartKlarna(['reward']);
        };
    };

    // Store Credit
    if (document.getElementsByName('use_customer_balance')[0]) {
        document.getElementsByName('use_customer_balance')[0].onchange = function() {
            massUpdateCartKlarna(['customer_balance']);
        };
    };

    // Change shipping method
    if (shippingMethods) {
        for (var q=0; q<shippingMethods.length; q++) {
            shippingMethodItem = shippingMethods[q];
            shippingMethodItem.onchange = function() {
                massUpdateCartKlarna(["shipping"]); // ,"cart"
                return false;
            };
        };
    };


    // Coupon
    if (document.querySelector('#klarna_coupon button')) {
        document.querySelector('#klarna_coupon button').onclick = function() {
            var couponRemove = document.getElementById('remove-coupone');
            var couponInput  = document.getElementById('coupon_code');
    
            if (this.className.match(/(?:^|\s)cancel-btn(?!\S)/)) {
                couponRemove.value = 1;
                document.cookie = 'klarnaDiscountShipping=1; expires=0;';
                massUpdateCartKlarna(["coupon", "cart"]);
            } else if (!couponInput.value) {
                couponInput.focus();
                couponInput.className += " error";
                setTimeout(function() {
                    couponInput.className = couponInput.className.replace( /(?:^|\s)error(?!\S)/g , '' )
                }, 6000)
            } else {
                document.cookie = 'klarnaDiscountShipping=1; expires=0;';
                massUpdateCartKlarna(["coupon", "cart"]);
            }
        };
    }

    if (document.getElementById('coupon_code')) {
        document.getElementById('coupon_code').onkeydown = function(e) {
            if (e.which == 13) {
                e.preventDefault();
                if (!this.value) {
                    this.focus();
                    this.className += " error";
                    setTimeout(function() {
                        this.className = this.className.replace( /(?:^|\s)error(?!\S)/g , '' )
                    }, 6000)
                } else {
                    document.cookie = 'klarnaDiscountShipping=1; expires=0;';
                    massUpdateCartKlarna(["coupon", "cart"]);
                }
            }
        };
    }
    

    // Giftcard
    if (document.querySelector('#giftcard-form button')) {
        document.querySelector('#giftcard-form button').onclick = function(e) {
            e.preventDefault();
            var giftcardInput = document.getElementById('giftcard_code');
            
            if (!giftcardInput.value) {
                giftcardInput.focus();
                for (i = 0; i < 3; i++) {
                    fadeOut(giftcardInput);
                    fadeIn(giftcardInput);
                }
                setTimeout(function() {
                    giftcardInput.className = couponInput.className.replace(
                            /(?:^|\s)error(?!\S)/g, '')
                }, 6000)
            } else {
                massUpdateCartKlarna(['giftcard', 'cart']);
            }
        };
    }

    if (document.getElementById('giftcard_code')) {
        document.getElementById('giftcard_code').onkeydown = function(e) {
            if (e.which == 13) {
                e.preventDefault();
                massUpdateCartKlarna(["giftcard"]);
            }
        };
    }
    
    // Giftcard remove on Klarna
    if (document.querySelector('#applied-gift-cards .btn-remove')) {
        document.querySelector('#applied-gift-cards .btn-remove').onclick = function(e) {
            e.preventDefault();
            massUpdateCartKlarna(['giftcard-remove', 'cart']);
        };
    }

    for (var q = 0; q < removeItemElement.length; q++) {
        var removeItem = removeItemElement[q];
        removeItem.addEventListener('click', function (e) {
            e.preventDefault();

            var itemid = this.getAttribute('data-itemid');
            fadeOut(document.getElementById('cart_item_' + itemid));
            document.getElementById('cart_item_qty_' + itemid).value = 0;
            massUpdateCartKlarna(["cart"], '', '');
        });
    }

    for (var q=0; q<subtrackItemElement.length; q++) {
        subtrackItem = subtrackItemElement[q];
        subtrackItem.onclick = function() {
            var itemid = this.getAttribute('data-itemid'),
                qtyInput = document.getElementById('cart_item_qty_' + itemid),
                qtyCurrent = parseInt(qtyInput.value);

            qtyInput.value = (qtyCurrent - 1);
            if (qtyCurrent - 1 == 0) {
                fadeOut(document.getElementById('cart_item_' + itemid));
            }
            window.klarnaQtyInput = qtyInput;
            window.klarnaQtyInputQuantity = qtyCurrent;
            massUpdateCartKlarna(["cart"]);
            return false;
        };
    };

    for (var q=0; q<addItemElement.length; q++) {
        addItem = addItemElement[q];
        addItem.onclick = function() {
            var itemid = this.getAttribute('data-itemid'),
                qtyInput = document.getElementById('cart_item_qty_' + itemid),
                qtyCurrent = parseInt(qtyInput.value);

            qtyInput.value = (qtyCurrent + 1);
            window.klarnaQtyInput = qtyInput;
            window.klarnaQtyInputQuantity = qtyCurrent;
            massUpdateCartKlarna(["cart"]);
            return false;
        };
    };

    for (var q=0; q<qtyInputList.length; q++) {
        inputField = qtyInputList[q];
        inputField.onkeydown = function(e) {
            if (e.which == 13) {
                e.preventDefault();
                var itemid = this.getAttribute('data-itemid'),
                qtyInput = document.getElementById('cart_item_qty_' + itemid),
                qtyCurrent = parseInt(qtyInput.value),
                qtyOrgInput = document.getElementById('cart_item_qty_org_' + itemid),
                qtyOrgCurrent = parseInt(qtyOrgInput.value);

                if (qtyCurrent != qtyOrgCurrent) {
                    window.klarnaQtyInput = qtyInput;
                    window.klarnaQtyInputQuantity = qtyCurrent;
                    massUpdateCartKlarna(["cart"]);
                }
            }
        };
    };

};

function registerKlarnaApiChange()
{
// This will trigger whenever you get out of editing the address in KCO
// We send an invisible Ajax call to Magento, updating quote, if different postcode
// If different postcode, it sends back true, which is where we need to update KCO
// Perhaps we can update shipping section in THAT ajax call, lets see...
// Using updateCartKlarna('shipping'); is NOT correct at least :)

    if (document.getElementById('klarna-checkout-shipping-update')) {
        _klarnaCheckout(function(api) {
            api.on({
                'change': function(data) {
                    showLoader();
                    var url = document.getElementById('klarna-checkout-shipping-update').value;
                    vanillaAjax(url, 'email=' + data.email + 
                            '&firstname=' + data.given_name +
                            '&lastname=' + data.family_name +
                            '&postcode=' + data.postal_code,
                        function(response){
                            console.log('API change');
                            var answer = JSON.parse(response);
                            if (answer) {
                                massUpdateCartKlarna(["shipping"], false);
                            }
                            hideLoader();
                        }, '', '', true);
                 }
             });
        });
    }
    if (document.getElementById('klarna-checkout-shipping-update-postcode')) {
        _klarnaCheckout(function(api) {
            api.on({
                'shipping_address_change': function(data) {
                    showLoader();
                    var url = document.getElementById('klarna-checkout-shipping-update-postcode').value;
                    vanillaAjax(url, 'email=' + data.email + 
                            '&firstname=' + data.given_name +
                            '&lastname=' + data.family_name +
                            '&street=' + data.street_address +
                            '&postcode=' + data.postal_code +
                            '&city=' + data.city +
                            '&region=' + data.region +
                            '&telephone=' + data.phone +
                            '&country=' + data.country,
                        function(response){
                            console.log('API Shipping Address Change');
                            var answer = JSON.parse(response);
                            if (answer) {
                                massUpdateCartKlarna(["cart", "shipping"], false);
                            }
                            hideLoader();
                        }, '', '', true);
                 }
             });
        });
    }

};

// If there's no shipping option selected when the document loads, then select
// the first option
docReady(function() {
    // Enable responsive mode if layout is 2-column-right
    //var isDefaultLayout = document.getElementById('klarna_container').getAttribute('data-layout');
    //if(!isDefaultLayout && isDefaultLayout !== '') {
        klarnaResponsive = new KlarnaResponsive();
    //}

    // Add login functionality if the form exists
    if (document.getElementById('klarna_form-login')) {
        new KlarnaLogin();
    }

    var shippingChecked = document.getElementsByClassName('.shipping-method-input-radio:checked');
    document.cookie = 'klarnaDiscountShipping=0; expires=0;';

    if (!shippingChecked) {
        document.querySelector("input[name=shipping_method]:first-child").checked = true;
        document.cookie = 'klarnaAddShipping=1; expires=0;';
        massUpdateCartKlarna(["shipping"], '', '');
    }

    bindCheckoutControls();
    registerKlarnaApiChange();
    window.klarnaAjaxRunning = 0;
    window.klarnaAjaxArray = new Array();

});

function klarnaCheckoutGo(url) {
    window.location.assign(url);
}

