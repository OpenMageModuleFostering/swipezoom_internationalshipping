
jQuery(document).ready(function(){ 

    jQuery("#carriers_swipezoom_paymentenabled").change(function(){

        if(jQuery(this).val() == 1) {

                var SITE_BASE_URL = BASE_URL.split('admin')[0];

                if(SITE_BASE_URL) {
                        new Ajax.Request(SITE_BASE_URL+'internationalshipping/index/getEnabledPaymentMethods',{
                        type: "POST", 
                        cache: false,
                        dataType: "html",
                        onSuccess: function(data) {
                            jQuery("#carriers_swipezoom_paymentenabled").parent().append("<span id='payment_response'>"+data.responseText+"</span>");
                        },
                        onFailure: function() { 
                            console.log('Something went wrong...'); 
                        }
                    });
                }
        } else {
            jQuery("#payment_response").remove();
        }
    });

    if(jQuery("#carriers_swipezoom_paymentenabled").val() == 1)
        jQuery("#carriers_swipezoom_paymentenabled").trigger('change');

    jQuery("#carriers_swipezoom_active").change(function(){

        if(jQuery(this).val() == 1) {

                var SITE_BASE_URL = BASE_URL.split('admin')[0];

                if(SITE_BASE_URL) {
                        new Ajax.Request(SITE_BASE_URL+'internationalshipping/index/getEnabledShippingMethods',{
                        type: "POST", 
                        cache: false,
                        dataType: "html",
                        onSuccess: function(data) {
                            jQuery("#carriers_swipezoom_active").parent().append("<span id='shipment_response'>"+data.responseText+"</span>");
                        },
                        onFailure: function() { 
                            console.log('Something went wrong...'); 
                        }
                    });
                }
        } else {
            jQuery("#shipment_response").remove();
        }

    });

     if(jQuery("#carriers_swipezoom_active").val() == 1)
        jQuery("#carriers_swipezoom_active").trigger('change');

});
