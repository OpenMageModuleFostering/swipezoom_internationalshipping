<?php

/**
 *  Swipezoom_CardPayment_Block_Info_Ccsave 
 *
 * @author Swipezoom
 */

class Swipezoom_CardPayment_Block_Info_Ccsave extends Mage_Payment_Block_Info_Cc
{
    protected function _prepareSpecificInformation($transport = null)
    {   
        $info = $this->getInfo();
        $additionalinfo = $extra_details = array();

        $transport = new Varien_Object();

        $paymentmode = Mage::getStoreConfig('carriers/swipezoom/paymentenabled',Mage::app()->getStore());
        if($paymentmode == 1 && $info->getMethodInstance()->getCode() == 'cardpayment') {
            
            $additionalinfo = unserialize($info->getAdditionalData());

            if(!empty($additionalinfo)) {
                if(!empty($info->getCcOwner()))
                    $extra_details = array(Mage::helper('payment')->__('Name on the Card') => $info->getCcOwner(),
                    Mage::helper('payment')->__('Expiration Date') => $this->_formatCardDate($info->getCcExpYear(), $this->getCcExpMonth())
                    );

                if(!empty($extra_details))
                    $additionalinfo = array_merge($additionalinfo,$extra_details);
            }

            if(!empty($additionalinfo))
                $transport->addData($additionalinfo);
        }

        return $transport;
    }

}
