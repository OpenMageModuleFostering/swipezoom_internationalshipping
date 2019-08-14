<?php

/**
 *  Swipezoom_InternationalShipping_Model_Total_Vat_Creditmemo
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Model_Total_Vat_Creditmemo extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $amount = floatval($order->getVatAmount());
        
        if (!empty($amount)) {
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $amount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $amount);
        }
 
        return $this;
    }
}
	 