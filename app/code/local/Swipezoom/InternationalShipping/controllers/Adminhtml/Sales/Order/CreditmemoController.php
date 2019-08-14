<?php

/**
 *  Swipezoom_InternationalShipping_Adminhtml_Sales_Order_CreditmemoController
 *
 * @author Swipezoom
 */
 
require_once "Mage/Adminhtml/controllers/Sales/Order/CreditmemoController.php";  
class Swipezoom_InternationalShipping_Adminhtml_Sales_Order_CreditmemoController extends Mage_Adminhtml_Sales_Order_CreditmemoController{
public function newAction()
    {
        if ($creditmemo = $this->_initCreditmemo()) {
            if ($creditmemo->getInvoice()) {
                $this->_title($this->__("New Memo for #%s", $creditmemo->getInvoice()->getIncrementId()));
            } else {
                $this->_title($this->__("New Memo"));
            }

            if ($comment = Mage::getSingleton('adminhtml/session')->getCommentText(true)) {
                $creditmemo->setCommentText($comment);
            }
		

            $this->loadLayout()
                ->_setActiveMenu('sales/order')
                ->renderLayout();
				
        } else {
            $this->_forward('noRoute');
        }
    }

	public function saveAction()
    {
		
        $data = $this->getRequest()->getPost('creditmemo');
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try {
			 $creditmemo = $this->_initCreditmemo();
			$orderId = $this->getRequest()->getParam('order_id');
			$order = Mage::getModel('sales/order')->load($orderId);
			if($order->getSwipezoomOrderNumberTemp()){
			if(!$this->getRequest()->getPost('reasoncode')){
				Mage::getSingleton('core/session')->addError(Mage::helper('core')->__('Please choose Refund option'));
				$this->_redirect('adminhtml/sales_order_creditmemo/new', array('order_id' => $this->getRequest()->getParam('order_id')));
				return;
			}
			$this->checkstatusavail();
           // $creditmemo = $this->_initCreditmemo();
			$orderId = $this->getRequest()->getParam('order_id');
			$order = Mage::getModel('sales/order')->load($orderId);
			$params = array("Caller" => $this->getCallerArray(),"RogOrderNo" =>$order->getSwipezoomOrderNumberTemp(),"RogRefundOption"=> $this->getRequest()->getPost('reasoncode'));
			$client = Mage::helper('internationalshipping')->_createSoapClient();
			$response = $client->RogRefundActivate($params);
			$statuscheck=$response->ResponseStatusCode;
			$ResponseStatusDesc=$response->ResponseStatusDesc;
			if($statuscheck=="000"){
						$swipezoomcreditmemo=  Mage::getModel('internationalshipping/swipezoomcreditmemo')->getCollection()->addFieldToFilter('reforderno',$this->getRequest()->getParam('order_id'));
						foreach($swipezoomcreditmemo as $singlememo){
							$parentorder = Mage::getModel('sales/order')->load($singlememo->getRealorderno());
							break;	 
						}
						
						$parentorder->addStatusHistoryComment('Refund Confirmed', 'refund_confirmed')->save();
						
			}else{
						Mage::helper('internationalshipping')->sendServiceFailureAlert($params,$response,'RogRefundActivate');
						$debugmode =  Mage::getStoreConfig('carriers/swipezoom/debug',Mage::app()->getStore());
						if($debugmode){
						Mage::getSingleton('core/session')->addError(Mage::helper('core')->__($statuscheck.' : '.$ResponseStatusDesc));
						}else{
						Mage::getSingleton('core/session')->addError(Mage::helper('core')->__('Unable to perform Credit Memo Action (Swipezoom Refund Confirm service)'));
						}
						 $this->_redirect('adminhtml/sales_order_creditmemo/new', array('order_id' => $this->getRequest()->getParam('order_id')));
						return;
			}
			
			}
			
			
            if ($creditmemo) {
                if (($creditmemo->getGrandTotal() <=0) && (!$creditmemo->getAllowZeroGrandTotal())) {
                    Mage::throwException(
                        $this->__('Credit memo\'s total must be positive.')
                    );
                }

                $comment = '';
                if (!empty($data['comment_text'])) {
                    $creditmemo->addComment(
                        $data['comment_text'],
                        isset($data['comment_customer_notify']),
                        isset($data['is_visible_on_front'])
                    );
                    if (isset($data['comment_customer_notify'])) {
                        $comment = $data['comment_text'];
                    }
                }

                if (isset($data['do_refund'])) {
                    $creditmemo->setRefundRequested(true);
                }
                if (isset($data['do_offline'])) {
                    $creditmemo->setOfflineRequested((bool)(int)$data['do_offline']);
                }

                $creditmemo->register();
                if (!empty($data['send_email'])) {
                    $creditmemo->setEmailSent(true);
                }

                $creditmemo->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                $this->_saveCreditmemo($creditmemo);
				
				if($order->getSwipezoomOrderNumberTemp() && empty($data['send_email'])){
					$sendmail = false;
				}else{
					$sendmail = empty($data['send_email']);
				}
				if(!$order->getSwipezoomOrderNumberTemp()){
                $creditmemo->sendEmail(!$sendmail, $comment);
				}else{
					 $creditmemo->setEmailSent(true);
					}
                $this->_getSession()->addSuccess($this->__('The credit memo has been created.'));
                Mage::getSingleton('adminhtml/session')->getCommentText(true);
				
				if($order->getSwipezoomOrderNumberTemp()){
						$orderId = $this->getRequest()->getParam('order_id');
						$order = Mage::getModel('sales/order')->load($orderId);
			
						$order->addStatusToHistory('refund_confirmed', 'Refund Confirmed', false);
						$order->save();
						
						$swipezoomcreditmemo=  Mage::getModel('internationalshipping/swipezoomcreditmemo')->getCollection()->addFieldToFilter('reforderno',$this->getRequest()->getParam('order_id'));
						if(count($swipezoomcreditmemo)){
							foreach($swipezoomcreditmemo as $parent){
								$orderparent = Mage::getModel('sales/order')->load($parent->getRealorderno());
								/*$un = unserialize($parent->getCreditmemoitems());
								
								$dif = array();
								foreach($un as $u){
									$dif[] = array('order_item_id' => $u['itemid'],'qty'=>$u['Quantity'],'back_to_stock'=>1);
								}
									
								if ($orderparent->hasInvoices()) {
//									$invIncrementIDs = array();
									foreach ($orderparent->getInvoiceCollection() as $inv) {
										$invIncrementIDs = $inv->getIncrementId();
										break;
									//other invoice details...
									} 
								}						
									
								$info = array("order_increment_id" => $orderparent->getIncrementId(), "invoice_id" => $invIncrementIDs,'shipping_amount'=>$creditmemo->getShippingAmount());						
								$this->createparentcredtimemo($dif, $info);*/
								$orderparent->addStatusToHistory('void', 'Void', false);
								$orderparent->save();
							}	
						}
						
				}
                $this->_redirect('*/sales_order/view', array('order_id' => $creditmemo->getOrderId()));
                return;
            } else {
                $this->_forward('noRoute');
                return;
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setFormData($data);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('Cannot save the credit memo.'));
        }
        $this->_redirect('*/*/new', array('_current' => true));
    }
	
	protected function createparentcredtimemo($dif, $info)
	{
			$qtys = array();
		
			foreach ($dif as $item) {
				if (isset($item['qty'])) {
					$qtys[$item['order_item_id']] = array("qty"=> $item['qty']);
				}
				if (isset($item['back_to_stock'])) {
					$backToStock[$item['order_item_id']] = true;
				}
			}
			if($info['shipping_amount']){
				$shipping_amount = $info['shipping_amount'];
			}else{
						$shipping_amount = 0;
			}
			$data = array(
				"items" => $qtys,
				"do_offline" => "1",
				"comment_text" => "",
				"shipping_amount" => $shipping_amount ,
				"adjustment_positive" => "0",
				"adjustment_negative" => "0",
			);
			if (!empty($data['comment_text'])) {
				Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
			}
		
			try {
				$creditmemo = $this->_initparentCreditmemo($data, $info);
				if ($creditmemo) {
					if (($creditmemo->getGrandTotal() <=0) && (!$creditmemo->getAllowZeroGrandTotal())) {
						Mage::throwException(
							$this->__('Credit memo\'s total must be positive.')
						);
					}
		
					$comment = '';
					if (!empty($data['comment_text'])) {
						$creditmemo->addComment(
							$data['comment_text'],
							isset($data['comment_customer_notify']),
							isset($data['is_visible_on_front'])
						);
						if (isset($data['comment_customer_notify'])) {
							$comment = $data['comment_text'];
						}
					}
		
					if (isset($data['do_refund'])) {
						$creditmemo->setRefundRequested(true);
					}
					if (isset($data['do_offline'])) {
						$creditmemo->setOfflineRequested((bool)(int)$data['do_offline']);
					}
		
					$creditmemo->register();
					//if (!empty($data['send_email'])) {
						$creditmemo->setEmailSent(true);
					//}
		
					$creditmemo->getOrder()->setCustomerNoteNotify(1);//!empty($data['send_email'])
					$this->_saveCreditmemo($creditmemo);
					$creditmemo->sendEmail(true, $comment); //!empty($data['send_email'])
					
					$order = $creditmemo->getOrder();
					$order->addStatusToHistory('void', 'Void', false);
					$order->save();
					
					Mage::getSingleton('adminhtml/session')->getCommentText(true);
					return;
				} else {
					//$this->_forward('noRoute');
					//return;
				}
			} catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
				Mage::getSingleton('adminhtml/session')->setFormData($data);
			} catch (Exception $e) {
				Mage::logException($e);
				$this->_getSession()->addError($this->__('Cannot save the credit memo.'));
			}
	}	
	
	protected function _initparentCreditmemo($data, $info, $update = false){
		$creditmemo = false;
		$invoice=false;
		$creditmemoId = null;//$this->getRequest()->getParam('creditmemo_id');
		$orderId = $info['order_increment_id'];//$this->getRequest()->getParam('order_id');
		$invoiceId = $data['invoice_id'];
		//echo "<br>abans if. OrderId: ".$orderId;
		if ($creditmemoId) {
			$creditmemo = Mage::getModel('sales/order_creditmemo')->load($creditmemoId);
		} elseif ($orderId) {
			$order  = Mage::getModel('sales/order')->loadByIncrementId($orderId);
			if ($invoiceId) {
				$invoice = Mage::getModel('sales/order_invoice')
					->load($invoiceId)
					->setOrder($order);
				//echo '<br>loaded_invoice_number: '.$invoice->getId();
			}
	
			 if (!$order->canCreditmemo()) {
				//echo '<br>cannot create credit memo'; 
				if(!$order->isPaymentReview())
				{
				//	echo '<br>cannot credit memo Payment is in review'; 
				Mage::getModel('core/session')->addError($this->__('cannot credit memo Payment is in review'));
				}
				if(!$order->canUnhold())
				{
					Mage::getModel('core/session')->addError($this->__('cannot credit memo Order is on hold'));
				//	echo '<br>cannot credit memo Order is on hold'; 
				}
				if(abs($order->getTotalPaid()-$order->getTotalRefunded())<.0001)
				{
					Mage::getModel('core/session')->addError($this->__('cannot credit memo Amount Paid is equal or less than amount refunded'));
					//echo '<br>cannot credit memo Amount Paid is equal or less than amount refunded'; 
				}
				if($order->getActionFlag('edit') === false)
				{
					Mage::getModel('core/session')->addError($this->__('cannot credit memo Action Flag of Edit not set'));
					//echo '<br>cannot credit memo Action Flag of Edit not set'; 
				}
				if ($order->hasForcedCanCreditmemo()) {
					Mage::getModel('core/session')->addError($this->__('cannot credit memo Can Credit Memo has been forced set'));
					//echo '<br>cannot credit memo Can Credit Memo has been forced set'; 
				}
				return false;
			}
	
			$savedData = array();
			if (isset($data['items'])) {
				$savedData = $data['items'];
			} else {
				$savedData = array();
			}
	
			$qtys = array();
			$backToStock = array();
			foreach ($savedData as $orderItemId =>$itemData) {
				if (isset($itemData['qty'])) {
					$qtys[$orderItemId] = $itemData['qty'];
				}
				if (isset($itemData['back_to_stock'])) {
					$backToStock[$orderItemId] = true;
				}
			}
			$data['qtys'] = $qtys;
	
			$service = Mage::getModel('sales/service_order', $order);
			if ($invoice) {
				$creditmemo = $service->prepareInvoiceCreditmemo($invoice, $data);
			} else {
				$creditmemo = $service->prepareCreditmemo($data);
			}
	
			/**
			 * Process back to stock flags
			 */
			foreach ($creditmemo->getAllItems() as $creditmemoItem) {
				$orderItem = $creditmemoItem->getOrderItem();
				$parentId = $orderItem->getParentItemId();
				if (isset($backToStock[$orderItem->getId()])) {
					$creditmemoItem->setBackToStock(true);
				} elseif ($orderItem->getParentItem() && isset($backToStock[$parentId]) && $backToStock[$parentId]) {
					$creditmemoItem->setBackToStock(true);
				} elseif (empty($savedData)) {
					$creditmemoItem->setBackToStock(Mage::helper('cataloginventory')->isAutoReturnEnabled());
				} else {
					$creditmemoItem->setBackToStock(false);
				}
			}
		}
	
		return $creditmemo;
	}
	
	
	public function checkstatusavail(){
			/**     * Adding New status     */
			$status = Mage::getModel('sales/order_status')->load('refund');
			if(!$status->getStatus()){
			    $data['label'] = 'Refund';
				$data['status'] = 'refund';
				$status->setData($data)->setStatus('refund');	
				$status->save();$status->assignState('complete', '0');
			}
			$status = Mage::getModel('sales/order_status')->load('refund_reqsted');
			if(!$status->getStatus()){
			    $data['label'] = 'Refund Requested';
				$data['status'] = 'refund_reqsted';
				$status->setData($data)->setStatus('refund_reqsted');	
				$status->save();$status->assignState('complete', '0');
			}
			$status = Mage::getModel('sales/order_status')->load('refund_confirmed');
			if(!$status->getStatus()){
			    $data['label'] = 'Refund Confirmed';
				$data['status'] = 'refund_confirmed';
				$status->setData($data)->setStatus('refund_confirmed');	
				$status->save();$status->assignState('complete', '0');
			}
			$status = Mage::getModel('sales/order_status')->load('void');
			if(!$status->getStatus()){
			    $data['label'] = 'Void';
				$data['status'] = 'void';
				$status->setData($data)->setStatus('void');	
				$status->save();$status->assignState('complete', '0');
			}
			/**     * Adding New status     */	
	}
	 
	 /**
     * Update items qty action
     */
    public function updateQtynewAction()
    {
        try {
			$data = $this->getRequest()->getPost();
			//print_r($data);
			$orderId = $this->getRequest()->getParam('order_id');
			$order = Mage::getModel('sales/order')->load($orderId);
			
			
			$this->checkstatusavail();
			
			/**     * Credit memo items */
			$creditmemo = $this->_initCreditmemo(true);

			/**     * Credit memo items */
			
			if($data['returnauthentication'] && $data['reasoncode']){
			
			$newitemarray = array();
			$itemarray = array();
			foreach ($creditmemo->getAllItems() as $creditmemoItem) {
				if( $creditmemoItem->getQty()){
                $orderItem = $creditmemoItem->getOrderItem();
				$itemarray[] = array('ProductCode' => $creditmemoItem->getSku(), 'Quantity' => $creditmemoItem->getQty());
				$newitemarray[] = array('itemid' => $creditmemoItem->getOrderItemId(),'ProductCode' => $creditmemoItem->getSku(), 'Quantity' => $creditmemoItem->getQty());
				}
             }
			 
			
			$params = array("Caller" => $this->getCallerArray(),"OrderNo" =>$order->getSwipezoomOrderNumberTemp(),"ReturnAuthentication"=>$data['returnauthentication'],"ROGReasonCode"=>$data['reasoncode'],"ProductLineItems"=>$itemarray );
			$client = Mage::helper('internationalshipping')->_createSoapClient();
			$response1 = $client->RogRefundReq($params);
			}else{
				  Mage::getSingleton('core/session')->addError(Mage::helper('core')->__('Please enter Return Authentication and Reason Code'));
			}

		 	Mage::log($response1,null,'SW_CreditMemo_'.$order->getSwipezoomOrderNumberTemp().'.log');
			if($order->getSwipezoomOrderNumberTemp()){
				if($response1->ResponseStatusCode=="000"){
					
					$newreforderid = $response1->RogActivityDetail->RogOrderNo;
					
					$folder1 =  Mage::getBaseDir('media') . DS . 'Swipzoom'. DS;
					if (!(@is_dir($folder1) || @mkdir($folder1, 0777, true))) {
						Mage::getSingleton('core/session')->addError(Mage::helper('core')->__( "Unable to create directory '{$folder1}'."));
					}
					$folder =  Mage::getBaseDir('media') . DS . 'Swipzoom'. DS . $newreforderid. DS ;
					if (!(@is_dir($folder) || @mkdir($folder, 0777, true))) {
						Mage::getSingleton('core/session')->addError(Mage::helper('core')->__( "Unable to create directory '{$folder}'."));
					}
					foreach( glob( $folder.'*.pdf'  ) as $filename ) {			chmod($filename, 0777);	}
					foreach( glob( $folder.'*.zip' ) as $filename ) {chmod($filename, 0777);}

					//$folder =  Mage::getBaseDir('media') . DS . 'Swipzoom'. DS . $newreforderid . DS ;
					//mkdir($folder);
					$imageFilename      = basename($response1->File->FileName);    
					$image_type         = substr(strrchr($imageFilename,"."),1); 
					$zipfile=  $filename           = $imageFilename; 
					$filepath           = Mage::getBaseDir('media') . DS . 'Swipzoom'. DS . $newreforderid. DS . $filename; 
					$newImgUrl          = file_put_contents($filepath, $response1->File->Contents); 
					$zip = new ZipArchive;
					if ($zip->open($filepath) === TRUE) {
						$zip->extractTo(Mage::getBaseDir('media') . DS . 'Swipzoom'. DS . $newreforderid. DS);
						$zip->close();
					} 
				/**     * Create new order */
					$id = $neworder = $this->creteorder($order->getCustomerId(),$itemarray,$this->getRequest()->getParam('order_id'),$newreforderid);// $response1->RogActivityDetail->RogOrderNo);		
				
				/**     * Create new order */
				
				$order->setSwipezoomOrderCreditmemo('2')->save();
				$order->addStatusToHistory('refund', 'Do Refund', false);
				$order->save();
				
				
				$swipezoomcreditmemo=  Mage::getModel('internationalshipping/swipezoomcreditmemo')->setId()->setSwipezoomorderid($order->getSwipezoomOrderNumberTemp())->setRealorderno($order->getId())->setRefswipezoomorderid($newreforderid)->setReforderno($id)->setCreditmemoitems(serialize($newitemarray))->save();
				
				$neworder=  Mage::getModel('sales/order')->load($neworder)->setSwipezoomOrderCreditmemo($order->getIncrementId())->save();
				$neworder=  Mage::getModel('sales/order')->load($id);//->addStatusHistoryComment('', 'refund')->save();
				$neworder->addStatusToHistory('refund_reqsted', 'Refund Requested', false);
				$neworder->save();
				
				
			    $response = array(
					'ajaxRedirect' => $this->getUrl('adminhtml/sales_order/index' ),//, array('order_id'=>$id)
					'ajaxExpired' => true
					);
				$response = Mage::helper('core')->jsonEncode($response);
				        
			}else{
				  Mage::helper('internationalshipping')->sendServiceFailureAlert($params,$response1,'RogRefundReq');	
				  $debugmode =  Mage::getStoreConfig('carriers/swipezoom/debug',Mage::app()->getStore());
				  if($debugmode){
					  $message = $response1->ResponseStatusDesc;
					  Mage::getSingleton('core/session')->addError(Mage::helper('core')->__($response1->ResponseStatusCode.' : '.$message));
				  }else{
					  $message =  'Unable to perform Credti Memo Action (Swipezoom Refund Request service)';	
					  Mage::getSingleton('core/session')->addError(Mage::helper('core')->__('Unable to perform Credti Memo Action (Swipezoom Refund Request service)'));
				  }
				  
				 	
						
				  $response = array(
					'error'     => true, 
					'message'   => $message,
					'ajaxRedirect' => $this->getUrl('adminhtml/sales_order_creditmemo/new' , array('order_id'=>$orderId)),
					'ajaxExpired' => true
					);
				  $response = Mage::helper('core')->jsonEncode($response);
			}
			}
        } catch (Mage_Core_Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage(),
				'ajaxRedirect' => $this->getUrl('adminhtml/sales_order_creditmemo/new' , array('order_id'=>$orderId)),
				'ajaxExpired' => true
            );
			Mage::getSingleton('core/session')->addError($e->getMessage());
            $response = Mage::helper('core')->jsonEncode($response);
        } 
        $this->getResponse()->setBody($response);
    }
	
	public function creteorder($customer,$newitems,$lastorder,$sworder){
		$customer = Mage::getModel('customer/customer')->load($customer);
		$lastorder = Mage::getModel('sales/order')->load($lastorder);
		$address = Mage::getModel('sales/order_address')->load($lastorder->getShippingAddressId());
		$transaction = Mage::getModel('core/resource_transaction');
		$storeId = $lastorder->getStoreId();
		$reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);
	
		$order = Mage::getModel('sales/order')
		->setIncrementId($reservedOrderId)
		->setStoreId($storeId)
		->setQuoteId(0)
		->setBaseCurrencyCode(Mage::app()->getBaseCurrencyCode())
		->setOrderCurrencyCode(Mage::app()->getBaseCurrencyCode())
		->setGlobalCurrencyCode(Mage::app()->getBaseCurrencyCode());
		
		
		if($lastorder->getCustomerGroupId()){
			$order->setCustomer_email($address->getEmail())
		->setCustomerFirstname($address->getFirstname())
		->setCustomerLastname($address->getLastname())
		->setCustomerGroupId($lastorder->getCustomerGroupId())
		->setCustomer_is_guest(0)
		->setCustomer($customer);
		}else{
			// set Customer data
			$order->setCustomer_email($address->getEmail())
			->setCustomerFirstname($address->getFirstname())
			->setCustomerLastname($address->getLastname())
			->setCustomerGroupId($lastorder->getCustomerGroupId());

		
		}

		$billingAddress = Mage::getModel('sales/order_address')
			->setStoreId($storeId)
			->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
			->setCustomerId($address->getCustomerId())
			->setCustomerAddressId($address->getCustomerAddressId())
			->setCustomer_address_id($address->getEntityId())
			->setPrefix($address->getPrefix())
			->setFirstname($address->getFirstname())
			->setMiddlename($address->getMiddlename())
			->setLastname($address->getLastname())
			->setSuffix($address->getSuffix())
			->setCompany($address->getCompany())
			->setStreet($address->getStreet())
			->setCity($address->getCity())
			->setCountry_id($address->getCountryId())
			->setRegion($address->getRegion())
			->setRegion_id($address->getRegionId())
			->setPostcode($address->getPostcode())
			->setTelephone($address->getTelephone())
			->setFax($address->getFax());
			$order->setBillingAddress($billingAddress);
		//$shipping = $customer->getDefaultShippingAddress();
		$shippingAddress = Mage::getModel('sales/order_address')
		->setStoreId($storeId)
		->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
		->setCustomerId($address->getCustomerId())
		->setCustomerAddressId($address->getCustomerAddressId())
		->setCustomer_address_id($address->getEntityId())
		->setPrefix($address->getPrefix())
		->setFirstname($address->getFirstname())
		->setMiddlename($address->getMiddlename())
		->setLastname($address->getLastname())
		->setSuffix($address->getSuffix())
		->setCompany($address->getCompany())
		->setStreet($address->getStreet())
		->setCity($address->getCity())
		->setCountryId($address->getCountryId())
		->setRegion($address->getRegion())
		->setRegionId($address->getRegionId())
		->setPostcode($address->getPostcode())
		->setTelephone($address->getTelephone())
		->setFax($address->getFax());
		
	
	
		$order->setShippingAddress($shippingAddress)
		->setShipping_method('flatrate_flatrate');	
		$order->setShippingDescription($lastorder->getShippingDescription());
		$order->setShippingAmount($lastorder->getShippingAmount());
		
		/*->setShippingDescription($this->getCarrierName('flatrate'));*/
		/*some error i am getting here need to solve further*/
	
		//you can set your payment method name here as per your need
		$orderPayment = Mage::getModel('sales/order_payment')
		->setStoreId($storeId)
		->setCustomerPaymentId(0)
		->setMethod('free')
		->setAction('yes')
		->setPo_number(' – ') 
		->setOrderStatus('processing');
		$order->setPayment($orderPayment);
	
		// let say, we have 2 products
		//check that your products exists
		//need to add code for configurable products if any
		$subTotal = 0;
	
		//$products = getFreeProducts($point);
	
		foreach ($newitems as $product) {
		$_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $product['ProductCode']); 
		$rowTotal = $_product->getPrice() * $product['Quantity'];
		$orderItem = Mage::getModel('sales/order_item')
		->setStoreId($storeId)
		->setQuoteItemId(0)
		->setQuoteParentItemId(NULL)
		->setProductId($_product->getId())
		->setProductType($_product->getTypeId())
		->setQtyBackordered(NULL)
		->setTotalQtyOrdered($product['Quantity'])
		->setQtyOrdered($product['Quantity'])
		->setName($_product->getName())
		->setSku($_product->getSku())
		->setPrice($_product->getPrice())
		->setBasePrice($_product->getPrice())
		->setOriginalPrice($_product->getPrice())
		->setRowTotal($rowTotal)
		->setBaseRowTotal($rowTotal);
	
		$subTotal += $rowTotal;
		//$subTotal = 0;
		$order->addItem($orderItem);
		}
		
		$order->setSubtotal($subTotal)
		->setBaseSubtotal($subTotal)
		->setGrandTotal($subTotal)
		->setBaseGrandTotal($subTotal);
	
		$transaction->addObject($order);
		$transaction->addCommitCallback(array($order, 'place'));
		$transaction->addCommitCallback(array($order, 'save'));
		$transaction->save();
		
		$order=  Mage::getModel('sales/order')->load($order->getId());
		$order->setSwipezoomOrderNumberTemp($sworder)->save();
		
		$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
		$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
		$invoice->register();
		
				$itemQty =  $order->getItemsCollection();//->count();
				$newarray = array();
				 foreach($itemQty as $item){
				$newarray[$item->getId()] = $item->getQtyInvoiced();	
				}
                 $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($newarray);
				$shipment->register();
               $transactionSave = Mage::getModel('core/resource_transaction')
				   ->addObject($invoice)
					->addObject($shipment)
					->addObject($order);
					

					//->addStatusHistoryComment('', 'refund')
					  
					$transactionSave->save();
			
		return $order->getId();
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
				
