<?php

/**
 *  Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Order_Create_Sidebar_Customreorder
 *
 * @author Swipezoom
 */
 
class Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Order_Create_Sidebar_Customreorder extends Mage_Adminhtml_Block_Sales_Order_Create_Sidebar_Reorder
{
	 /**
     * Retrieve item collection
     *
     * @return mixed
     */
    public function getItemCollection()
    {
		
		$current = Mage::getSingleton('adminhtml/sales_order_create')->getQuote()->getAllItems();
		$ar = array();
		foreach ($current as $item1) { $ar[] = $item1->getSku();  }
		
		
        if ($order = Mage::getModel('sales/order')->load(Mage::getSingleton('adminhtml/session_quote')->getOrder()->getId())) {
            $items = array();
			
            foreach ($order->getItemsCollection() as $item) {
			    if (!$item->getParentItem() && !in_array($item->getSku(),$ar)) {
                    $items[] = $item; 
                }
            }
//			echo "1";
            return $items;
        }
        return false;
    }
}
			