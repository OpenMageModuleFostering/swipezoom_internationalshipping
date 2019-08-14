<?php
class Swipezoom_InternationalShipping_Model_Sales_Quote_Address_Total_Vat extends Mage_Sales_Model_Quote_Address_Total_Abstract{
    protected $_code = 'VAT';
 
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);
 
        $this->_setAmount(0);
        $this->_setBaseAmount(0);
 
        $items = $this->_getAddressItems($address);
        if (!count($items)) {
            return $this; //this makes only address type shipping to come through
        }
 
        $quote = $address->getQuote();
        
        $fullActionName = Mage::app()->getFrontController()->getAction()->getFullActionName();
        $extraRates = Mage::getSingleton('checkout/session')->getSwipezoomExtraRates();
        $extraRates['CustVatAmount'] = floatval($extraRates['CustVatAmount']);
        
        if(!empty($extraRates['CustVatAmount']) && $fullActionName != 'checkout_cart_index') {
            $address->setVatAmount($extraRates['CustVatAmount']);
            $address->setBaseVatAmount($extraRates['CustVatAmount']);
            $quote->setVatAmount($extraRates['CustVatAmount']);
 
            $address->setGrandTotal($address->getGrandTotal() + $address->getVatAmount());
            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getBaseVatAmount());
        }
    }
 
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        if ($address->getData('address_type') == 'billing')
            return $this;

        $fullActionName = Mage::app()->getFrontController()->getAction()->getFullActionName();
        
        $amt = floatval($address->getVatAmount());

        if(!empty($amt) && $fullActionName != 'checkout_cart_index') {
            $address->addTotal(array(
                    'code'=>$this->getCode(),
                    'title'=>'VAT',
                    'value'=> $amt
            ));
        }
        return $this;
    }
}