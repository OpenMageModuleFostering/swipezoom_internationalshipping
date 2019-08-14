<?php

/**
 *  Swipezoom_InternationalShipping_Model_Total_Vat_Invoice
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Model_Total_Vat_Invoice extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();
   
        $amount = floatval($order->getVatAmount());
        if (!empty($amount)) {
            $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $amount);
        }
 
        return $this;
    }
}
	 