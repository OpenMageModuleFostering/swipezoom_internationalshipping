<?php

/**
 *  Swipezoom_InternationalShipping_CartControllers
 *
 * @author Swipezoom
 */

require_once 'Mage/Checkout/controllers/CartController.php';

class Swipezoom_InternationalShipping_CartController extends Mage_Checkout_CartController
{
   /**
     * Shopping cart display action
     */
    public function indexAction()
    {
        $shippingaddress = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress(); 
        $shippingaddress->setShippingMethod(''); 
        $shippingaddress->save(); 

        //Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->unsetData();
    	Mage::getSingleton('checkout/session')->setSwipezoomExtraRates('');
		Mage::getSingleton('core/session')->setSwipezoomExtraRates('');
		Mage::getSingleton('core/session')->setRequestedString('');
        parent::indexAction();
    }
}

?>

