<?php

/**
 *  Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Grid_Swipezoomorderinvoice
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Grid_Swipezoomorderinvoice extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
		$order = Mage::getModel('sales/order')->loadByIncrementId($value);		
        if($order->getSwipezoomOrderNumberTemp()){

        return '<span class="swipezoomorder"> #'.$order->getSwipezoomOrderNumberTemp().' <img src="'.$this->getSkinUrl("internationalshipping/images/logo.jpg").'" height="20px;" title="Powered by Swipezoom" style="float:right;" /></span><script type="text/javascript">
        Event.observe(document,"dom:loaded",function(){
                $$(".swipezoomorder").each(function(ele){
                    ele.up().previous().previous().down().remove();
                    ele.up().previous().up().addClassName("swipezoom-row");
                });
        });
        </script><style ="text/css">.sdwipezoom{padding-left:4px;}</style>';    
        }
        
    }
}