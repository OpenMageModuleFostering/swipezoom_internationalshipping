
// function for storing sensitive data to the Wirecard data storage
function storeData(aPaymentType) {
	        
  checkout.setLoadWaiting('payment');
  
  sw = document.getElementById('sw_card_errors');
  sw.innerHTML = "";
  // sets the selected payment type where sensitive data should be stored
  paymentType = aPaymentType;

  if (typeof WirecardCEE_DataStorage == 'function') {
    // creates a new JavaScript object containing the Wirecard data storage functionality
    var dataStorage = new WirecardCEE_DataStorage();
    // initializes the JavaScript object containing the payment specific information and data
    var paymentInformation = {};

    if (aPaymentType == "CCARD") {
      // gets all sensitive data of corresponding input fields of HTML form and stores them
      // in the JavaScript object

      paymentInformation.pan = document.getElementById('cardpayment_cc_number').value;
      paymentInformation.expirationMonth = document.getElementById('cardpayment_expiration').value;
      paymentInformation.expirationYear = document.getElementById('cardpayment_expiration_yr').value;
      paymentInformation.cardholdername = document.getElementById('cardpayment_cc_owner').value;
      paymentInformation.cardverifycode = document.getElementById('cardpayment_cc_cid').value;
      
      dataStorage.storeCreditCardInformation(paymentInformation, callbackFunction);
    }
  } else {
    checkout.setLoadWaiting(false);
    alert("Something went wrong. Please refresh and try again..");
  }

}

// callback function for displaying the results of storing the
// sensitive data to the Wirecard data storage
callbackFunction = function(aResponse) {
  // initiates the result string presented to the user
  var s = "";
  // checks if response status is without errors
  if (aResponse.getStatus() == 0) {
    // saves all anonymized payment information to a JavaScript object
    var info = aResponse.getAnonymizedPaymentInformation();
    
    sw = document.getElementById('sw_card_errors');
  	sw.innerHTML = "";

    if(document.getElementById('cardpayment_cc_owner').value == "") {
      sw = document.getElementById('sw_card_errors');
      sw.innerHTML = 'Card holder name is missing.<br>';
    } else {
      window.wirecardStorageDone = true;
    }

  
  } else {
    // collects all occured errors and adds them to the result string
    var errors = aResponse.getErrors();
    var errorMessages = [];

    for (e in errors) {
    	if(errors[e].consumerMessage != undefined) {
    		if(errorMessages.indexOf(errors[e].consumerMessage) == -1)
    			errorMessages.push(errors[e].consumerMessage);  		
    	}
    }

    if(document.getElementById('cardpayment_cc_owner').value == "") {
    	errorMessages.push('Card holder name is missing.');
    }

    for (var i = 0; i < errorMessages.length; i++) {
	    s += errorMessages[i]+"<br>";
	 }
    
    sw = document.getElementById('sw_card_errors');
  	sw.innerHTML = s;

  }

  checkout.setLoadWaiting(false);

  // presents result string to the user
}

Event.observe(window, 'load', function (callbackCompleted) {
	payment.save = payment.save.wrap(function (origSaveMethod) {

	    if (this.currentMethod == 'cardpayment') {
	    	window.wirecardStorageDone = false;

		        if (checkout.loadWaiting!=false) return;
			    storeData('CCARD');

			    var s = this;
					    
			    window.sz_payment = setInterval(function(){
			    	if(window.wirecardStorageDone == true) {
						
		        		var validator = new Validation(s.form);
				        if (s.validate() && validator.validate()) {
				        	
				            checkout.setLoadWaiting('payment');
				            var request = new Ajax.Request(
				                s.saveUrl,
				                {
				                    method:'post',
				                    onComplete: s.onComplete,
				                    onSuccess: s.onSave,
				                    onFailure: checkout.ajaxFailure.bind(checkout),
				                    parameters: Form.serialize(s.form)
				                }
				            );
				        }
				        window.wirecardStorageDone = false;
				     	clearInterval(window.sz_payment);	
			    	}
			    },100);

		        
	    } else {
	        origSaveMethod();

	    }
	});
});

   
  