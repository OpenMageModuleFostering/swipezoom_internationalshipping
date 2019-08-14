<?php

/**
 *  Swipezoom_InternationalShipping_IndexController
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function _createSoapClient($type = "admin") {

		if(Mage::getStoreConfig('carriers/swipezoom/mode') == 0) {
			// dev mode
			$adminurl = Mage::getStoreConfig('carriers/swipezoom/devadminurl',Mage::app()->getStore());
			$clienturl = Mage::getStoreConfig('carriers/swipezoom/devurl',Mage::app()->getStore());
		} else {
			//prod mode
			$adminurl = Mage::getStoreConfig('carriers/swipezoom/adminurl',Mage::app()->getStore());
			$clienturl = Mage::getStoreConfig('carriers/swipezoom/url',Mage::app()->getStore());
		}

		if($type=="admin") { // if admin side
			$wsdl = Mage::getModuleDir('etc', 'Swipezoom_InternationalShipping')  . DS . 'WsWsdl' .DS. "GecaWs.wsdl";
	        $client = new SoapClient($wsdl, array('trace' => $trace, "exceptions" => 0, "cache_wsdl" => 0));
	        $client->__setLocation($adminurl);
		} else {  // if client side
			$client = new SoapClient($clienturl,
				array("trace" => 1, "exceptions" => 1, "cache_wsdl" => 0));
		}

		return $client;
	}

	public function sendServiceFailureAlert($ratesRequest = array(), $response = "", $event = "", $extra = "") {
         
         if(!empty($ratesRequest['Caller']['MerchantKey'])) {
         	$ratesRequest['Caller']['MerchantKey'] = str_repeat("*", strlen($ratesRequest['Caller']['MerchantKey']));
         }
         	
         $date_time = Mage::getModel('core/date')->date('Y-m-d H:i:s');
         $timezone = Mage::getStoreConfig('general/locale/timezone');

         $timezone_n = new DateTimeZone($timezone);
		 $timenow = new DateTime('now', $timezone_n);
		 $offset = $timezone_n->getOffset( $timenow ) / 3600;
		 $timezone = "GMT" . ($offset < 0 ? $offset : "+".$offset);

         $currentUrl = Mage::helper('core/url')->getCurrentUrl();

         $storeName = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME);

         $to = "level2support@swipezoom.com";
         $subject = "[ALERT] [LEVEL-2] [MAGENTO] Application Report";

		 $body = "Event: ".$event."\nStore: ".$storeName."\nDate: ".$date_time." ".$timezone."\nURL: ".$currentUrl."\n\n";
		 $body .= "Request: \n".print_r($ratesRequest, true);

		 $body .= "\nResponse: \n";
		 if(empty($response))
		 	$body .= "Empty Response";
		 else
		 	$body .= print_r($response, true);

		 if(!empty($extra)) {
		 	$body .= "\n\nExtra: \n";
		 	$body .= $extra;
		 }

		 
		 mail($to, $subject, $body);
		   
    }
}