Event.observe(window, 'load', function() {
    Event.observe('partialpayment_status','change',function(event){
        var elementContainer = Event.findElement(event,'div.fieldset');
        var sourceElement    = event.element();
        var sourceElementId  = sourceElement.id;
        var isDisabled	     = (sourceElement.getValue() == 1) ? false: true;
	
	var isInitialPaymentConfigChecked    = null;
	var isInstallmentCountConfigChecked  = null;
	var isAdditionalPaymentConfigChecked = null;
	
	if($('partialpayment_use_config_initial_payment_amount')) {
	    isInitialPaymentConfigChecked    = $('partialpayment_use_config_initial_payment_amount').checked;
	}
        
	if($('partialpayment_use_config_installments')) {
	    isInstallmentCountConfigChecked  = $('partialpayment_use_config_installments').checked;
	}
	
	if($('partialpayment_use_config_additional_payment_amount')) {
	    isAdditionalPaymentConfigChecked = $('partialpayment_use_config_additional_payment_amount').checked;
	}
        
        
        elementContainer.select('input','select').each(function(e){
            if(sourceElementId != e.id){
                switch(e.id){
                    case 'partialpayment_initial_payment_amount':
			e.disabled = (!isInitialPaymentConfigChecked) ? isDisabled : true;
			(isDisabled) ? e.writeAttribute('class','input-text'): e.writeAttribute('class','input-text required-entry validate-not-negative-number');
			break;
                    case 'partialpayment_additional_payment_amount':
			e.disabled = (!isAdditionalPaymentConfigChecked) ? isDisabled : true;
			(isDisabled) ? e.writeAttribute('class','input-text'): e.writeAttribute('class','input-text required-entry validate-not-negative-number');
			break;
                    case 'partialpayment_installments':
			e.disabled = (!isInstallmentCountConfigChecked) ? isDisabled : true;
			(isDisabled) ? e.writeAttribute('class','input-text'): e.writeAttribute('class','required-entry validate-number validate-not-negative-number');
			break;
                }
            }
        });
    });
});

