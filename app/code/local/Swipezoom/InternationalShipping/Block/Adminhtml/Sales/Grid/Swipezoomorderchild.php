<?php

/**
 *  Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Grid_Swipezoomorderchild
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Grid_Swipezoomorderchild extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
         $value =  $row->getData($this->getColumn()->getIndex());
		$order = Mage::getModel('sales/order')->loadByIncrementId($value);
		
		
		$swipezoomcreditmemo1 =  Mage::getModel('internationalshipping/swipezoomcreditmemo')->getCollection()->addFieldToFilter('refswipezoomorderid',$order->getSwipezoomOrderNumberTemp());
		foreach($swipezoomcreditmemo1 as $ss){
			$sorder = $ss->getRefswipezoomorderid();
		}
		if($sorder){
		 return '<span class="swipezoomorder1"> #'.$sorder.' <img src="'.$this->getSkinUrl("internationalshipping/images/logo.jpg").'" height="20px;" title="Powered by Swipezoom" style="float:right;" /></span>';    
        }
		
		
		$swipezoomcreditmemo=  Mage::getModel('internationalshipping/swipezoomcreditmemo')->getCollection()->addFieldToFilter('swipezoomorderid',$order->getSwipezoomOrderNumberTemp());
		foreach($swipezoomcreditmemo as $ss){
			$sorder = $ss->getRefswipezoomorderid();
		}
		if(count($swipezoomcreditmemo)){
		 return '<span class="swipezoomorder1"> #'.$sorder.' <img src="'.$this->getSkinUrl("internationalshipping/images/logo.jpg").'" height="20px;" title="Powered by Swipezoom" style="float:right;" /></span>';    
        }
		
		return;
        
    }
}