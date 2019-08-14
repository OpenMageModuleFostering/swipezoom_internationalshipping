<?php

/**
 *  Swipezoom_InternationalShipping_Model_Observer
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Model_Observer
{			
	 public function salesOrderGridCollectionLoadBefore($observer)
    {
        $collection = $observer->getOrderGridCollection();
        $select = $collection->getSelect();
        $select->joinLeft(array('swipezoom' => $collection->getTable('sales/order')), 'swipezoom.entity_id=main_table.entity_id', array('swipezoom_order_number_temp' => 'swipezoom_order_number_temp'));
    }

	
    public function saveSZorderNumber($observer){
        
        Mage::getSingleton('core/session')->setRequestedString("");
        $order = $observer->getEvent()->getOrder();
        $shippingMethod = $order->getData("shipping_method") ;
        $temp1 = $order->getSwipezoomOrderConfirmed();
        if(strpos($shippingMethod,"swipezoom") !== FALSE && !$temp = $order->getSwipezoomOrderNumberTemp() && Mage::getSingleton('checkout/session')->getQuote()->getSwipezoomOrderNumber() && Mage::app()->getRequest()->getActionName() == "saveOrder" && !empty($temp1) ){
            $extraRates = Mage::getSingleton('checkout/session')->getSwipezoomExtraRates();

            $order->setSwipezoomOrderNumberTemp(Mage::getSingleton('checkout/session')->getQuote()->getSwipezoomOrderNumber());
            $order->setSwipezoomOrderShippingCharges($extraRates["shipping"]);
            $order->setSwipezoomOrderDutiesTaxes($extraRates["CustCourierDuties"]);
            $order->setSwipezoomOrderInsuranceCharges($extraRates["CustInsuranceCharges"]);
            (strpos($shippingMethod,"withduties") !== FALSE)? $order->setSwipezoomOrderDutiesTaxPrepaid("Y"): $order->setSwipezoomOrderDutiesTaxPrepaid("N");
            (strpos($shippingMethod,"withinsurance") !== FALSE)? $order->setSwipezoomOrderInsurancePaid("Y"): $order->setSwipezoomOrderInsurancePaid("N");
			
			$order->setEmailSent(true);
        }
    }
	
	public function orderplaced(Varien_Event_Observer $observer){
		 $order = $observer->getEvent()->getOrder();
		 $quote = $observer->getEvent()->getQuote();
        $shippingMethod = $order->getData("shipping_method") ;
        if(strpos($shippingMethod,"swipezoom") !== FALSE && $quote->getSwipezoomOrderNumber()){
            $extraRates = Mage::getSingleton('core/session')->getSwipezoomExtraRates();
            $order->setSwipezoomOrderNumberTemp($quote->getSwipezoomOrderNumber());
            $order->setSwipezoomOrderShippingCharges($extraRates["shipping"]);
            $order->setSwipezoomOrderDutiesTaxes($extraRates["CustCourierDuties"]);
            $order->setSwipezoomOrderInsuranceCharges($extraRates["CustInsuranceCharges"]);
            (strpos($shippingMethod,"withduties") !== FALSE)? $order->setSwipezoomOrderDutiesTaxPrepaid("Y"): $order->setSwipezoomOrderDutiesTaxPrepaid("N");
            (strpos($shippingMethod,"withinsurance") !== FALSE)? $order->setSwipezoomOrderInsurancePaid("Y"): $order->setSwipezoomOrderInsurancePaid("N");
			
			$order->setEmailSent(true);
        }
	}
	
	public function modifySubtotal(Varien_Event_Observer $observer)
	{	
		$quote=$observer->getEvent()->getQuote();
		
		$fullActionName = Mage::app()->getFrontController()->getAction()->getFullActionName();
		$currentShip = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingMethod();

		if ('checkout_cart_index' != $fullActionName && strpos($currentShip,"swipezoom") !== FALSE) {
		    $extraRates = Mage::getSingleton('core/session')->getSwipezoomExtraRates();
			   $extraRates['CustItemSubtotal'] = floatval($extraRates['CustItemSubtotal']);
			   $extraRates['CustTotalTransAmount'] = floatval($extraRates['CustTotalTransAmount']);
			   $extraRates["CustCourierDuties"] = floatval($extraRates["CustCourierDuties"]);
			   $extraRates["CustInsuranceCharges"] = floatval($extraRates["CustInsuranceCharges"]);
			   
		       $quote=$observer->getEvent()->getQuote();
		       $quoteid=$quote->getId();
			   //check condition here if need to apply Discount
		      
		     if($quoteid) {
		        if(!empty($extraRates['CustItemSubtotal'])) {
		           $total=$quote->getBaseSubtotal();
		           $quote->setSubtotal(0);
		           $quote->setBaseSubtotal(0);

		           $quote->setSubtotalWithDiscount(0);
		           $quote->setBaseSubtotalWithDiscount(0);

		           $quote->setGrandTotal(0);
		           $quote->setBaseGrandTotal(0);

		           $canAddItems = $quote->isVirtual() ? ('billing') : ('shipping'); 

		           foreach ($quote->getAllAddresses() as $address) {

		                    $address->setSubtotal(0);
		                    $address->setBaseSubtotal(0);

		                    $address->setGrandTotal(0);
		                    $address->setBaseGrandTotal(0);

		                    $address->collectTotals();

		                    if($address->getAddressType()==$canAddItems) {
			                    $address->setSubtotal($extraRates['CustItemSubtotal']);
			                    $address->setBaseSubtotal($extraRates['CustItemSubtotal']);

			                    (strpos($currentShip,"noduties") !== FALSE)?$PrepaidDuties=$extraRates["CustCourierDuties"]:$PrepaidDuties=0;
								(strpos($currentShip,"noinsurance") !== FALSE)?$PrepaidInsurance=$extraRates["CustInsuranceCharges"]:$PrepaidInsurance=0;
								$address->setGrandTotal($extraRates['CustTotalTransAmount'] - $PrepaidDuties - $PrepaidInsurance);
			                    $address->setBaseGrandTotal($extraRates['CustTotalTransAmount'] - $PrepaidDuties - $PrepaidInsurance);
		                	}

		                    $quote->setSubtotal((float) $quote->getSubtotal() + $address->getSubtotal());
		                    $quote->setBaseSubtotal((float) $quote->getBaseSubtotal() + $address->getBaseSubtotal());

		                    $quote->setSubtotalWithDiscount(
		                        (float) $quote->getSubtotalWithDiscount() + $address->getSubtotalWithDiscount()
		                    );
		                    $quote->setBaseSubtotalWithDiscount(
		                        (float) $quote->getBaseSubtotalWithDiscount() + $address->getBaseSubtotalWithDiscount()
		                    );

		                    $quote->setGrandTotal((float) $quote->getGrandTotal() + $address->getGrandTotal());
		                    $quote->setBaseGrandTotal((float) $quote->getBaseGrandTotal() + $address->getBaseGrandTotal());

		           	  $quote ->save(); 

		              $quote->setGrandTotal($quote->getBaseSubtotal()-$discountAmount)
		              ->setBaseGrandTotal($quote->getBaseSubtotal()-$discountAmount)
		              ->setSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
		              ->setBaseSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
		              ->save(); 

		           } //end: foreach

			       }
			    }
		} else {

			$quote=$observer->getEvent()->getQuote();
			$quoteid=$quote->getId();
			if($quoteid) {
		           $total=$quote->getBaseSubtotal();
		           $quote->setSubtotal(0);
		           $quote->setBaseSubtotal(0);

		           $quote->setSubtotalWithDiscount(0);
		           $quote->setBaseSubtotalWithDiscount(0);

		           $quote->setGrandTotal(0);
		           $quote->setBaseGrandTotal(0);

		           $canAddItems = $quote->isVirtual()? ('billing') : ('shipping'); 

		           foreach ($quote->getAllAddresses() as $address) {

		                    $address->setSubtotal(0);
		                    $address->setBaseSubtotal(0);

		                    $address->setGrandTotal(0);
		                    $address->setBaseGrandTotal(0);

		                    $address->collectTotals();

		                    $quote->setSubtotal((float) $quote->getSubtotal() + $address->getSubtotal());
		                    $quote->setBaseSubtotal((float) $quote->getBaseSubtotal() + $address->getBaseSubtotal());

		                    $quote->setSubtotalWithDiscount(
		                        (float) $quote->getSubtotalWithDiscount() + $address->getSubtotalWithDiscount()
		                    );
		                    $quote->setBaseSubtotalWithDiscount(
		                        (float) $quote->getBaseSubtotalWithDiscount() + $address->getBaseSubtotalWithDiscount()
		                    );

		                    $quote->setGrandTotal((float) $quote->getGrandTotal() + $address->getGrandTotal());
		                    $quote->setBaseGrandTotal((float) $quote->getBaseGrandTotal() + $address->getBaseGrandTotal());

		           	  $quote ->save(); 

		              $quote->setGrandTotal($quote->getBaseSubtotal()-$discountAmount)
		              ->setBaseGrandTotal($quote->getBaseSubtotal()-$discountAmount)
		              ->setSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
		              ->setBaseSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
		              ->save(); 

		           } //end: foreach

			       
			    }
		}

	}
	
    public function AddressVerifacation()
    {
    }
	
	public function editorderdeleteentry(Varien_Event_Observer $observer){
			  $order = Mage::app()->getRequest()->getParam('order_id');

			  $newcoll = Mage::getModel('internationalshipping/partshipreq')->getCollection()->addFieldToFilter('realorderid', Mage::app()->getRequest()->getParam('order_id')); 
			  foreach ($newcoll as $ccitem) {
				  $ccitem->delete();
			  } 
			  
			  
			  $newcoll1 = Mage::getModel('internationalshipping/partshipreqitems')->getCollection()
														->addFieldToFilter('swipezoomorderid',Mage::app()->getRequest()->getParam('order_id')); 
			  foreach ($newcoll1 as $ccitem) {
				  $ccitem->delete();
			  }	
			  
			  
	}
	
	public function editordersave($proitems,$swipwzoomorder,$realorderid){

				if($swipwzoomorder){
						try {
								$client = Mage::helper('internationalshipping')->_createSoapClient();
								$params = array("Caller" => $this->getCallerArray(),"OrderNo" =>$swipwzoomorder,"ProductLineItems"=>$proitems );
								Mage::log($params,null,'PartShipReq'.$swipwzoomorder.'.log');
								$response = $client->PartShipReq($params);
								Mage::log($response,null,'PartShipReq'.$swipwzoomorder.'.log');
								
								if($response->ResponseStatusCode == '000'){
									
								$OrderNo = $response->Order->OrderNo;
								$OrderNoRef = $response->Order->TransDetail->OrderNoRef;
								$ProductPrice = $response->Order->Merchant->ProductPrice;
								$ProductPriceCurrency = $response->Order->Merchant->ProductPriceCurrency;
								$CustCurrency = $response->Order->TransDetail->CustCurrency;
								$CustExcRate = $response->Order->TransDetail->CustExcRate;
								$CustTotalValue = $response->Order->TransDetail->CustTotalValue;
								$CourierCharges = $response->Order->TransDetail->CourierCharges;
								$InsuranceCharges = $response->Order->TransDetail->InsuranceCharges;
								$SZMarkup = $response->Order->TransDetail->SZMarkup;
								$newcoll = Mage::getModel('internationalshipping/partshipreq')->getCollection()->addFieldToFilter('realorderid',$realorderid); ////
								foreach ($newcoll as $ccitem) {
									$ccitem->delete();
								}
								$colection = Mage::getModel('internationalshipping/partshipreq')->getCollection()->addFieldToFilter('realorderid',$realorderid);
								
								$model = Mage::getModel('internationalshipping/partshipreq')->setId()
														->setSwipezoomorderid($OrderNoRef)
														->setNewszorderid($OrderNo)
														->setProductprice($ProductPrice)
														->setPricecurrency($ProductPriceCurrency)
														->setSalecurrency($CustCurrency)
														->setExcrate($CustExcRate)
														->setOrdertotal($CustTotalValue)
														->setCouriercharges($CourierCharges)
														->setInsurancecharges($InsuranceCharges)
														->setSzmarkup($SZMarkup)
														->setRealorderid($realorderid) //Mage::app()->getRequest()->getParam('order_id')
														->save();
								
								
								$newcoll1 = Mage::getModel('internationalshipping/partshipreqitems')->getCollection()
														->addFieldToFilter('swipezoomorderid',$realorderid); 
										
										foreach ($newcoll1 as $ccitem) {
											$ccitem->delete();
										}	
								if(is_array( $response->Order->ProductLineItems->ProductLineItem)){
									 foreach($response->Order->ProductLineItems->ProductLineItem as $proitem){
										$LineItemNo = $proitem->LineItemNo;
										$ProductCode = $proitem->ProductCode;
										$Description = $proitem->Description;
										$Price = $proitem->Price;
										$Quantity = $proitem->Quantity;
										$SaleValue = $proitem->SaleValue;
										
										
												$model = Mage::getModel('internationalshipping/partshipreqitems')->setId()
																	->setSwipezoomorderid($realorderid) 
																	->setLineitemno($LineItemNo)
																	->setProductcode($ProductCode)
																	->setDescription($Description)
																	->setPrice($Price)
																	->setQty($Quantity)
																	->setSalevalue($SaleValue)
																	->save();
											
											
									 }
								 
								
								}else{
								
										$LineItemNo = $response->Order->ProductLineItems->ProductLineItem>LineItemNo;
										$ProductCode = $response->Order->ProductLineItems->ProductLineItem->ProductCode;
										$Description = $response->Order->ProductLineItems->ProductLineItem->Description;
										$Price = $response->Order->ProductLineItems->ProductLineItem->Price;
										$Quantity = $response->Order->ProductLineItems->ProductLineItem->Quantity;
										$SaleValue = $response->Order->ProductLineItems->ProductLineItem->SaleValue;
										
												$model = Mage::getModel('internationalshipping/partshipreqitems')->setId()
																	->setSwipezoomorderid($realorderid) 
																	->setLineitemno($LineItemNo)
																	->setProductcode($ProductCode)
																	->setDescription($Description)
																	->setPrice($Price)
																	->setQty($Quantity)
																	->setSalevalue($SaleValue)
																	->save();
											
											
								}
								
								
								
								
								} else {
									Mage::helper('internationalshipping')->sendServiceFailureAlert($params,$response,'PartShipReq');
								}
								
						} catch (Exception $e) {
								$debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
								Mage::log($debugData,null,'debug.log');
								Mage::logException($e);
						}
				}
			  
		
	}	
	
	public function trackingfetch()
    {
		
		$shippingInfoModel = Mage::getModel('shipping/info')->loadByHash(Mage::app()->getRequest()->getParam('hash'));
		 foreach($shippingInfoModel->getTrackingInfo() as $shipid => $_result){
			  $shipment = Mage::getModel('sales/order_shipment');
			  $shipment->load($shipid);	
			   $shipmentorder = Mage::getModel('sales/order_shipment')->getCollection()
			  ->addFieldToFilter('increment_id', $shipid)
			  ->getFirstItem();

			  
			  foreach($_result as $trackingdata){
					  $trackingnumber = $trackingdata['number'];
					  break;
			  }
 			  $orderid  = $shipmentorder->getOrderId();
			  
			  $order = Mage::getModel('sales/order')->load($orderid);
		 	  $swipwzoomorder = $order->getSwipezoomOrderNumberTemp();
				if($swipwzoomorder){
						try {
								$client = Mage::helper('internationalshipping')->_createSoapClient();
								$params = array("Caller" => $this->getCallerArray(),"OrderNo" =>$swipwzoomorder,"TrackingNumber"=>$trackingnumber );
								Mage::log($params,null,'SW_ShipTracking'.$swipwzoomorder.'.log');
								$response = $client->Track($params);
								if($response->ResponseStatusCode != '000' ){
		                        	Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'Track');
		                        }
								Mage::log($response,null,'SW_ShipTracking'.$swipwzoomorder.'.log');
						} catch (Exception $e) {
								$debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
								Mage::log($debugData,null,'debug.log');
								Mage::logException($e);
						}
				}
			  
		 }
		
    }
	
	public function salesordershipmentnew1(Varien_Event_Observer $observer){
			$orderid = Mage::app()->getRequest()->getParam('order_id');
			$order= Mage::getModel('sales/order')->load($orderid);
			$swipwzoomorder = $order->getSwipezoomOrderNumberTemp();
			if($swipwzoomorder){
			$count = Mage::getModel('internationalshipping/shipmentdetail')->getCollection()->addFieldToFilter('swipezoomorderid',$swipwzoomorder);
			if(!count($count)){
				try {
					$swipwzoomorder = $order->getSwipezoomOrderNumberTemp();
					$client = Mage::helper('internationalshipping')->_createSoapClient();
					$params = array("Caller" => $this->getCallerArray(),"OrderNo" =>$swipwzoomorder );
					$response = $client->Ship($params);
					$statuscheck=$response->ResponseStatusCode;
					$ResponseStatusDesc=$response->ResponseStatusDesc;
					if($statuscheck=="000"){
						$CourierName = $response->ShipmentBooking->CourierName;
						$CourierServiceName = $response->ShipmentBooking->CourierServiceName;
						$CourierWaybillNo = $response->ShipmentBooking->CourierWaybillNo;
						$PickupDue = $response->ShipmentBooking->PickupDue;
						
						$ship = Mage::getModel('internationalshipping/shipmentdetail')->setId()->setSwipezoomorderid($swipwzoomorder)->setCouriername($CourierName)->setCourierservicename($CourierServiceName)->setCourierwaybillno($CourierWaybillNo)->setPickupdue($PickupDue)->save();
					}else{
						Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'Ship');
						$debugmode =  Mage::getStoreConfig('carriers/swipezoom/debug',Mage::app()->getStore());
						if($debugmode){
						Mage::getSingleton('core/session')->addError(Mage::helper('core')->__($statuscheck.' : '.$ResponseStatusDesc));
						}else{
						Mage::getSingleton('core/session')->addError(Mage::helper('core')->__('Unable to perform Shipment Swipezoom'));
						}
						return;
					}
					
					
				} catch (Exception $e) {
							$debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
							Mage::log($debugData,null,'debug.log');
							Mage::logException($e);
				}
			}
		}
		
	}
	
	
	public function cancelorder(Varien_Event_Observer $observer){
			  $orderid = Mage::app()->getRequest()->getParam('order_id');
			  $order = Mage::getModel('sales/order')->load($orderid);
		 	  $swipwzoomorder = $order->getSwipezoomOrderNumberTemp();
				if($swipwzoomorder){
						try {
								$client = Mage::helper('internationalshipping')->_createSoapClient();
								$params = array("Caller" => $this->getCallerArray(),"OrderNo" =>$swipwzoomorder,"ReasonCode"=>$trackingnumber );
								Mage::log($params,null,'SW_ShipTracking'.$swipwzoomorder.'.log');
								$response = $client->CancelOrder($params);
								if($response->ResponseStatusCode != '000' ){
		                        	Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'CancelOrder');
		                        }
								Mage::log($response,null,'SW_ShipTracking'.$swipwzoomorder.'.log');
						} catch (Exception $e) {
								$debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
								Mage::log($debugData,null,'debug.log');
								Mage::logException($e);
						}
				}
			  
		 
		
				
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
	
}
