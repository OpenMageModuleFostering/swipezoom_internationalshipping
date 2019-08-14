<?php

/**
 *  Swipezoom_InternationalShipping_Model_Mysql4_Packingdetail
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Model_Mysql4_Packingdetail extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("internationalshipping/packingdetail", "id");
    }
}