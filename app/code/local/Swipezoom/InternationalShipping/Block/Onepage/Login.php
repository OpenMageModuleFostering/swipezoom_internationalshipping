<?php

/**
 *  Swipezoom_InternationalShipping_Block_Onepage_Login
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Block_Onepage_Login extends Mage_Checkout_Block_Onepage_Abstract
{
   
    protected function _construct()
    {
        if (!$this->isCustomerLoggedIn()) {
            $this->getCheckout()->setStepData('login', array('label'=>Mage::helper('checkout')->__('Checkout Method'), 'allow'=>true));
        }
        parent::_construct();
    }

    public function getMessages()
    {
        return Mage::getSingleton('customer/session')->getMessages(true);
    }

    public function getPostAction()
    {
        return Mage::getUrl('customer/account/loginPost', array('_secure'=>true));
    }

    public function getMethod()
    {
        return $this->getQuote()->getMethod();
    }

    public function getMethodData()
    {
        return $this->getCheckout()->getMethodData();
    }

    public function getSuccessUrl()
    {
        return $this->getUrl('*/*');
    }

    public function getErrorUrl()
    {
        return $this->getUrl('*/*');
    }

    /**
     * Retrieve username for form field
     *
     * @return string
     */
    public function getUsername()
    {
        return Mage::getSingleton('customer/session')->getUsername(true);
    }
    
    public function isShow()
    {
        $force_guest_checkout = Mage::getStoreConfig('carriers/swipezoom/force_guest_checkout',Mage::app()->getStore());

        if($force_guest_checkout) {
            return false;
        } else {
            return true;
        }
    }
} 