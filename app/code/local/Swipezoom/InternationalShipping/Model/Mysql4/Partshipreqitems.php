<?php

/**
 *  Swipezoom_InternationalShipping_Model_Mysql4_Partshipreqitems
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Model_Mysql4_Partshipreqitems extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("internationalshipping/partshipreqitems", "id");
    }
}