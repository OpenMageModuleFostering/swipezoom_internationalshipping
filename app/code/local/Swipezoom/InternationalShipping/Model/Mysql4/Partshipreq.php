<?php

/**
 *  Swipezoom_InternationalShipping_Model_Mysql4_Partshipreq
 *
 * @author Swipezoom
 */
 
class Swipezoom_InternationalShipping_Model_Mysql4_Partshipreq extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("internationalshipping/partshipreq", "id");
    }
}