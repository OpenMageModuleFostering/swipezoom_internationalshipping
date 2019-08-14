<?php

/**
 *	Swipezoom_CardPayment_Model_Standard
 *
 * @author Swipezoom
 */

class Swipezoom_CardPayment_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	
	protected $_code = 'cardpayment';
	protected $_canSaveCc   = false;
    protected $_formBlockType = 'cardpayment/form_ccsave';
    protected $_infoBlockType = 'cardpayment/info_ccsave';
	
	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = true;
	protected $_canUseForMultishipping  = false;
	
	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('cardpayment/payment/redirect', array('_secure' => true));
	}

	public function isAvailable($quote = null){

	    //define array with method codes that will enable this payment methods
	    $enableOnMethods = array(
	       'swipezoom'
	    );

	    //get the current shipping method from quote 
	    $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
	    $shippingMethod = explode('_',$shippingMethod);
	    
	    $paymentmode = Mage::getStoreConfig('carriers/swipezoom/paymentenabled',Mage::app()->getStore());
	    
	    if(!in_array($shippingMethod[0],$enableOnMethods) || $paymentmode != 1){
	    	// swipezoom card payment not available
	        return false;
	    }

	    return parent::isAvailable($quote);
	}
}
?>