<?php

class Swipezoom_InternationalShipping_Model_System_Config_Source_Extmode 
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Development')),
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Production')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            0 => Mage::helper('adminhtml')->__('Development'),
            1 => Mage::helper('adminhtml')->__('Production'),
        );
    }

}