<?php

/**
 *  Swipezoom_InternationalShipping_Block_Onepage_Shipping_Method
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Block_Onepage_Shipping_Method extends Mage_Checkout_Block_Onepage_Shipping_Method
{
    public function isShow()
    {
    	$hideshipping = Mage::getStoreConfig('carriers/swipezoom/hideshipping',Mage::app()->getStore());

    	if($hideshipping) {
        	return false;
    	} else {
    		return true;
    	}
    }
} 