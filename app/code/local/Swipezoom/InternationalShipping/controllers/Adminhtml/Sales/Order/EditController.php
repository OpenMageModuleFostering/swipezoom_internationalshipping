<?php

/**
 *  Swipezoom_InternationalShipping_Adminhtml_Sales_Order_EditController
 *
 * @author Swipezoom
 */
 
require_once('CreateController.php');
class Swipezoom_InternationalShipping_Adminhtml_Sales_Order_EditController extends Mage_Adminhtml_Sales_Order_CreateController{
	public function startAction()
    {
        $this->_getSession()->clear();
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);

        try {
            if ($order->getId()) {
                $this->_getSession()->setUseOldShippingMethod(true);
                $this->_getOrderCreateModel()->initFromOrder($order);
                $this->_redirect('*/*');
            }
            else {
                $this->_redirect('*/sales_order/');
            }
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/sales_order/view', array('order_id' => $orderId));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addException($e, $e->getMessage());
            $this->_redirect('*/sales_order/view', array('order_id' => $orderId));
        }
    }
	
	public function createnewAction()
    {
		$orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
		
		$colleciotn = Mage::getModel('internationalshipping/partshipreq')->getCollection()->addFieldToFilter('realorderid',$orderId);
		if(count($colleciotn)){
				foreach($colleciotn as $getneworder){
					$neworderid = $getneworder->getNewszorderid();
					$shippingcharge = ($getneworder->getCouriercharges() + $getneworder->getInsurancecharges() + $getneworder->getCourierduties())*$getneworder->getExcrate();
					$getCouriercharges= $getneworder->getCouriercharges()*$getneworder->getExcrate();
					$getInsurancecharges = $getneworder->getInsurancecharges()*$getneworder->getExcrate();
					$getCourierduties = $getneworder->getCourierduties()*$getneworder->getExcrate();
					break;
				}
								
								try{
								$client = Mage::helper('internationalshipping')->_createSoapClient();
								$params = array("Caller" => $this->getCallerArray($this->getRequest()->getPost('refno')),"OrderNo"=>$neworderid,"MerchantRefNo"=>$this->getRequest()->getPost('refno'));
								Mage::log($params,null,'PartShipConfirm'.$orderId.'.log');
								$response = $client->PartShipConfirm($params);
								Mage::log($response,null,'PartShipConfirm'.$orderId.'.log');
								
								if($response->ResponseStatusCode=="000"){
									
								$colleciotnitems = Mage::getModel('internationalshipping/partshipreqitems')->getCollection()->addFieldToFilter('swipezoomorderid',$orderId);
								
								$itemaray  = array();
								foreach($colleciotnitems as $items){
									$itemaray[] = array('ProductCode' => $items->getProductcode(), 'Quantity' => $items->getQty());
								}
								
								
								$ordermodel =  Mage::getModel('internationalshipping/order');
								
								$newroderid1 = $ordermodel->cretejustorder($order->getCustomerId(),$itemaray,$orderId,$neworderid,$shippingcharge);
								
								$updateorder = Mage::getModel('sales/order')->load($newroderid1);
								$updateorder->setSwipezoomOrderShippingCharges($getCouriercharges)->setSwipezoomOrderDutiesTaxes($getCourierduties)
											->setSwipezoomOrderInsuranceCharges($getInsurancecharges)->setSwipezoomOrderDutiesTaxPrepaid('Y')
											->setSwipezoomOrderInsurancePaid('Y')->setSwipezoomOrderConfirmed('1')
											->save();
								
								$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
								$response = array(
										'ajaxRedirect' => $this->getUrl('adminhtml/sales_order/index' ),//, array('order_id'=>$id)
										'ajaxExpired' => true
										);
								$response = Mage::helper('core')->jsonEncode($response);
										
									
								}else{
									  Mage::helper('internationalshipping')->sendServiceFailureAlert($params,$response,'PartShipConfirm');
									  $debugmode =  Mage::getStoreConfig('carriers/swipezoom/debug',Mage::app()->getStore());
									  if($debugmode){
										  $message = $response->ResponseStatusDesc;
										  Mage::getSingleton('core/session')->addError(Mage::helper('core')->__($response->ResponseStatusCode.' : '.$message));
									  }else{
										  $message =  'Unable to perform Partship Confirm Action (Swipezoom Partship Confirm service)';	
										  Mage::getSingleton('core/session')->addError(Mage::helper('core')->__('Unable to perform Credti Memo Action (Swipezoom Partship Confirm service)'));
									  }
									  
										
											
									  $response = array(
										'error'     => true, 
										'message'   => $message,
										'ajaxRedirect' => $this->getUrl('*/*'),
										'ajaxExpired' => true
										);
									  $response = Mage::helper('core')->jsonEncode($response);
								}
			
								}catch (Mage_Core_Exception $e) {
									 $response = array(
											'error'     => true,
											'message'   => $e->getMessage(),
											'ajaxRedirect' => $this->getUrl('*/*'),
											'ajaxExpired' => true
										);
										Mage::getSingleton('core/session')->addError($e->getMessage());
										$response = Mage::helper('core')->jsonEncode($response);
								}
		}else{
			Mage::getSingleton('adminhtml/session')->addError("Partship Request Doesn't Exists");
			 $response = array(
                'error'     => true,
                'message'   => 'Partship Request Doesn\'t Exists',
				'ajaxRedirect' => $this->getUrl('*/*'),
				'ajaxExpired' => true
            );
		
            $response = Mage::helper('core')->jsonEncode($response);
		}
		
		$this->getResponse()->setBody($response);
		
		//Mage::getModel('internationalShipping/order')->creteorder($order->getCustomerId())
	}
	/**
     * Loading page block
     */
   
   
     public function sendpartshiprqAction()
    {
		
		
		try {	
				if ($this->getRequest()->getPost('szorderid') && $this->getRequest()->getPost('sendrequest')) {
				Mage::register('szorderid', $this->getRequest()->getPost('szorderid'));
				Mage::getSingleton('adminhtml/session_quote')->setSwipezoomOrderNumber($this->getRequest()->getPost('szorderid'));
				
				$ordercolection  = Mage::getModel('sales/order')->getCollection()->addAttributeToSelect('*')->addFieldToFilter('swipezoom_order_number_temp',$this->getRequest()->getPost('szorderid'));
				foreach($ordercolection as $orders){
					$realorderid = $orders->getId();break;	
				}
				$updateditem = array();
				
				$realorderload  = Mage::getModel('sales/order')->load($realorderid);
				$ordered_items = $realorderload->getAllItems();
				$realitemqty = array();
				foreach($ordered_items as $item2){
						$realitemqty[$item2->getSku()] = $item2->getQtyOrdered();
				}
				
				foreach($this->getRequest()->getPost('item',false) as $iditem => $item){
					if(!$item['action']){
					$realitemqty[$item['sku']] = $realitemqty[$item['sku']] - $item['qty'];
					}else{
					$realitemqty[$item['sku']] = $realitemqty[$item['sku']];
					}
				}
				
				foreach($realitemqty as $sku => $itemqty){
						if($itemqty>0){
						$updateditem[] = array('ProductCode'=>$sku,'Quantity'=>(int)($itemqty));
						}
					
				}
				//Mage::getModel('internationalshipping/observer')->editordersave($updateditem,$this->getRequest()->getPost('szorderid'),$realorderid);
				
				 
				$proitems = $updateditem;
				$swipwzoomorder = $this->getRequest()->getPost('szorderid');
				$realorderid = $realorderid;
				
								$client = Mage::helper('internationalshipping')->_createSoapClient();
								$params = array("Caller" => $this->getCallerArray(),"OrderNo" =>$swipwzoomorder,"ProductLineItems"=>$proitems );
								Mage::log($params,null,'PartShipReq'.$swipwzoomorder.'.log');
								$response = $client->PartShipReq($params);
								Mage::log($response,null,'PartShipReq'.$swipwzoomorder.'.log');
								
						if($response->ResponseStatusCode == '000')
								{
									
								$OrderNo = $response->Order->OrderNo;
								$OrderNoRef = $response->Order->TransDetail->OrderNoRef;
								$ProductPrice = $response->Order->Merchant->ProductPrice;
								$ProductPriceCurrency = $response->Order->Merchant->ProductPriceCurrency;
								$CustCurrency = $response->Order->TransDetail->CustCurrency;
								$CustExcRate = $response->Order->TransDetail->CustExcRate;
								$CustTotalValue = $response->Order->TransDetail->CustTotalValue;
								$CourierCharges = $response->Order->TransDetail->CourierCharges;
								$CourierDuties = $response->Order->TransDetail->CourierDuties;
								$InsuranceCharges = $response->Order->TransDetail->InsuranceCharges;
								Mage::log($CourierDuties,null,'1PartShipReq'.$swipwzoomorder.'.log');
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
														->setCourierduties($CourierDuties)
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
								
								
								
								
								
										$response = array(
											'ajaxRedirect' => $this->getUrl('*/*' ),//, array('order_id'=>$id)
											'ajaxExpired' => true
											);
										$response = Mage::helper('core')->jsonEncode($response);
								}else{
							 Mage::helper('internationalshipping')->sendServiceFailureAlert($params,$response,'PartShipReq');										
							 $debugmode =  Mage::getStoreConfig('carriers/swipezoom/debug',Mage::app()->getStore());
							  if($debugmode){
								  $message = $response->ResponseStatusDesc;
								  Mage::getSingleton('core/session')->addError(Mage::helper('core')->__($response->ResponseStatusCode.' : '.$message));
							  }else{
								  $message =  'Unable to perform Partship Request Action (Swipezoom Partship Request Service)';	
								  Mage::getSingleton('core/session')->addError(Mage::helper('core')->__('Unable to perform Credti Memo Action (Swipezoom Refund Request service)'));
							  }	
						
										$response = array(
										'error'     => true, 
										'message'   => $message,
										'ajaxRedirect' => $this->getUrl('*/*'),
										'ajaxExpired' => true
										);
									  $response = Mage::helper('core')->jsonEncode($response);						}
								
						
				
				
				
			}
			
			} catch (Mage_Core_Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage(),
				'ajaxRedirect' => $this->getUrl('*/*'),
				'ajaxExpired' => true
            );
			Mage::getSingleton('core/session')->addError($e->getMessage());
            $response = Mage::helper('core')->jsonEncode($response);
        } 
        $this->getResponse()->setBody($response);
		
	}
    public function loadBlockAction()
    {
		
		$request = $this->getRequest();
        try {
            $this->_initSession()
                ->_processData();
			
        }
        catch (Mage_Core_Exception $e){
            $this->_reloadQuote();
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e){
            $this->_reloadQuote();
            $this->_getSession()->addException($e, $e->getMessage());
        }

		
		
        $asJson= $request->getParam('json');
        $block = $request->getParam('block');

        $update = $this->getLayout()->getUpdate();
        if ($asJson) {
            $update->addHandle('adminhtml_sales_order_create_load_block_json');
        } else {
            $update->addHandle('adminhtml_sales_order_create_load_block_plain');
        }

        if ($block) {
            $blocks = explode(',', $block);
            if ($asJson && !in_array('message', $blocks)) {
                $blocks[] = 'message';
            }

            foreach ($blocks as $block) {
                $update->addHandle('adminhtml_sales_order_create_load_block_' . $block);
            }
        }
        $this->loadLayoutUpdates()->generateLayoutXml()->generateLayoutBlocks();
        $result = $this->getLayout()->getBlock('content')->toHtml();
        if ($request->getParam('as_js_varname')) {
            Mage::getSingleton('adminhtml/session')->setUpdateResult($result);
            $this->_redirect('*/*/showUpdateResult');
        } else {
            $this->getResponse()->setBody($result);
        }
    }

    /**
     * Index page
     */
    public function indexAction()
    {
        $this->_title($this->__('Sales'))->_title($this->__('Orders'))->_title($this->__('Edit Order'));
        $this->loadLayout();

        $this->_initSession()
            ->_setActiveMenu('sales/order')
            ->renderLayout();
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/edit');
    }
	public function getCallerArray($merchantRefNo){
        if(!$merchantRefNo){
		$merchantRefNo = "123";
		}
        $callerObj = array("MerchantID" => Mage::getStoreConfig('carriers/swipezoom/merchantid',Mage::app()->getStore()),
        "MerchantKey" => Mage::getStoreConfig('carriers/swipezoom/merchantkey',Mage::app()->getStore()),
        "Version"=> "SW0101", 
        "Datetime"      => date("Y-m-d h:i:s"),
        "MerchantRefNo"      => $merchantRefNo); 
        return $callerObj; 
    }
	
}
				
