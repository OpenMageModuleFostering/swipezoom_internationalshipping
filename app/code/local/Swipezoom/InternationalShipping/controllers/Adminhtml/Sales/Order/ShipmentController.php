<?php

/**
 *  Swipezoom_InternationalShipping_Adminhtml_Sales_Order_ShipmentController
 *
 * @author Swipezoom
 */
 
require_once "Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php";  
class Swipezoom_InternationalShipping_Adminhtml_Sales_Order_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController{
/**
     * Save shipment
     * We can save only new shipment. Existing shipments are not editable
     *
     * @return null
     */
    public function saveAction()
    {
		
		//Mage::getModel('internationalshipping/observer')->salesordershipmentnew1();
		
		
		$orderid = Mage::app()->getRequest()->getParam('order_id');
			$order= Mage::getModel('sales/order')->load($orderid);
			$swipwzoomorder = $order->getSwipezoomOrderNumberTemp();
			if($swipwzoomorder){
			$count = Mage::getModel('internationalshipping/shipmentdetail')->getCollection()->addFieldToFilter('swipezoomorderid',$swipwzoomorder);
			if(!count($count)){
				try {
					$swipwzoomorder = $order->getSwipezoomOrderNumberTemp();
					$client = Mage::helper('internationalshipping')->_createSoapClient();
					$params = array("Caller" => $this->getCallerArray(),"OrderNo" => $swipwzoomorder );
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
						Mage::helper('internationalshipping')->sendServiceFailureAlert($params,$response,'Ship');
						$debugmode =  Mage::getStoreConfig('carriers/swipezoom/debug',Mage::app()->getStore());
						if($debugmode){
						Mage::getSingleton('core/session')->addError(Mage::helper('core')->__($statuscheck.' : '.$ResponseStatusDesc));
						}else{
						Mage::getSingleton('core/session')->addError(Mage::helper('core')->__('Unable to perform Ship action (Swipezoom Ship service)'));
						}
						 $this->_redirect('adminhtml/sales_order_shipment/new', array('order_id' => $this->getRequest()->getParam('order_id')));
						return;
					}
					
					
				} catch (Exception $e) {
							$debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
							Mage::log($debugData,null,'debug.log');
							Mage::logException($e);
				}
			}
		}
	
        $data = $this->getRequest()->getPost('shipment');
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try {
            $shipment = $this->_initShipment();
            if (!$shipment) {
                $this->_forward('noRoute');
                return;
            }

            $shipment->register();
            $comment = '';
            if (!empty($data['comment_text'])) {
                $shipment->addComment(
                    $data['comment_text'],
                    isset($data['comment_customer_notify']),
                    isset($data['is_visible_on_front'])
                );
                if (isset($data['comment_customer_notify'])) {
                    $comment = $data['comment_text'];
                }
            }

            if (!empty($data['send_email'])) {
                $shipment->setEmailSent(true);
            }

            $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
            $responseAjax = new Varien_Object();
            $isNeedCreateLabel = isset($data['create_shipping_label']) && $data['create_shipping_label'];

            if ($isNeedCreateLabel && $this->_createShippingLabel($shipment)) {
                $responseAjax->setOk(true);
            }

            $this->_saveShipment($shipment);


			if($swipwzoomorder && empty($data['send_email'])){
					$sendmail = false;
				}else{
					$sendmail = empty($data['send_email']);
				}
				
								if(!$swipwzoomorder)	
          						{ $shipment->sendEmail(!$sendmail, $comment); }else{
								 $shipment->setEmailSent(true);
								}
								if($swipwzoomorder){
								$count = Mage::getModel('internationalshipping/shipmentdetail')->getCollection()->addFieldToFilter('swipezoomorderid',$swipwzoomorder);
								if(count($count)){
									
								foreach($count as $oid){
										$carrier = $oid->getCouriername();
										$trackInfo = $oid->getCourierwaybillno();
										$carrier_code = $oid->getCourierservicename();
										$shiptime = $oid->getPickupdue();
								}
								
								$shipment_collection = Mage::getResourceModel('sales/order_shipment_collection');
								$shipment_collection->addAttributeToFilter('order_id', Mage::app()->getRequest()->getParam('order_id'));
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
											
										}catch (Exception $e)
										{
											
										}
										}
								}
								}
								}
            $shipmentCreatedMessage = $this->__('The shipment has been created.');
            $labelCreatedMessage    = $this->__('The shipping label has been created.');

            $this->_getSession()->addSuccess($isNeedCreateLabel ? $shipmentCreatedMessage . ' ' . $labelCreatedMessage
                : $shipmentCreatedMessage);
            Mage::getSingleton('adminhtml/session')->getCommentText(true);
        } catch (Mage_Core_Exception $e) {
            if ($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage($e->getMessage());
            } else {
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
            }
        } catch (Exception $e) {
            Mage::logException($e);
            if ($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage(
                    Mage::helper('sales')->__('An error occurred while creating shipping label.'));
            } else {
                $this->_getSession()->addError($this->__('Cannot save shipment.'));
                $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
            }

        }
        if ($isNeedCreateLabel) {
            $this->getResponse()->setBody($responseAjax->toJson());
        } else {
            $this->_redirect('*/sales_order/view', array('order_id' => $shipment->getOrderId()));
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
				
