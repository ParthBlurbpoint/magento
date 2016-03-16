function applyProductPartialplan(partialpaymentArr) {
    var submitUrl = $('productpartialplanurl').value;
    new Ajax.Request(submitUrl, {
	method: 'post',
	parameters: {
	    partialparams: JSON.stringify(partialpaymentArr),
	    fullCart: 'false'
	},
	onComplete: function (transport) {
	    var response = transport.responseText;
	    if (response) {
		AdminOrder.prototype.setLoadBaseUrl($('loadblockurl').value);		
		AdminOrder.prototype.loadArea(['totals', 'items'], true);		
		AdminOrder.prototype.setLoadBaseUrl(null);
		/**
		 * To-Do: Permanent fix to bring back add products button on Update 
		 * Following is temporary fix to bring back the add products button
		*/
		location.reload();  
	    }
	}
    });
}

AdminOrder.addMethods({
    setPartialpaymentMethod: function () {
	var data = {};
	data['partialpayment'] = {};
	data['partialpayment']['options'] = $('partialpayment_full_cart_options').value;
	data['partialpayment']['installments'] = $('partialpayment[installments]').value;
	data['partialpayment']['price'] = $('partialpayment[price]').value;
	data['partialpayment']['price_type'] = $('partialpayment[price_type]').value;

	data['order[partialpayment]'] = method;
	this.loadArea(['shipping_method', 'totals', 'billing_method', 'partialpayment'], true, data);
    },
    addProduct: function (id) {
	this.loadArea(['items', 'shipping_method', 'totals', 'billing_method', 'partialpayment'], true, {add_product: id, reset_shipping: true});
    },
    removeQuoteItem: function (id) {
	this.loadArea(['items', 'shipping_method', 'totals', 'billing_method', 'partialpayment'], true,
		{remove_item: id, from: 'quote', reset_shipping: true});
    },
    productGridAddSelected: function () {
	if (this.productGridShowButton)
	    Element.show(this.productGridShowButton);
	var area = ['search', 'items', 'shipping_method', 'totals', 'giftmessage', 'billing_method', 'partialpayment'];
	// prepare additional fields and filtered items of products
	var fieldsPrepare = {};
	var itemsFilter = [];
	var products = this.gridProducts.toObject();
	for (var productId in products) {
	    itemsFilter.push(productId);
	    var paramKey = 'item[' + productId + ']';
	    for (var productParamKey in products[productId]) {
		paramKey += '[' + productParamKey + ']';
		fieldsPrepare[paramKey] = products[productId][productParamKey];
	    }
	}
	this.productConfigureSubmit('product_to_add', area, fieldsPrepare, itemsFilter);
	productConfigure.clean('quote_items');
	this.hideArea('search');
	this.gridProducts = $H({});
    },
    applyCoupon : function(code){
        this.loadArea(['items', 'shipping_method', 'totals', 'billing_method', 'partialpayment'], true, {'order[coupon][code]':code, reset_shipping: true});
    },
    itemsUpdate: function () {
	var partialpaymentArr = {};
	$$('select[name^=options[installments]]').each(function (e) {
	    if (e.value !== '') {
		var matches = e.id.match(/\[(.*?)\]/);
		if (matches) {
		    var quoteItemId = matches[1];
		}

		partialpaymentArr[quoteItemId] = {};
		partialpaymentArr[quoteItemId]['partialpayment'] = {};
		partialpaymentArr[quoteItemId]['partialpayment']['options'] = e.value;

		var label = 'options[' + quoteItemId + '][installments]';

		partialpaymentArr[quoteItemId]['partialpayment']['installments'] = document.getElementsByName(label)[0].value;

		label = 'options[' + quoteItemId + '][price]';

		partialpaymentArr[quoteItemId]['partialpayment']['price'] = document.getElementsByName(label)[0].value;

		label = 'options[' + quoteItemId + '][price_type]';

		partialpaymentArr[quoteItemId]['partialpayment']['price_type'] = document.getElementsByName(label)[0].value;
		var labelId = 'applied-partialpayment-details-' + quoteItemId;
		$(labelId).style.display = 'block';
	    }
	});

	var area = ['sidebar', 'items', 'shipping_method', 'billing_method', 'totals', 'giftmessage', 'partialpayment'];
	// prepare additional fields
	var fieldsPrepare = {update_items: 1};
	var info = $('order-items_grid').select('input', 'select', 'textarea');
	for (var i = 0; i < info.length; i++) {
	    if (!info[i].disabled && (info[i].type != 'checkbox' || info[i].checked)) {
		fieldsPrepare[info[i].name] = info[i].getValue();
	    }
	}

	fieldsPrepare = Object.extend(fieldsPrepare, this.productConfigureAddFields);
	this.productConfigureSubmit('quote_items', area, fieldsPrepare);
	this.orderItemChanged = false;
	setTimeout(function () {
	    applyProductPartialplan(partialpaymentArr);
	}, 7000);
    },
});