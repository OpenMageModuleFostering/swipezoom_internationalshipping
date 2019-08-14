<?php

/**
 *  Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Grid_Swipezoomorder
 *
 * @author Swipezoom
 */
class Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Grid_Swipezoomorder extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        if(!empty($value)){
        return '<span class="swipezoomorder"> #'.$value.' <img src="'.$this->getSkinUrl("internationalshipping/images/logo.jpg").'" height="20px;" title="Powered by Swipezoom" style="float:right;" /></span><style ="text/css">.sdwipezoom{padding-left:4px;}</style>';    
        }
        
    }
}