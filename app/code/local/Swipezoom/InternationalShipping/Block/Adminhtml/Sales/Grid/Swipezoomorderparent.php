<?php

/**
 *  Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Grid_Swipezoomorderparent
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Grid_Swipezoomorderparent extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
		$order = Mage::getModel('sales/order')->loadByIncrementId($value);
		
		$swipezoomcreditmemo=  Mage::getModel('internationalshipping/swipezoomcreditmemo')->getCollection()->addFieldToFilter('swipezoomorderid',$order->getSwipezoomOrderNumberTemp());
		foreach($swipezoomcreditmemo as $ss){
			$sorder = $ss->getSwipezoomorderid();
		}
		if(count($swipezoomcreditmemo)){
		 return '<span class="swipezoomorder del'.$order->getId().'"> #'.$sorder.' <img src="'.$this->getSkinUrl("internationalshipping/images/logo.jpg").'" height="20px;" title="Powered by Swipezoom" style="float:right;" /></span><style ="text/css">.sdwipezoom{padding-left:4px;}</style>';    
        }
		
		$swipezoomcreditmemo1 =  Mage::getModel('internationalshipping/swipezoomcreditmemo')->getCollection()->addFieldToFilter('refswipezoomorderid',$order->getSwipezoomOrderNumberTemp());
		foreach($swipezoomcreditmemo1 as $ss){
			$sorder = $ss->getSwipezoomorderid();
		}
		if($sorder){
		return '<span class="swipezoomorder del'.$order->getId().'"> #'.$sorder.' <img src="'.$this->getSkinUrl("internationalshipping/images/logo.jpg").'" height="20px;" title="Powered by Swipezoom" style="float:right;" /></span><style ="text/css">.sdwipezoom{padding-left:4px;}</style>';   
        }
		
		return;
    }
}