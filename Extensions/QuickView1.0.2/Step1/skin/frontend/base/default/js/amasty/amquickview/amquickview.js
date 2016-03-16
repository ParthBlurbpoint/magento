AmQuickview = Class.create();
AmQuickview.prototype =
{
    options : null,
    color: '',

    initialize : function(options) {
        this.options = options;
    },

    initHover : function() {
        if( !$$(AmQuickviewObject.options['item_selector']).length ) return;

        jQuery('body').on(
            {
                'mouseenter': function(){AmQuickviewObject.showLen(this)},
                'mouseleave':  function(){AmQuickviewObject.hideLen(this)},
                'touchstart': function(){AmQuickviewObject.showLen(this)},
                'touchend'  : function(){AmQuickviewObject.showLen(this)}
            },
            AmQuickviewObject.options['item_selector']
        );
    },

    createHover : function(element) {
        var productId = this.getProductId(element);
        if(!productId){
            console.debug("We didn't find price block with product id");
            return false;
        }
        var hover = document.createElement('div');
        hover = $(hover); // fix for IE
        hover.addClassName('amquickview-hover');
        hover.setStyle(AmQuickviewObject.options['css']);


        element.setStyle({
            position : 'relative'
        });

        var link = document.createElement('a');
        link = $(link); // fix for IE
        link.addClassName('amquickview-link');
        link.id = 'amquickview-link-' + productId;
        link.innerHTML = this.options['text'];

        //fix issue with list mode
        var image = element.select('.product-image img');
        if(image[0]){
            var maxWidth  = image[0].getWidth();
            if(maxWidth > 100){
                hover.setStyle({
                    maxWidth : maxWidth + 'px'
                });
            }
            if(image[0].parentNode){
                element = image[0].parentNode;
            }
        }
        element.appendChild(hover);
        hover.hide();
        Event.observe(link, 'click', AmQuickviewObject.showPopup);

        hover.appendChild(link);
        /*
        * set hover block at the bottom of the image
        * */
        if(image[0]) {
            var imageHeight = image[0].getHeight();
            var itemPadding= parseInt(element.getStyle('paddingTop'))
            var hoverHeight = hover.getHeight();
            var px = (imageHeight - hoverHeight + itemPadding);
            hover.setStyle({
                top: px + 'px'
            });

            var imageMargin = parseInt(image[0].getStyle('marginLeft'));
            if(imageMargin){
                hover.setStyle({
                    marginLeft: imageMargin + 'px'
                });
            }
        }

        return hover;
    },

    showPopup : function(event) {
        event.preventDefault();
        var element = Event.element(event);
        if(undefined == AmQuickviewObject.options['url']) return false;
        if(element.hasClassName('am-quickview-icon')){
            element = element.parentNode;
        }

        var productId = element.id.replace(/[a-z-]*/, '');
        if(!productId) return;
        var url = AmQuickviewObject.options['url'] +"?id=" + productId;

        jQuery.fancybox.open({
            padding :0,
            href    : url,
            type    : 'iframe'
        });
        AmQuickviewObject.hideLen(element.parentNode.parentNode);
        return false;
    },

    showLen : function(element) {
        if(!element) return false;

        var hover = $(element).select('.amquickview-hover').first();
        if(!hover){
            hover = this.createHover(element);
        }

        if(hover){
            hover.show();
            if(!this.color) {
                this.color = element.getStyle('background-color');
            }
            element.setStyle({
                backgroundColor: '#eee'
            });
        }
    },


    hideLen : function(element) {
        if(! element) return false;
        var hover = $(element).select('.amquickview-hover').first();
        if(hover){
            hover.hide();
            element.setStyle({
                backgroundColor: this.color
            });
        }
    },

    getProductId: function(parent) {
         var selector =  'div[id^=amasty-product-id-], ' +
						'div.price-box [id^=product-price-], ' +
                        'div.special-price [id^=product-price-], ' +
                        'div.price-box [id^=price-excluding-tax-], ' +
                        'div.price-box [id^=price-including-tax-], ' +
                        'div.price-box [id^=old-price-]';
        var element = parent.select(selector);
        if(element[0]) {
            var id = element[0].id;
            if(id.indexOf("_") > 0){
                var productId = element[0].id.match(/product-price(.*?)_/)[0].replace(/[^\d]/gi, '');
            }
            else{
                var productId = element[0].id.match(/\d+/).first();
            }
            if (parseInt(productId) > 0) {
                return productId;
            }
        }
        var element = parent.select('ul.add-to-links a');
        if(element[0] && element[0].href)
            var productId = element[0].href.match(/product(.*?)form_key/)[0].replace(/[^\d]/gi, '');
        if(parseInt(productId) > 0) {
            return productId;
        }
        return false;
    }
}

    
function AmQuickviewLoad(){
    if(typeof(AmQuickviewObject) != 'undefined') {
        $$('.amquickview-hover').each(function (hover) {
            hover.remove();
        });
        AmQuickviewObject.initHover();
    }
}

document.observe("dom:loaded", function() {
  AmQuickviewLoad();
});