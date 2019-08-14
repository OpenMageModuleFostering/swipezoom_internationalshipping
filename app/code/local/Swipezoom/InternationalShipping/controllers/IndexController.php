<?php

/**
 *  Swipezoom_InternationalShipping_IndexController
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        try {
            $countrycode= Mage::app()->getRequest()->getParam("countrycode");
            $specificCountry =  Mage::getStoreConfig('carriers/swipezoom/sallowspecific',Mage::app()->getStore());
            $specificCountryList =  Mage::getStoreConfig('carriers/swipezoom/specificcountry',Mage::app()->getStore());
            if($specificCountry == 1 && strpos($specificCountryList,$countrycode) === FALSE){
                $html =  $this->getLayout()->createBlock("checkout/onepage_billing")->setTemplate("internationalshipping/processformat.phtml")->setFlag("reset")->toHtml();
            }else{
                $responseArray =  Mage::getModel('internationalshipping/carrier_swipezoom')->doAddressFormatRequest(); 
                $html =  $this->getLayout()->createBlock("checkout/onepage_billing")->setTemplate("internationalshipping/processformat.phtml")->setResponseArray($responseArray)->toHtml();
            }

            $this->getResponse()->setBody($html);
            return;
        } catch (Exception $e) {
            $debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
        }
    }
	
	public function cancelAction() {
      
	  $this->loadLayout();   
	  $this->renderLayout(); 
	  
    }

	public function openpdfAction() {
      
	  $this->loadLayout();   
	  $this->renderLayout(); 
	  
    }
	
	public function printallAction() {
      
	  $this->loadLayout();   
	  $this->renderLayout(); 
	  
    }

	public function printpackAction() {
      $orderId = (int) $this->getRequest()->getParam('order_id');
	   $order = Mage::getModel('sales/order')->load($orderId);
	    Mage::register('current_order', $order);
	
	  $this->loadLayout();   
	  $this->renderLayout(); 
	  
    }

	public function adcommentAction() {
      $orderid = Mage::app()->getRequest()->getParam('order_id');
	  $key = Mage::app()->getRequest()->getParam('key');
	  $order = Mage::getModel('sales/order')->load($orderid);
 		try {	
				$haserror = false;
				$debugData['alertpopup'] = false;
				$debugData['me'] = Mage::getSingleton('core/session')->getEncryptedSessionId();
				if(!$this->getRequest()->getPost('comment')){
					$debugData['error'] = true;
					$debugData['message'] = 'Please choose reason.';
					$debugData['ajaxRedirect'] = Mage::getUrl('internationalshipping/index/cancel/order_id/'.$orderid.'/key/'.$key);
					$debugData['ajaxExpired'] = true;
					$debugData['alertpopup'] = true;
					Mage::getSingleton('core/session')->addError(Mage::helper('core')->__('Please choose reason.'));
					$haserror = true;
				}
				$swipwzoomorder = $order->getSwipezoomOrderNumberTemp();
				if($swipwzoomorder && !$haserror){
					$client = Mage::helper('internationalshipping')->_createSoapClient();
					$params = array("Caller" => $this->getCallerArray(),"OrderNo" =>$swipwzoomorder,"ReasonCode"=> preg_replace('/[^A-Za-z0-9\-]/', '', Mage::app()->getRequest()->getPost('comment'))  );
					Mage::log($params,null,'SW_CANCEL_'.$swipwzoomorder.'.log');
					$response = $client->CancelOrder($params);
					Mage::log($response,null,'SW_CANCEL_'.$swipwzoomorder.'.log');	
					$statuscheck=$response->ResponseStatusCode;
					$ResponseStatusDesc=$response->ResponseStatusDesc;			
					if($response->ResponseStatusCode == '000' ){
						$debugData['error'] = false;  
					}else{
						Mage::helper('internationalshipping')->sendServiceFailureAlert($params,$response,'CancelOrder');
						$debugData['error'] = true;
						$debugmode =  Mage::getStoreConfig('carriers/swipezoom/debug',Mage::app()->getStore());
						if($debugmode){
							
							$debugData['message'] = $statuscheck.' : '.$ResponseStatusDesc;
						
							 Mage::getSingleton('core/session')->addError(Mage::helper('core')->__($response->ResponseStatusCode.' : '.$ResponseStatusDesc));
						}else{
							$debugData['message'] = "Unable to perform Cancel Action (Swipezoom Cancel service)";
							Mage::getSingleton('core/session')->addError(Mage::helper('core')->__('Unable to perform Cancel Action (Swipezoom Cancel service)'));
						}
						$debugData['ajaxRedirect'] = Mage::getUrl('internationalshipping/index/cancel/order_id/'.$orderid.'/key/'.$key);
						$debugData['ajaxExpired'] = true;
					}
				}
			
			
		} catch (Exception $e) {
            $debugData['result'] = array('error' => true, 'message' => $e->getCode());
        }
		$response = Mage::helper('core')->jsonEncode($debugData);
		$this->getResponse()->setBody($response);
		
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
	
    public function CheckAddressAction()
    {
        $task = Mage::getModel('internationalshipping/carrier_swipezoom')->AddressVerifacation(); 
    }

    public function getProductListAction() {
    	$responseArray =  Mage::getModel('internationalshipping/carrier_swipezoom')->getProductList();
    	$dom = new DOMDocument;
		$dom->preserveWhiteSpace = FALSE;
		$dom->loadXML($responseArray);
		$dom->formatOutput = TRUE;
		echo $dom->saveXml();
    	return true;
    }
    
 	public function getCitiesForAutoCompleteAction() {
    	$responseArray =  Mage::getModel('internationalshipping/carrier_swipezoom')->getCitiesForAutoComplete(); 
    	echo $responseArray;
    	return true;
    	
    }

    public function checkIfCityExistsAction() {
    	$responseArray =  Mage::getModel('internationalshipping/carrier_swipezoom')->checkIfCityExists(); 
    	echo $responseArray;
    	return true;
    }
    
	public function getStatesForAutoCompleteAction() {
    	$responseArray =  Mage::getModel('internationalshipping/carrier_swipezoom')->getCitiesForAutoComplete(); 
    	echo $responseArray;
    	return true;
    	
    }
    
	public function getCityStatesAction() {
    	$responseArray =  Mage::getModel('internationalshipping/carrier_swipezoom')->getCityStates(); 
    	echo $responseArray;
    	return true;
    }
    
	public function getPostCodesForAutoCompleteAction() {
    	$responseArray =  Mage::getModel('internationalshipping/carrier_swipezoom')->getPostCodesForAutoComplete(); 
    	echo $responseArray;
    	return true;
    	
    }

    public function getEnabledPaymentMethodsAction() {
       $payments = Mage::getSingleton('payment/config')->getActiveMethods();
	   $methods = "<b><span style='color:red'>Following payment methods will be disabled with Swipezoom Payment enabled: </span></b>";
 		
 	   if(!empty($payments)) {

		   foreach ($payments as $paymentCode => $paymentModel) {
	            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
	            if($paymentCode != 'cardpayment')
	            	$methods .= "<br>".$paymentTitle.', ';
	        }
	 		
	 		echo trim($methods,', ');
 		} else {
 			echo "";
 		}
    	return true;
    }

    public function getEnabledShippingMethodsAction() {
       $shipping = Mage::getSingleton('shipping/config')->getActiveCarriers();
	   $methods = "<b><span style='color:red'>Following shipping methods will be disabled with Swipezoom Shipment enabled: </span></b>";
 		

 	   if(!empty($shipping)) {
		   foreach ($shipping as $shippingCode => $shipModel) {
	            $shipTitle = Mage::getStoreConfig('carriers/'.$shippingCode.'/title');

	            if($shippingCode != 'swipezoom')
	            	$methods .= "<br>".$shipTitle.', ';
	        }
	 		
	 		echo trim($methods,', ');
 		} else {
 			echo "";
 		}
    	return true;
    }    
    
}
