document.observe("dom:loaded", function() {
    $$('a.alert-price, a.link-wishlist, a.link-share,#product_addtocart_form').each(function(link){
        link.writeAttribute('target','_parent');
        link.writeAttribute('onclick','');
        link.onclick = "";
        jQuery(link).removeAttr("onclick");
	});
});