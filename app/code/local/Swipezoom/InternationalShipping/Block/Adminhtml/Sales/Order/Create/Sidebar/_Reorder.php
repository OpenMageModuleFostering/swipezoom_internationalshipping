<?php

/**
 *  Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Order_Create_Sidebar_Reorder
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Order_Create_Sidebar_Reorder extends Mage_Adminhtml_Block_Sales_Order_Create_Sidebar_Reorder
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
		
        if ($order = $this->getLastOrder()) {
            $items = array();
            foreach ($order->getItemsCollection() as $item) {
				
                if (!$item->getParentItem() && !in_array($item->getSku(),$ar)) {
                    $items[] = $item; 
                }
            }
            return $items;
        }
        return false;
    }
}
			