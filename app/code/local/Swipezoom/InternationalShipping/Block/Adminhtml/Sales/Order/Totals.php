<?php

/**
 *  Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Order_Totals
 *
 * @author Swipezoom
 */
class Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Order_Totals extends Mage_Adminhtml_Block_Sales_Order_Totals
{
    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
        parent::_initTotals();
        $amount = $this->getSource()->getVatAmount();
        $amount = floatval($amount);
        
        if (!empty($amount)) {
            $this->addTotalBefore(new Varien_Object(array(
                'code'      => 'VAT',
                'value'     => $amount,
                'base_value'=> $amount,
                'label'     => 'VAT',
            ), array('shipping', 'tax')));
        }
 
        return $this;
    }
 
}