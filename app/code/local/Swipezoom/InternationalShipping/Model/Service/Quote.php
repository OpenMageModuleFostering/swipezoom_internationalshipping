<?php
/**
 *  Swipezoom_InternationalShipping_Model_Service_Quote
 *
 * @author Swipezoom
 */

 class Swipezoom_InternationalShipping_Model_Service_Quote extends Mage_Sales_Model_Service_Quote
{
     
    protected function _validate()
    {
        $hideshipping = Mage::getStoreConfig('carriers/swipezoom/hideshipping',Mage::app()->getStore());

        if($hideshipping) {
            $helper = Mage::helper('sales');
            if (!$this->getQuote()->isVirtual()) {
                $address = $this->getQuote()->getShippingAddress();
                $addressValidation = $address->validate();
                if ($addressValidation !== true) {
                    Mage::throwException(
                    $helper->__('Please check shipping address information. %s', implode(' ', $addressValidation))
                    );
                }
            }

            $addressValidation = $this->getQuote()->getBillingAddress()->validate();
            if ($addressValidation !== true) {
                Mage::throwException(
                $helper->__('Please check billing address information. %s', implode(' ', $addressValidation))
                );
            }

            if (!($this->getQuote()->getPayment()->getMethod())) {
                Mage::throwException($helper->__('Please select a valid payment method.'));
            }

            return $this;    
        } else {
             if (!$this->getQuote()->isVirtual()) {
                $address = $this->getQuote()->getShippingAddress();
                $addressValidation = $address->validate();
                if ($addressValidation !== true) {
                    Mage::throwException(
                        Mage::helper('sales')->__('Please check shipping address information. %s', implode(' ', $addressValidation))
                    );
                }
                $method= $address->getShippingMethod();
                $rate  = $address->getShippingRateByCode($method);
                if (!$this->getQuote()->isVirtual() && (!$method || !$rate)) {
                    Mage::throwException(Mage::helper('sales')->__('Please specify a shipping method.'));
                }
            }

            $addressValidation = $this->getQuote()->getBillingAddress()->validate();
            if ($addressValidation !== true) {
                Mage::throwException(
                    Mage::helper('sales')->__('Please check billing address information. %s', implode(' ', $addressValidation))
                );
            }

            if (!($this->getQuote()->getPayment()->getMethod())) {
                Mage::throwException(Mage::helper('sales')->__('Please select a valid payment method.'));
            }

            return $this;      
        }

        
    }
}