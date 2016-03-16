/**
 * Copyright (c) 2009-2014 Vaimo AB
 *
 * Vaimo reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of Vaimo, except as provided by licence. A licence
 * under Vaimo's rights in the Program may be available directly from
 * Vaimo.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    Vaimo
 * @package     Vaimo_Klarna
 * @copyright   Copyright (c) 2009-2014 Vaimo AB
 */

/*
 * Address search in Checkout
 */
function disableUpdateAddressButton(method)
{
    Form.Element.disable(method + '_update_address_button');
    $(method + '_update_address_button').addClassName('disabled');
}

function enableUpdateAddressButton(method)
{
    Form.Element.enable(method + '_update_address_button');
    $(method + '_update_address_button').removeClassName('disabled');
}

function disableUpdateAddressIndicator(method)
{
    Element.hide(method + '_update_address_span');
}

function enableUpdateAddressIndicator(method)
{
    Element.show(method + '_update_address_span');
}

function clearUpdateAddressMessage(method)
{
    $(method + '_update_address_message').update('');
}

function doAddressSearch(url, method)
{
    var addressReceived = function(transport)
    {
        if (transport.responseText.isJSON()) {
            response = transport.responseText.evalJSON()
            if (response.error) {
                alert(response.message);
            } else {
                $(method + '_update_address_message').update(response.html);
            }
        }
        enableUpdateAddressButton(method);
        disableUpdateAddressIndicator(method);
    };

    var addressError = function(transport)
    {
        if (transport.responseText.isJSON()) {
            response = transport.responseText.evalJSON()
            if (response.error) {
                alert(response.message);
            } else {
                $(method + '_update_address_message').update(response.message);
            }
        }
        enableUpdateAddressButton(method);
        disableUpdateAddressIndicator(method);
    };


    var value = $(method + '_pno').value;
    var params = {pno:value, method:method};
    new Ajax.Request(url, {
        parameters: params,
        method:    'POST',
        onSuccess: addressReceived,
        onFailure : addressError
    });

    enableUpdateAddressIndicator(method);
    disableUpdateAddressButton(method);
    clearUpdateAddressMessage(method);
}

/*
 * Payment plan information in checkout, mainly for Norway
 */
function disableUpdateInformationIndicator(method)
{
    Element.hide(method + '_update_paymentplan_information_span');
}

function enableUpdateInformationIndicator(method)
{
    Element.show(method + '_update_paymentplan_information_span');
}

function clearUpdateInformationMessage(method)
{
    $(method + '_update_paymentplan_information_message').update('');
}

function doPaymentPlanInformation(url, method, plan, storeId)
{
    var informationReceived = function(transport)
    {
        if (transport.responseText.isJSON()) {
            response = transport.responseText.evalJSON()
            if (response.error) {
                alert(response.message);
            } else {
                $(method + '_update_paymentplan_information_message').update(response.html);
            }
        }
        disableUpdateInformationIndicator(method);
    };

    var informationError = function(transport)
    {
        if (transport.responseText.isJSON()) {
            response = transport.responseText.evalJSON()
            if (response.error) {
                alert(response.message);
            } else {
                $(method + '_update_paymentplan_information_message').update(response.message);
            }
        }
        disableUpdateInformationIndicator(method);
    };


    var params = {payment_plan:plan, method:method, store_id:storeId};
    new Ajax.Request(url, {
        parameters: params,
        method:    'POST',
        onSuccess: informationReceived,
        onFailure : informationError
    });

    enableUpdateInformationIndicator(method);
    clearUpdateInformationMessage(method);
}

/*
 * Update PClasses in Admin
 */
function disableUpdatePclassButton()
{
    Form.Element.disable('update_pclass_button');
    $('update_pclass_button').addClassName('disabled');
}

function enableUpdatePclassButton()
{
    Form.Element.enable('update_pclass_button');
    $('update_pclass_button').removeClassName('disabled');
}

function clearUpdatePclassMessage()
{
    $("update_pclass_message").update('');
}

function doUpdatePClass(url)
{
    var pclassReceived = function(transport)
    {
        if (transport.responseText.isJSON()) {
            response = transport.responseText.evalJSON();
            if (response.error) {
                alert(response.message);
            } else {
                var message = $("update_pclass_message");
                var messageContainer = message.up('td');

                var next;
                var counter = 0;
                
                while (next = messageContainer.next()) {
                    next.remove();
                    console.log('removed');
                    counter++;
                }

                if (counter) {
                    messageContainer.setAttribute('colspan', counter);
                }

                message.update(response.html);

            }
        }
        enableUpdatePclassButton();
    };

    var pclassError = function(transport)
    {
        if (transport.responseText.isJSON()) {
            response = transport.responseText.evalJSON();
            if (response.error) {
                alert(response.message);
            } else {
                $("update_pclass_message").update(response.message);
            }
        }
        enableUpdatePclassButton();
    };

    new Ajax.Request(url, {
        method:    'POST',
        onSuccess: pclassReceived,
        onFailure : pclassError
    });

    disableUpdatePclassButton();
    clearUpdatePclassMessage();
}

function insertKlarnaInvoiceElements(merchant, locale, invoiceFee) {

    var klarnaElementId,
        klarnaElement = '';

    klarnaElementId = 'klarna_invoice_readme';
    if(klarnaElement = document.getElementById(klarnaElementId)){
        while(klarnaElement.firstChild) klarnaElement.removeChild(klarnaElement.firstChild);
        new Klarna.Terms.Invoice({
            el: klarnaElementId,
            eid: merchant,
            locale: locale,
            charge: invoiceFee,
            type: 'desktop'
        });
    }

    klarnaElementId = 'vaimo_klarna_invoice_consent_span';
    if(klarnaElement = document.getElementById(klarnaElementId)){
        while(klarnaElement.firstChild) klarnaElement.removeChild(klarnaElement.firstChild);
        new Klarna.Terms.Consent({
            el: klarnaElementId,
            eid: merchant,
            locale: locale,
            type: 'desktop'
        });
    }

}

function insertKlarnaAccountElements(merchant, locale) {

    var klarnaElementId,
        klarnaElement = '';

    klarnaElementId = 'klarna_account_readme';
    if(klarnaElement = document.getElementById(klarnaElementId)){
        while(klarnaElement.firstChild) klarnaElement.removeChild(klarnaElement.firstChild);
        new Klarna.Terms.Account({
            el: klarnaElementId,
            eid: merchant,
            locale: locale,
            type: 'desktop'
        });
    }

    klarnaElementId = 'vaimo_klarna_account_consent_span';
    if(klarnaElement = document.getElementById(klarnaElementId)){
        while(klarnaElement.firstChild) klarnaElement.removeChild(klarnaElement.firstChild);
        new Klarna.Terms.Consent({
            el: klarnaElementId,
            eid: merchant,
            locale: locale,
            type: 'desktop'
        });
    }

}

function insertKlarnaSpecialElements(merchant, locale) {

    var klarnaElementId,
        klarnaElement = '';

    klarnaElementId = 'klarna_special_readme';
    if(klarnaElement = document.getElementById(klarnaElementId)){
        while(klarnaElement.firstChild) klarnaElement.removeChild(klarnaElement.firstChild);
        new Klarna.Terms.Special({
            el: klarnaElementId,
            eid: merchant,
            locale: locale,
            type: 'desktop'
        });
    }

    klarnaElementId = 'vaimo_klarna_special_consent_span';
    if(klarnaElement = document.getElementById(klarnaElementId)){
        while(klarnaElement.firstChild) klarnaElement.removeChild(klarnaElement.firstChild);
        new Klarna.Terms.Consent({
            el: klarnaElementId,
            eid: merchant,
            locale: locale,
            type: 'desktop'
        });
    }
}

function klarnaCheckoutGo(url) {
    window.location.assign(url);
}

function toggleInformationBoxes(id) {
    var innerDivs = document.querySelectorAll('.payment_plan_info_wrapper');
    for(var i=0; i<innerDivs.length; i++)
    {
        Element.hide(innerDivs[i].id);
    }
    Element.show('infobox_pclass_' + id);
}