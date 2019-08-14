<?php

/**
 *  Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Order_View
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{
	
	public function __construct()
    {
        
        parent::__construct();
		$order = $this->getOrder();
		
		if ($swipwzoomorder = $order->getSwipezoomOrderNumberTemp() && $order->hasInvoices() && $order->getSwipezoomOrderCreditmemo()<3) {
            $this->_addButton('print', array(
                'label'     => Mage::helper('sales')->__('Print Packing Instruction'),
                'class'     => 'save',
                'onclick'   => 'setLocation(\''.$this->getPrintUrl($order).'\')'
                )
            );
        }
		if ($swipwzoomorder = $order->getSwipezoomOrderNumberTemp()) {
			// $this->_removeButton('order_edit');
		}
		
	
		
		if ($swipwzoomorder = $order->getSwipezoomOrderNumberTemp() && $order->hasShipments()) {
            $this->_addButton('printdocumetns', array(
                'label'     => Mage::helper('sales')->__('Print All Documents'),
                'class'     => 'save',
                'onclick'   => 'openMyPopup(\''.$this->getPrintallUrl($order).'\', 300,650)'
                )
            );
        }
		
		/*if ($swipwzoomorder = $order->getSwipezoomOrderNumberTemp() && !$order->hasShipments()) {
			$this->_removeButton('order_cancel');
            $message = Mage::helper('sales')->__('Are you sure you want to cancel this order?');
            $this->_addButton('ordercancel', array(
                'label'     => Mage::helper('sales')->__('Cancel'),
                'onclick'   => 'deleteConfirm(\''.$message.'\', \'' . $this->getCancelUrl() . '\')',
            ));
        }*/
		 
		if($order->getSwipezoomOrderCreditmemo() ==2){
			$this->_removeButton('order_creditmemo');
		}
		if($order->getSwipezoomOrderNumberTemp()){
			$this->_removeButton('send_notification');
			$this->_removeButton('order_reorder');
		}
		
		
    }
	
	public function getPrintUrl($order)
    {
		
		$invoicid =  Mage::getResourceModel('sales/order_invoice_collection')
                ->setOrderFilter($order->getId());

            if ($order->getId()) {
                foreach ($invoicid as $invoice) {
                    $invoice->setOrder($order);
                    $invoice_id = $invoice->getId();
                }
            }
		
        return $this->getUrl('adminhtml/sales_order_invoice/print', array(
            'invoice_id' => $invoice_id
        ));
    }
	
	public function getPrintallUrl($order)
    {
        return $this->getUrl('internationalshipping/index/printall', array(
            'order_id' => $order->getId(),
			'szoomorder' => $order->getSwipezoomOrderNumberTemp()
        ));
    }

}
			
