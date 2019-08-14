<?php

/**
 *  Swipezoom_InternationalShipping_Adminhtml_Sales_Order_InvoiceController
 *
 * @author Swipezoom
 */

require_once "Mage/Adminhtml/controllers/Sales/Order/InvoiceController.php";  
class Swipezoom_InternationalShipping_Adminhtml_Sales_Order_InvoiceController extends Mage_Adminhtml_Sales_Order_InvoiceController{
	
	/**
     * Shipment 
     */
    public function doshipmentAction() 
    {
		
		Mage::getModel('internationalshipping/observer')->salesordershipmentnew1();
		$orderId = Mage::app()->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
		
		$swipeorderId = Mage::app()->getRequest()->getParam('sorid');
		$count = Mage::getModel('internationalshipping/shipmentdetail')->getCollection()->addFieldToFilter('swipezoomorderid',$swipeorderId);
		if(count($count)){
			
		foreach($count as $oid){
				$carrier = $oid->getCouriername();
				$trackInfo = $oid->getCourierwaybillno();
				$carrier_code = $oid->getCourierservicename();
				$shiptime = $oid->getPickupdue();
		}
		
		//create shipment
		/*$itemQty =  $order->getItemsCollection()->count();
		$shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($itemQty);
		$shipment = new Mage_Sales_Model_Order_Shipment_Api();
		$shipmentId = $shipment->create( $order->getIncrementId(), array(), 'Shipment created through SwipeZoom on '.$shiptime, true, true);*/
		$savedQtys = $this->_getItemQtys();
        $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($savedQtys);
        if (!$shipment->getTotalQty()) {
            Mage::getSingleton('core/session')->addError('items for order '.$orderId.' no found');
        }


        $shipment->register();
		
	    $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($order);
               if ($shipment) {
                        $transactionSave->addObject($shipment);
                }
                $transactionSave->save();
		
		//add tracking info
		$shipment_collection = Mage::getResourceModel('sales/order_shipment_collection');
		$shipment_collection->addAttributeToFilter('order_id', $orderId);
		foreach($shipment_collection as $sc)
		{
		$shipment = Mage::getModel('sales/order_shipment');
		$shipment->load($sc->getId());
										if($shipment->getId() != '')
										{ 
										try
										{
											 Mage::getModel('sales/order_shipment_track')
											 ->setShipment($shipment)
											 ->setData('title', $carrier)
											 ->setData('number', $trackInfo)
											 ->setData('carrier_code', $carrier_code )
											 ->setData('order_id', $shipment->getData('order_id'))
											 ->save();
											Mage::getSingleton('core/session')->addSuccess('Order Shipped successfully.');
										}catch (Exception $e)
										{
											Mage::getSingleton('core/session')->addError('order id '.$orderId.' no found');
										}
										}
								}
		// change order status to complete
								$order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE);
								$order->setData('state', Mage_Sales_Model_Order::STATE_COMPLETE);
								$order->save();
								
								
								
								
								
		}
		$this->_redirect('adminhtml/sales_order/view', array('order_id'=>$this->getRequest()->getParam('order_id')));
    }


	public function getCallerArray(){
        $merchantRefNo = "123";
          
        $callerObj = array("MerchantID" => Mage::getStoreConfig('carriers/swipezoom/merchantid',Mage::app()->getStore()),
        "MerchantKey" => Mage::getStoreConfig('carriers/swipezoom/merchantkey',Mage::app()->getStore()),
        "Version"=> "SW0101", 
        "Datetime"      => date("Y-m-d h:i:s"),
        "MerchantRefNo"      => $merchantRefNo); 
        return $callerObj; 
    }
	
	public function submitinvoice(Varien_Event_Observer $observer)
	{
			$orderid = Mage::app()->getRequest()->getParam('order_id');
			$order= Mage::getModel('sales/order')->load($orderid);
			$swipwzoomorder = $order->getSwipezoomOrderNumberTemp();
			if($swipwzoomorder){
			$count = Mage::getModel('internationalshipping/packingdetail')->getCollection()->addFieldToFilter('swipezoomorderid',$swipwzoomorder);
			if(!count($count)){
			try {
					$swipwzoomorder = $order->getSwipezoomOrderNumberTemp();
					$client = Mage::helper('internationalshipping')->_createSoapClient();
					$params = array("Caller" => $this->getCallerArray(),"OrderNo" =>$swipwzoomorder );

    			    $response = $client->Pack($params);
					$statuscheck=$response->ResponseStatusCode;
					$ResponseStatusDesc=$response->ResponseStatusDesc;
					$message = array();
					if($statuscheck=="000"){
							$chk = json_decode(json_encode($response), true);
					
							$boxlist = $chk['PackingBoxes']['PackingBox'];
							if(is_array($response->PackingBoxes->PackingBox)){
								foreach($response->PackingBoxes->PackingBox as $box){
								
									$boxno = $box->BoxNo;
									$boxcode = $box->BoxCode;
									if(is_array($box->ProductLineItems->ProductLineItem)){
										foreach($box->ProductLineItems->ProductLineItem as $lineitems){
										
											$productcode = $lineitems->ProductCode;
											$quantity = $lineitems->Quantity;
											//$products = Mage::getModel('catalog/product') ;
											$newpack = Mage::getModel('internationalshipping/packingdetail')->setId()->setSwipezoomorderid($swipwzoomorder)->setBoxno($boxno)->setBoxcode($boxcode)->setProductcode($productcode)->setProductqty($quantity)->setProductname("Product")->setMagentoorderid(Mage::app()->getRequest()->getParam('order_id'))->save();
											
											
										}
									}else{
										$productcode = $box->ProductLineItems->ProductLineItem->ProductCode;
										$quantity = $box->ProductLineItems->ProductLineItem->Quantity;
										$newpack = Mage::getModel('internationalshipping/packingdetail')->setId()->setSwipezoomorderid($swipwzoomorder)->setBoxno($boxno)->setBoxcode($boxcode)->setProductcode($productcode)->setProductqty($quantity)->setProductname("Product")->setMagentoorderid(Mage::app()->getRequest()->getParam('order_id'))->save();
									}
								}
								$message['error'] = false;
								$message['message'] = 'The invoice has been created.';
							}else{
								
									
									$boxno = $response->PackingBoxes->PackingBox->BoxNo;
									$boxcode = $response->PackingBoxes->PackingBox->BoxCode;
									
									if(is_array( $response->PackingBoxes->PackingBox->ProductLineItems->ProductLineItem)){
										foreach($response->PackingBoxes->PackingBox->ProductLineItems->ProductLineItem as $lineitems){
											$productcode = $lineitems->ProductCode;
											$quantity = $lineitems->Quantity;
											//$products = Mage::getModel('catalog/product') ;
											$newpack = Mage::getModel('internationalshipping/packingdetail')->setId()->setSwipezoomorderid($swipwzoomorder)->setBoxno($boxno)->setBoxcode($boxcode)->setProductcode($productcode)->setProductqty($quantity)->setProductname("Product")->setMagentoorderid(Mage::app()->getRequest()->getParam('order_id'))->save();
										}
									}else{
									$productcode = $response->PackingBoxes->PackingBox->ProductLineItems->ProductLineItem->ProductCode;
									$quantity = $response->PackingBoxes->PackingBox->ProductLineItems->ProductLineItem->Quantity;
									$newpack = Mage::getModel('internationalshipping/packingdetail')->setId()->setSwipezoomorderid($swipwzoomorder)->setBoxno($boxno)->setBoxcode($boxcode)->setProductcode($productcode)->setProductqty($quantity)->setProductname("Product")->setMagentoorderid(Mage::app()->getRequest()->getParam('order_id'))->save();
									}
									$message['error'] = false;
									$message['message'] = 'The invoice has been created.';
							}
							
							return $message;
					}
					else{
						Mage::helper('internationalshipping')->sendServiceFailureAlert($params,$response,'Pack');
						$debugmode =  Mage::getStoreConfig('carriers/swipezoom/debug',Mage::app()->getStore());
						$message['error'] = true;
						if($debugmode){
						$message['message'] = Mage::getSingleton('core/session')->addError(Mage::helper('core')->__($statuscheck.' : '.$ResponseStatusDesc));
						}else{
						$message['message'] = Mage::getSingleton('core/session')->addError(Mage::helper('core')->__('Unable to perform Invoice action (Swipezoom Pack service)'));
						}
						return $message;
					}
					
				} catch (Exception $e) {
						$debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
						Mage::log($debugData,null,'debug.log');
						Mage::logException($e);
				}
			}
		}
	}

    /**
     * Save invoice
     * We can save only new invoice. Existing invoices are not editable
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost('invoice');
	    $orderId = $this->getRequest()->getParam('order_id');

		$order= Mage::getModel('sales/order')->load($orderId);
		$swipwzoomorder = $order->getSwipezoomOrderNumberTemp();
		if($swipwzoomorder){
			$response = $this->submitinvoice();
			$count = Mage::getModel('internationalshipping/packingdetail')->getCollection()->addFieldToFilter('swipezoomorderid',$swipwzoomorder);
			if(!count($count)){
				$this->_redirect('adminhtml/sales_order_invoice/new', array('order_id' => $orderId));
				return;
			}
		}
		
		
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try {
            $invoice = $this->_initInvoice();
            if ($invoice) {

                if (!empty($data['capture_case'])) {
                    $invoice->setRequestedCaptureCase($data['capture_case']);
                }

                if (!empty($data['comment_text'])) {
                    $invoice->addComment(
                        $data['comment_text'],
                        isset($data['comment_customer_notify']),
                        isset($data['is_visible_on_front'])
                    );
                }

                $invoice->register();

                if (!empty($data['send_email'])) {
                    $invoice->setEmailSent(true);
                }
				if($swipwzoomorder && empty($data['send_email'])){
					$invoice->setEmailSent(true);
				}
                $invoice->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                $invoice->getOrder()->setIsInProcess(true);

                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $shipment = false;
                if (!empty($data['do_shipment']) || (int) $invoice->getOrder()->getForcedDoShipmentWithInvoice()) {
                    $shipment = $this->_prepareShipment($invoice);
                    if ($shipment) {
                        $shipment->setEmailSent($invoice->getEmailSent());
                        $transactionSave->addObject($shipment);
                    }
                }
                $transactionSave->save();

                if (isset($shippingResponse) && $shippingResponse->hasErrors()) {
                    $this->_getSession()->addError($this->__('The invoice and the shipment  have been created. The shipping label cannot be created at the moment.'));
                } elseif (!empty($data['do_shipment'])) {
                    $this->_getSession()->addSuccess($this->__('The invoice and shipment have been created.'));
                } else {
                    $this->_getSession()->addSuccess($this->__('The invoice has been created.'));
                }

                // send invoice/shipment emails
                $comment = '';
                if (isset($data['comment_customer_notify'])) {
                    $comment = $data['comment_text'];
                }
				
				if($swipwzoomorder && empty($data['send_email'])){
					$sendmail = false;
				}else{
					$sendmail = empty($data['send_email']);
				}
                try {
					if(!$swipwzoomorder )
                    { $invoice->sendEmail(!$sendmail, $comment); }else{
								 $invoice->setEmailSent(true);
								}
                } catch (Exception $e) {
                    Mage::logException($e);
                    $this->_getSession()->addError($this->__('Unable to send the invoice email.'));
                }
                if ($shipment) {
                    try {
                        $shipment->sendEmail(!empty($data['send_email']));
                    } catch (Exception $e) {
                        Mage::logException($e);
                        $this->_getSession()->addError($this->__('Unable to send the shipment email.'));
                    }
                }
                Mage::getSingleton('adminhtml/session')->getCommentText(true);
				if($order->getSwipezoomOrderNumberTemp()){
	                $this->_redirect('adminhtml/sales_order/view', array('order_id' => $orderId,'showpopup'=> '1'));
				}else{
					$this->_redirect('adminhtml/sales_order/view', array('order_id' => $orderId));
				}
            } else {
                $this->_redirect('*/*/new', array('order_id' => $orderId));
            }
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Unable to save the invoice.'));
            Mage::logException($e);
        }
        $this->_redirect('*/*/new', array('order_id' => $orderId));
    }


}


				
