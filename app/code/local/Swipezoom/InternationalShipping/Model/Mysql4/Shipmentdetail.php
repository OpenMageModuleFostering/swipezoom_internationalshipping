<?php
/**
 *  Swipezoom_InternationalShipping_Model_Mysql4_Shipmentdetail
 *
 * @author Swipezoom
 */
 
class Swipezoom_InternationalShipping_Model_Mysql4_Shipmentdetail extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("internationalshipping/shipmentdetail", "id");
    }
}