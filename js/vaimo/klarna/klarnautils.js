/** Helping functions for replacing jQuery functions with default js * */
(function(funcName, baseObj) {

    funcName = funcName || "docReady";
    baseObj = baseObj || window;
    var readyList = [];
    var readyFired = false;
    var readyEventHandlersInstalled = false;

    function ready() {
        if (!readyFired) {
            readyFired = true;
            for (var i = 0; i < readyList.length; i++) {
                readyList[i].fn.call(window, readyList[i].ctx);
            }
            readyList = [];
        }
    }

    function readyStateChange() {
        if ( document.readyState === "complete" ) {
            ready();
        }
    }

    baseObj[funcName] = function(callback, context) {
        if (readyFired) {
            setTimeout(function() {callback(context);}, 1);
            return;
        } else {
            readyList.push({fn: callback, ctx: context});
        }
        if (document.readyState === "complete") {
            setTimeout(ready, 1);
        } else if (!readyEventHandlersInstalled) {
            if (document.addEventListener) {
                document.addEventListener("DOMContentLoaded", ready, false);
                window.addEventListener("load", ready, false);
            } else {
                document.attachEvent("onreadystatechange", readyStateChange);
                window.attachEvent("onload", ready);
            }
            readyEventHandlersInstalled = true;
        }
    }

})("docReady", window);

// Abstract(s) for Klarna: Suspend and resume
function _klarnaCheckoutWrapper(callback) {
    if (typeof _klarnaCheckout != 'undefined') {
        _klarnaCheckout(function(api) {
            if (typeof callback === 'function') {
                callback(api);
            }
        });
    }
};

// Helpers for Klarna: Suspend and resume
function klarnaCheckoutSuspend() {
    _klarnaCheckout(function(api) {
        api.suspend();
    });
};

function klarnaCheckoutResume() {
    _klarnaCheckout(function(api) {
        api.resume();
    });
};

function initiateFromKlarnaAjaxArray() {
    if (window.klarnaAjaxArray.length == 0) {
        return;
    }
    url = window.klarnaAjaxArray[0].url;
    dataString = window.klarnaAjaxArray[0].dataString;
    callbackOnSuccess = window.klarnaAjaxArray[0].callbackOnSuccess;
    callbackOnError = window.klarnaAjaxArray[0].callbackOnError;
    callbackOnOther = window.klarnaAjaxArray[0].callbackOnOther;
    async = window.klarnaAjaxArray[0].async;
    window.klarnaAjaxArray.splice(0, 1);
    vanillaAjax(url, dataString, callbackOnSuccess, callbackOnError, callbackOnOther, async);
    
}

function vanillaAjax(url, dataString, callbackOnSuccess, callbackOnError, callbackOnOther, async) {
    if (window.klarnaAjaxRunning == 1) {
        window.klarnaAjaxArray[klarnaAjaxArray.length] = {};
        var index = klarnaAjaxArray.length - 1;
        window.klarnaAjaxArray[index].url = url;
        window.klarnaAjaxArray[index].dataString = dataString;
        window.klarnaAjaxArray[index].callbackOnSuccess = callbackOnSuccess;
        window.klarnaAjaxArray[index].callbackOnError = callbackOnError;
        window.klarnaAjaxArray[index].callbackOnOther = callbackOnOther;
        window.klarnaAjaxArray[index].async = async
    } else {
        var xmlhttp;
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == XMLHttpRequest.DONE ) {
                var response = xmlhttp.responseText;
                if (xmlhttp.status == 200 && callbackOnSuccess != ''){
                    callbackOnSuccess(response);
                } else if (xmlhttp.status == 400 && callbackOnError != '') {
                    callbackOnError(response);
                } else if (callbackOnOther != '') {
                    callbackOnOther(response);
                }
                window.klarnaAjaxRunning = 0;
                //console.log("The request is done : " + url);
                initiateFromKlarnaAjaxArray();
            }
        }
        
        //console.log("The request is starting : " + url);
        window.klarnaAjaxRunning = 1;
        xmlhttp.open("POST", url);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xmlhttp.send(dataString);
    }
};

// fade out
function fadeOut(el){
    el.style.opacity = 0;

    (function fade() {
        if ((el.style.opacity -= .1) < 0) {
            el.style.display = "none";
        } else {
            requestAnimationFrame(fade);
        }
    })();
};

// fade in
function fadeIn(el, display){
    if (el) {
        el.style.opacity = 1;
        el.style.display = display || "block";

        (function fade() {
            var val = parseFloat(el.style.opacity);
            if (!((val += .1) > 1)) {
                el.style.opacity = val;
                requestAnimationFrame(fade);
            }
        })();
    }
};

// Closest
function closest() {
    var parents = [];
    var tmpList = document.getElementsByClassName('world');
    for (var i = 0; i < tmpList.length; i++) {
        parents.push(tmpList[i].parentNode);
    }

    var list = [];
    for (var i = 0; i < parents.lenght; i++) {
        if ((parents[i].hasAttribute('data-prefix')) && (parents[i].attributes.getNamedItem('data-prefix').textContent == 'hello')) {
            list.push(tmpList[i]);
        }
    }
    return list;
};

// IE check
function isIECheck() {
    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE ");

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {
        return true;
    } else {
        return false;
    }
};

// Serialize
/* Add the forEach method to Array elements if absent */
if (!Array.prototype.forEach) {
    Array.prototype.forEach = function(fn, scope) {
        'use strict';

        var i, len;

        for (i = 0, len = this.length; i < len; ++i) {
            if (i in this) {
                fn.call(scope, this[i], i, this);
            }
        }
    };
}

/* Extrapolate the Array forEach method to NodeList elements if absent */
if (!NodeList.prototype.forEach) {
    NodeList.prototype.forEach = Array.prototype.forEach;
}

/*
 * Extrapolate the Array forEach method to HTMLFormControlsCollection elements
 * if absent
 */
if (!isIECheck) {
    if (!HTMLFormControlsCollection.prototype.forEach) {
        HTMLFormControlsCollection.prototype.forEach = Array.prototype.forEach;
    }
} else {
    if (!HTMLCollection.prototype.forEach) {
        HTMLCollection.prototype.forEach = Array.prototype.forEach;
    }
}

/**
 * Convert form elements to query string or JavaScript object.
 *
 * @param asObject
 *            If the serialization should be returned as an object.
 */
HTMLFormElement.prototype.serialize = function(asObject) {
    'use strict';
    var form = this;
    var elements;
    var add = function(name, value) {
        value = encodeURIComponent(value);

        if (asObject) {
            elements[name] = value;
        } else {
            elements.push(name + '=' + value);
        }
    };

    if (asObject) {
        elements = {};
    } else {
        elements = [];
    }

    form.elements.forEach(function(element) {
        switch (element.nodeName) {
            case 'BUTTON':
                /* Omit this elements */
                break;

            default:
                switch (element.type) {
                    case 'submit':
                    case 'button':
                        /* Omit this types */
                        break;
                    case 'radio':
                        if (element.checked) {
                            add(element.name, element.value);
                        }
                        break;
                    default:
                        add(element.name, element.value);
                        break;
                }
                break;
        }
    });

    if (asObject) {
        return elements;
    }

    return elements.join('&');
};

var KlarnaLogin = (function () {
    "use strict";

    var me = function (config) {
        var cfg = config || {};

        this.form = cfg.form || document.getElementById('klarna_form-login');
        this.registerListeners();
    };

    me.prototype.registerListeners = function () {
        if(this.form) {
            this.form.addEventListener('submit', this.doLogin.bind(this));
        }
    };

    me.prototype.doLogin = function (e) {
        e.preventDefault();

        var form = e.target,
            data = form.serialize(false),
            url = form.action;

        vanillaAjax(url, data, this.successCallback.bind(this), this.errorCallback.bind(this), '', true);
    };

    me.prototype.showMessage = function (message) {
        var messageEl = document.getElementById('klarna_msg'),
            messageContentEl = messageEl.querySelector('.klarna_msg-content');

        messageContentEl.textContent = message;
        fadeIn(messageEl);
    };

    me.prototype.successCallback = function (response) {
        var data = JSON.parse(response),
            messageEl = document.getElementById('klarna_msg');
        
        // Show message if we get a response code
        if(!isNaN(data['r_code'])) {

            if (data['r_code'] < 0) { // Error
                if (!messageEl.classList.contains('error')) {
                    messageEl.classList.add('error');
                }
                this.showMessage(data.message);
            } else {
                messageEl.classList.remove('error'); // Success
                this.showMessage(data.message);
            }
        }
        
        if (typeof data.reload_checkout !== "undefined" && data.reload_checkout == 1) {
            window.location.reload();
        }
    };

    me.prototype.errorCallback = function (response) {
        try {
            var data = JSON.parse(response);
            this.showMessage(data.message);
        }
        catch (e) {
            var loginFailedText = Translator.translate("Could not log in. Please try again");
            this.showMessage(loginFailedText);
        }

        console.log("Login failed! Here's the data:");
        console.log(data);
    };

    return me;
})();

var KlarnaResponsive = (function () {
    var me = function (config) {
        var cfg = config || {};

        this.element = cfg.element || document.getElementById('klarna_container');
        this.isRunning = false;
        this.storedSidebarEl = document.createDocumentFragment();
        this.mobileBreakPoint = 992;

        // Only run init functions if the site admin has set the Klarna module to use the responsive layout
        if(this.getLayout() === 'two-column') {
            this.registerListeners();
            this.updateLayout();
        }
    };

    me.prototype.registerListeners = function () {
        window.addEventListener('resize', resize.bind(this));
    };

    function resize(e) {
        if (!this.isRunning) {
            this.isRunning = true;

            if (window.requestAnimationFrame) {
                window.requestAnimationFrame(this.updateLayout.bind(this));
            } else {
                setTimeout(this.updateLayout.bind(this), 66);
            }
        }
    }

    me.prototype.getLayout = function () {
        var layoutVal = parseInt(this.element.getAttribute('data-layout'));

        if(layoutVal === 0) {
            return 'default';
        }
        else if (layoutVal === 1) {
            return 'two-column';
        }

        return '';
    };

    me.prototype.getDesktopLayout = function (el) {
        var sidebarEls = getSidebarElements(el),
            docFragment = document.createDocumentFragment(),
            fragmentSidebarEl,
            sidebarEl = el || this.storedSidebarEl;

        if (el !== "undefined") {
            docFragment.appendChild(sidebarEl);
        } else {
            if (sidebarEl) {
                docFragment.appendChild(sidebarEl);
            }
            fragmentSidebarEl = docFragment.querySelector('#klarna_sidebar');
    
            // Add all sidebar items to the temporary sidebar fragment
            if (sidebarEls.payment) {
                fragmentSidebarEl.appendChild(sidebarEls.payment);
            }
            if (sidebarEls.shipping) {
                fragmentSidebarEl.appendChild(sidebarEls.shipping);
            }
            if (sidebarEls.cart) {
                fragmentSidebarEl.appendChild(sidebarEls.cart);
            }
            if (sidebarEls.discount) {
                fragmentSidebarEl.appendChild(sidebarEls.discount);
            }
        }

        return docFragment;
    };

    me.prototype.setMobileLayout = function (el) {
        var groupedEls = getSidebarElements(el, true),
            sidebarEls = getSidebarElements(el),
            mainContentEl = document.getElementById('klarna_main'),
            iframeEl = document.getElementById('klarna_checkout'),
            tempEl = document.createDocumentFragment();

        for(var key in groupedEls) {
            if(groupedEls.hasOwnProperty(key) && groupedEls[key] != null) {
                tempEl.appendChild(groupedEls[key]);
            }
        }

        mainContentEl.insertBefore(tempEl, iframeEl);
        if (sidebarEls.payment) {
            mainContentEl.appendChild(sidebarEls.payment);
        }
    };

    /**
     * Gets the sidebar children elements as an object
     * @param sidebarEl (optional)
     * @param getGroup
     * @returns {{cart: HTMLElement, shipping: HTMLElement, discount: HTMLElement}}
     */
    function getSidebarElements (sidebarEl, getGroup) {
        var ref = sidebarEl || document,
            cartEl = document.getElementById('klarna_cart-container') ? document.getElementById('klarna_cart-container') : ref.querySelector('#klarna_cart-container'),
            shippingEl = document.getElementById('klarna_shipping') ? document.getElementById('klarna_shipping') : ref.querySelector('#klarna_shipping'),
            discountEl = document.getElementById('klarna_discount') ? document.getElementById('klarna_discount') : ref.querySelector('#klarna_discount'),
            groupedEls = {
                cart: cartEl,
                shipping: shippingEl,
                discount: discountEl
            },
            sidebarEls = groupedEls;

        sidebarEls.payment = document.getElementById('klarna_methods') ? document.getElementById('klarna_methods') : ref.querySelector('#klarna_methods');

        return getGroup ? groupedEls : sidebarEls;
    }

    /**
     * Checks if the current viewport width corresponds to the predefined mobile breakpoint
     * and if so changes the layout to mobile. Otherwise the layout is set to desktop.
     */
    me.prototype.updateLayout = function () {
        var sidebarEl = document.getElementById('klarna_sidebar'),
            klarnaContainer = document.getElementById('klarna_container'),
            mainContentEl = document.getElementById('klarna_main'),
            cartEl = document.getElementById('klarna_cart-container');

        if(this.getMode() === 'mobile' && sidebarEl && !mainContentEl.contains(cartEl)) {
            this.storedSidebarEl = sidebarEl.cloneNode(false);

            this.setMobileLayout();

            sidebarEl.parentNode.removeChild(sidebarEl);
        }
        else if(this.getMode() === 'desktop' && !sidebarEl && mainContentEl.contains(cartEl)) {
            klarnaContainer.appendChild(this.getDesktopLayout());
        }

        this.isRunning = false;
    };

    me.prototype.getMode = function () {
        var viewportWidth = window.innerWidth;

        if(viewportWidth < this.mobileBreakPoint) {
            return 'mobile';
        }
        else if(viewportWidth >= this.mobileBreakPoint) {
            return 'desktop';
        }
        else {
            return false;
        }
    };

    /**
     * Renders the given element as a sidebar or as a part of the main content depending
     * on whether the browser window is in "mobile" or "desktop" mode. This is mostly intended to be used
     * when the cart is updated through AJAX as the AJAX response will typically be an html view.
     * @param el {HTMLElement}
     */
    me.prototype.drawLayoutForElement = function (el) {
        if(!el) {
            return false;
        }

        var klarnaContainer = document.getElementById('klarna_container'),
            mainContentEl = document.getElementById('klarna_main');

        if(this.getMode() === 'mobile') {
            var sidebarEls = getSidebarElements(null, true);

            // Remove all the current sidebar items inside the main content area
            for(var key in sidebarEls) {
                if(sidebarEls.hasOwnProperty(key) && sidebarEls[key] != null) {
                    mainContentEl.removeChild(sidebarEls[key]);
                }
            }

            this.setMobileLayout(el);
        }
        else {
            var newSidebar = this.getDesktopLayout(el);
            klarnaContainer.replaceChild(newSidebar, klarnaContainer.querySelector('#klarna_sidebar'));
        }
    };

    return me;
})();