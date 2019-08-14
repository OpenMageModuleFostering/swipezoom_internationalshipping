<?php

/**
 *	Swipezoom_CardPayment_Block_Form_Ccsave	
 *
 * @author Swipezoom
 */

class Swipezoom_CardPayment_Block_Form_Ccsave extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('cardpayment/ccsave.phtml');
    }
}