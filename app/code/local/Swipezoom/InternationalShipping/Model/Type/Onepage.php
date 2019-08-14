<?php
/**
 *  Swipezoom_InternationalShipping_Model_Type_Onepage
 *
 * @author Swipezoom
 */

 class Swipezoom_InternationalShipping_Model_Type_Onepage extends Mage_Checkout_Model_Type_Onepage
{
     protected function validateOrder()
    {
        $hideshipping = Mage::getStoreConfig('carriers/swipezoom/hideshipping',Mage::app()->getStore());

        if($hideshipping) {
            $helper = Mage::helper('checkout');
            if ($this->getQuote()->getIsMultiShipping()) {
                Mage::throwException($helper->__('Invalid checkout type.'));
            }
      
            $addressValidation = $this->getQuote()->getBillingAddress()->validate();
            if ($addressValidation !== true) {
                Mage::throwException($helper->__('Please check billing address information.'));
            }
      
            if (!($this->getQuote()->getPayment()->getMethod())) {
                Mage::throwException($helper->__('Please select valid payment method.'));
            }
        } else {
             if ($this->getQuote()->getIsMultiShipping()) {
            Mage::throwException(Mage::helper('checkout')->__('Invalid checkout type.'));
            }

            if (!$this->getQuote()->isVirtual()) {
                $address = $this->getQuote()->getShippingAddress();
                $addressValidation = $address->validate();
                if ($addressValidation !== true) {
                    Mage::throwException(Mage::helper('checkout')->__('Please check shipping address information.'));
                }
                $method= $address->getShippingMethod();
                $rate  = $address->getShippingRateByCode($method);
                if (!$this->getQuote()->isVirtual() && (!$method || !$rate)) {
                    Mage::throwException(Mage::helper('checkout')->__('Please specify shipping method.'));
                }
            }

            $addressValidation = $this->getQuote()->getBillingAddress()->validate();
            if ($addressValidation !== true) {
                Mage::throwException(Mage::helper('checkout')->__('Please check billing address information.'));
            }

            if (!($this->getQuote()->getPayment()->getMethod())) {
                Mage::throwException(Mage::helper('checkout')->__('Please select valid payment method.'));
            }
        }
    }
}