<?php

/**
 *  Swipezoom_InternationalShipping_Model_Carrier_Swipezoom
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Model_Carrier_Swipezoom
extends Mage_Shipping_Model_Carrier_Abstract
{
	protected $_code = 'swipezoom';
	protected $_isFixed = true;
	protected $_request = null;
	protected $_revised;
	protected $_customSelection;
	protected $_quoteobj;
	
	public function collectRates(Mage_Shipping_Model_Rate_Request $request)
	{
		if (!$this->getConfigFlag('active')) {
			return false;
		}

		$this->setRequest($request);
		$this->_getQuotes();
		return $this->getResult();;
	}

	/*
	 * Config setup with admin params
	 */
	public function setRequest(Mage_Shipping_Model_Rate_Request $request)
	{
		$this->_request = $request;

		$r = new Varien_Object();

		$r->setMerchantId($this->getConfigData('merchantid'));
		$r->setMerchantKey($this->getConfigData('merchantkey'));
		$r->setUrl($this->getConfigData('url'));
		$r->setPackageDescritpion($this->getConfigData('package_descritpion'));
		$r->setBaseSubtotalInclTax($request->getBaseSubtotalInclTax());
		$r->setOrderTotal($request->getGrandTotal());

		if ($request->getDestCountryId()) {
			$destCountry = $request->getDestCountryId();
		} else {
			$destCountry = self::USA_COUNTRY_ID;
		}
		$r->setDestCountry(Mage::getModel('directory/country')->load($destCountry)->getIso2Code());


		$this->_rawRequest = $r;

		return $this;
	}

	public function getResult()
	{
		return $this->_result;
	}
	/*
	 *  Returns Current Quote Object
	 */
	public function getQuoteObj(){
		if(isset($_quoteobj)){
			return $_quoteobj;
		}else{
			if (Mage::app()->getStore()->isAdmin()) {
				$session  = Mage::getSingleton('adminhtml/session_quote')->getQuote();
				return  Mage::getModel("sales/quote")->load($session->getId());
			}else{
				$session  = Mage::getSingleton('checkout/session');
				return Mage::getModel("sales/quote")->load($session->getQuote()->getEntityId());
			}
				 
		}
	}

	protected function __convertFormateAmount($amountToBeFormat){
		$baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
		$currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
		$amountToBeFormat = Mage::helper('directory')->currencyConvert($amountToBeFormat, $baseCurrencyCode, $currentCurrencyCode);
		return $amountToBeFormat; 
	}
	
	/*
	 * PREPARE TRANS_LOGISTICS_REQUEST
	 */
	protected function _formRateRequest(){
		
		if (Mage::app()->getStore()->isAdmin()) {
			$session  = Mage::getSingleton('adminhtml/session_quote');

		}else{
			$session  = Mage::getSingleton('checkout/session');
		}
		/*
		 *  PREPARE Billing Address Section
		 */
		$r = $this->_rawRequest;
		$sameasShipping = "Y";
		$shippingQuote = Mage::getSingleton('checkout/session')->getQuote();
		/*
		 *  PREPARE Billing Address Section
		 */
		$regionId = $shippingQuote->getBillingAddress()->getRegionId();
		$bState = (!empty($regionId))?Mage::getModel('directory/region')->load($regionId)->getCode():$shippingQuote->getShippingAddress()->getRegion();
		$bstreetArray = $shippingQuote->getBillingAddress()->getStreet();
		$billingArray = array(
        "Country"=>Mage::getModel('directory/country')->load($shippingQuote->getBillingAddress()->getCountryId())->getIso3Code(),
        "FirstName"=>$shippingQuote->getBillingAddress()->getFirstname(),
        "LastName"=>$shippingQuote->getBillingAddress()->getLastname(),
        "AddressLine1"=>$bstreetArray[0],
        "AddressLine2"=>$bstreetArray[1],
        "City"=>ucfirst($shippingQuote->getBillingAddress()->getCity()),
        "StateDivision"=>$bState,
        "PostalCode"=>$shippingQuote->getBillingAddress()->getPostcode(),
        "PhoneNumber"=>$shippingQuote->getBillingAddress()->getTelephone(),
        "Email"=>$shippingQuote->getBillingAddress()->getEmail()
		);
		if(empty($bstreetArray[1])) unset($billingArray["AddressLine2"]);

		/*
		 * PREPARE Shipping Address Section
		 * CONDITIONAL : SameAddForShipping = 0
		 */
		if(!$shippingQuote->getShippingAddress()->getData("same_as_billing")){

			$sameasShipping = "N";
			$sstreetArray = $shippingQuote->getShippingAddress()->getStreet();
			$regionId = $shippingQuote->getShippingAddress()->getRegionId();
			$sState = (!empty($regionId))?Mage::getModel('directory/region')->load($regionId)->getCode():$shippingQuote->getShippingAddress()->getRegion();
			$shippingArray = array(
            "Country"=>Mage::getModel('directory/country')->load($shippingQuote->getShippingAddress()->getCountryId())->getIso3Code(),
            "FirstName"=>$shippingQuote->getShippingAddress()->getFirstname(),
            "LastName"=>$shippingQuote->getShippingAddress()->getLastname(),
            "AddressLine1"=>$sstreetArray[0],
            "AddressLine2"=>$sstreetArray[1],
            "City"=>$shippingQuote->getShippingAddress()->getCity(),
            "StateDivision"=>$sState,
            "PostalCode"=>$shippingQuote->getShippingAddress()->getPostcode(),
            "PhoneNumber"=>$shippingQuote->getShippingAddress()->getTelephone(),
            "Email"=>$shippingQuote->getBillingAddress()->getEmail()
			);
			if(empty($sstreetArray[1])) unset($shippingArray["AddressLine2"]);
		}
		/* END */

		/*
		 * PREPARE Merchandise Line Items Section
		 */
		$QuoteItems = $shippingQuote->getAllItems();
		$currentCustomer = Mage::getSingleton('customer/session')->getCustomer();
		$listItems = array();
		$count = 0;
		$skipProduct = false;
		$finalAllProductAmountTotal = 0;
		foreach($QuoteItems as $key => $quoteItem){
			//load the product - this may not be needed if you get the product from a collection with the prices loaded.
			$product = Mage::getModel("catalog/product")->load($quoteItem->getProductId());
			if (!$skipProduct) {
				if ($product->isConfigurable()) {
					// use this and next product.
					$productquoteItem_next = $QuoteItems[$key+1];
					$product_next = Mage::getModel("catalog/product")->load($productquoteItem_next->getProductId());

					// Get add add Product Tax Amount
					$currentStore = Mage::app()->getStore($shippingQuote->getStoreId());
					$current_customer = Mage::getSingleton('customer/session')->getCustomer();
					/*$taxCalc = Mage::getSingleton('tax/calculation');
					$taxClassId = $product_next->getData("tax_class_id");
					$tax_rate_req = $taxCalc->getRateRequest($shippingQuote->getShippingAddress(),$shippingQuote->getBillingAddress(),$current_customer->getTaxClassId(),$currentStore);
					$percent = $taxCalc->getRate($tax_rate_req->setProductClassId($taxClassId));
					$tax = Mage::getModel('tax/calculation')->calcTaxAmount($quoteItem->getPrice(),$percent,false,true );
					$discuntAmount = (float)$quoteItem->getData('discount_amount');
					$finalProductAmount = ($quoteItem->getPrice()-$discuntAmount) + $tax;*/
					$finalProductAmount = $quoteItem->getPrice();
					$finalProductAmountTotal = $finalProductAmount *  $quoteItem->getQty();
					$finalAllProductAmountTotal = $finalAllProductAmountTotal + $finalProductAmountTotal;
					
					$currentFinalProductAmount = $this->__convertFormateAmount($finalProductAmount);
					$currentFinalProductAmountTotal = $this->__convertFormateAmount($finalProductAmountTotal);
					
					$listItems[$count] = array( "ItemId"=> $product_next->getSku(),
                    "ItemDescription"=>$product->getName(),
		            "ItemQuantity"=>$quoteItem->getQty(),
                    "ItemPrice"=>$currentFinalProductAmount,
		            "ItemTotalAmount"=>$currentFinalProductAmountTotal,   
		            "ItemImageURL"=>Mage::helper('catalog/product')->getImageUrl($product),
					);
					// DEFFAULT WEIGHT ATTRIBUTE
					if($weight = $product_next->getWeight()){
						$listItems[$count]["ItemWeightUnit"] = "LB";
						$listItems[$count]["ItemWeight"] = number_format($product_next->getWeight(),0) ;
					}
					// DEFAULT COLOR ATTRIBUTE
					if($color = $product_next->getColor()){
						$listItems[$count]["ItemColor"] = $product_next->getColor();
					}
					$count++;
					$skipProduct = true;

				} else {
						
					// Get add add Product Tax Amount
					$currentStore = Mage::app()->getStore($shippingQuote->getStoreId());
					$current_customer = Mage::getSingleton('customer/session')->getCustomer();
					/*$taxCalc = Mage::getSingleton('tax/calculation');
					$taxClassId = $product->getData("tax_class_id");
					$tax_rate_req = $taxCalc->getRateRequest($shippingQuote->getShippingAddress(),$shippingQuote->getBillingAddress(),$current_customer->getTaxClassId(),$currentStore);
					$percent = $taxCalc->getRate($tax_rate_req->setProductClassId($taxClassId));
					$tax = Mage::getModel('tax/calculation')->calcTaxAmount($quoteItem->getPrice(),$percent,false,true );
					$discuntAmount = (float)$quoteItem->getData('discount_amount');
					$finalProductAmount = ($quoteItem->getPrice()-$discuntAmount) + $tax;*/
					$finalProductAmount = $quoteItem->getPrice();
					$finalProductAmountTotal = $finalProductAmount *  $quoteItem->getQty();
					$finalAllProductAmountTotal = $finalAllProductAmountTotal + $finalProductAmountTotal;
					$currentFinalProductAmount = $this->__convertFormateAmount($finalProductAmount);
					$currentFinalProductAmountTotal = $this->__convertFormateAmount($finalProductAmountTotal);
					
					$listItems[$count] = array( "ItemId"=> $product->getSku(),
                    "ItemDescription"=>$product->getName(),
		            "ItemQuantity"=>$quoteItem->getQty(),
                    "ItemPrice"=>$currentFinalProductAmount,
		            "ItemTotalAmount"=>$currentFinalProductAmountTotal,
		            "ItemImageURL"=>Mage::helper('catalog/product')->getImageUrl($product),
					);

					// DEFFAULT WEIGHT ATTRIBUTE
					if($weight = $product->getWeight()){
						$listItems[$count]["ItemWeightUnit"] = "LB";
						$listItems[$count]["ItemWeight"] = number_format($product->getWeight(),0) ;
					}

					// DEFAULT COLOR ATTRIBUTE
					if($color = $product->getColor()){
						$listItems[$count]["ItemColor"] = $product->getColor();
					}
		
					$count++;
					$skipProduct = false;
				}

			} else {
				$skipProduct = false;
			}
		}
		$listItems =  array("LineItem"=>$listItems);
		
		$languageCode = Mage::getStoreConfig('general/locale/code', Mage::app()->getStore()->getId());
		$languageCode = substr($languageCode, 0,2);
		
		// GENERATE FINGUREPRINT FOR REQUEST
		$fingurePrint = $this->generateFingurePrint($r->getMerchantId(),$r->getMerchantKey(),$this->__convertFormateAmount($finalAllProductAmountTotal),$this->getQuoteObj()->getItemsQty(),$merchantRefKey);
		/* GENEREATE REQUEST PARAMS WITH DYNAMIC ORDER DETAILS*/
		$ratesRequest = array("Caller" => $this->getCallerArray() ,
        "RequestFingerprint" => $fingurePrint,
		"Order"=>array("OrderAmount"=>$this->__convertFormateAmount($finalAllProductAmountTotal),
        "OrderLineItemCount"=>number_format($this->getQuoteObj()->getItemsQty(),0),
        "OrderDescription"=>$r->getMerchantId().' MAG',
        "TransactionCurrency"=>Mage::app()->getStore()->getCurrentCurrencyCode(),
        "CustomerLanguage"=>$languageCode),
        "LineItems"=> $listItems,
        "Customer"=> array("SameAddForShipping"=>$sameasShipping,
        "BillingAddress"=>$billingArray
		)
		);

		if($sameasShipping == "N"){
			$ratesRequest["Customer"]["ShippingAddress"] = $shippingArray;
		}
		return $ratesRequest;

	}

	/**
	 * Merchant Caller Array
	 */
	public function getCallerArray(){
		$merchantRefNo = "";

		$callerObj = array("MerchantID" => Mage::getStoreConfig('carriers/swipezoom/merchantid',Mage::app()->getStore()),
        "MerchantKey" => Mage::getStoreConfig('carriers/swipezoom/merchantkey',Mage::app()->getStore()),
        "Version"=> "SW0101",
        "Datetime"      => date("Y-m-d h:i:s"),
        "MerchantRefNo"      => $merchantRefNo);
		return $callerObj;
	}

	/*
	 * GENERATE FINGUREPRINT SIGN
	 */
	public function generateFingurePrint($merchantId,$merchantKey,$subTotal,$qty,$merchantRefNo=""){

		$fPrintToEncript = $merchantId.$merchantKey.Mage::app()->getStore()->getCurrentCurrencyCode().$subTotal.number_format($qty,0).((!empty($merchantRefNo))?$merchantRefNo:"");
		return (md5($fPrintToEncript));;
	}

	/*
	 * EXECUTE SOAP TRANS_LOGISTICS_REQ
	 */
	protected function _doRatesRequest()
	{
		$ratesRequest = $this->_formRateRequest();
        
        $checkerString = $ratesRequest;
        unset($checkerString["Caller"]["Datetime"]);

        $requestString = serialize($ratesRequest);
        $uniqueString =  md5(serialize($checkerString));

	    $a = $checkerString; 
		$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($a)); 
		foreach($it as $v) { 
		   $reqString .= $v; 
		}

		$currentTime = strtotime(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
		if(Mage::getSingleton('core/session')->getSzRequestedTime())
			$requiredDuration = (($currentTime - Mage::getSingleton('core/session')->getSzRequestedTime()) / 60);
		else
			$requiredDuration = 0;

		$uniqueString = md5($reqString);

        $debugData = array('request' => $ratesRequest);
        if ($response === null && (Mage::getSingleton('core/session')->getRequestedString() != $uniqueString || $requiredDuration > 25)
                && Mage::app()->getFrontController()->getRequest()->getControllerName() != "cart") {
                try {
                		$requestedTime = strtotime(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
                		Mage::getSingleton('core/session')->setSzRequestedTime($requestedTime);

                        Mage::getSingleton('core/session')->setRequestedString($uniqueString);
                        Mage::log($ratesRequest,null,'SW_TransLogisticRequest.log');
                        $client = Mage::helper('internationalshipping')->_createSoapClient("frontend");
                        $response = $client->TransLogisticsReq($ratesRequest);
                        if($response->ResponseStatusCode != '000' ){
                        	Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'TransLogisticsReq');
                        	$response = $client->TransLogisticsReq($ratesRequest);
                        }
                        Mage::log($response,null,'SW_TransLogisticRequest.log');
                        $debugData['result'] = $response;
                        Mage::getSingleton('core/session')->setTransRequestData(serialize($response));
                } catch (Exception $e) {
                		Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'TransLogisticsReq',$e->getMessage());
                        $debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
                        Mage::logException($e);
                }
        } else {
                $response = unserialize(Mage::getSingleton('core/session')->getTransRequestData());
                $debugData['result'] = $response;
        }
        $this->_debug($debugData);
        return $response;
	}

	protected function _getQuotes()
	{

		$this->_result = Mage::getModel('shipping/rate_result');
		$response = $this->_doRatesRequest();
		$preparedGeneral = $this->_prepareRateResponse($response);
		$this->_result->append($preparedGeneral);
		return $this->_result;
	}


	/**
	 * TransLogistic Response
	 */
	protected function _prepareRateResponse($response)
	{
		$costArr = array();
		$priceArr = array();
		$errorTitle = 'Unable to Process Request. Try again Later.';


		if (is_object($response)) {
			if ($response->ResponseStatusCode != '000') {

				// IF DEBUG TRUE , ERROR MESSAGE FROM SWIPEZOOM WILL BE DISPLAY ON SHIPPING METHOD STEP
				($this->getConfigData('debug'))?$errorTitle = (string)$response->ResponseStatusDesc : $errorTitle = (string)$this->getConfigData('specificerrmsg');

				$flag = 0;
			} elseif ($response->ResponseStatusCode == "000") {
				$flag = 1;

				$shippingcharges = $response->OrderCustomerDetails->CustShippingcharges;
				$duties = $response->OrderCustomerDetails->CustCourierDuties;
				$insurance = $response->OrderCustomerDetails->CustInsuranceCharges;

				$priceArr["custshippingcharges_noduties_noinsurance"] =  $this->getMethodPrice($shippingcharges, "custshippingcharges_noduties_noinsurance") ;
				$priceArr["custshippingcharges_withduties_withinsurance"] = $this->getMethodPrice($shippingcharges+$duties+$insurance, "custshippingcharges_withduties_withinsurance") ;
				$priceArr["custshippingcharges_noduties_withinsurance"] = $this->getMethodPrice($shippingcharges+$insurance, "custshippingcharges_noduties_withinsurance") ;
				$priceArr["custshippingcharges_withduties_noinsurance"] = $this->getMethodPrice($shippingcharges+$duties, "custshippingcharges_withduties_noinsurance") ;
				$priceArr["CustCourierDuties"] = $this->getMethodPrice($response->OrderCustomerDetails->CustCourierDuties, "CustCourierDuties");
				$priceArr["CustCourierDuties_with_CustInsurance_Duties"] = $this->getMethodPrice($response->OrderCustomerDetails->CustInsuranceCharges, "CustCourierDuties_with_Insurance_Duties") ;
				 
				$extraCharges["DataStorageUrl"] = ""; 
				$extraCharges["CustVatAmount"] = 0; 
				$extraCharges["CustItemSubtotal"] = $response->OrderCustomerDetails->CustItemSubtotal;
				$extraCharges["CustTotalTransAmount"] = $response->OrderCustomerDetails->CustTotalTransAmount;
				$extraCharges["PrepaidDutiesAllowed"] = $response->LogisticDetails->PrepaidDutiesAllowed;
				$extraCharges["shipping"] = $response->OrderCustomerDetails->CustShippingcharges;
				$extraCharges["CustCourierDuties"] = $response->OrderCustomerDetails->CustCourierDuties;
				$extraCharges["CustInsuranceCharges"] = $response->OrderCustomerDetails->CustInsuranceCharges;
				$paymentmode = Mage::getStoreConfig('carriers/swipezoom/paymentenabled',Mage::app()->getStore());

				if(!empty($response->OrderPaymentDetails->StorageUrl) && $paymentmode == 1)
                    $extraCharges["DataStorageUrl"] = $response->OrderPaymentDetails->StorageUrl; 

                if(!empty($response->OrderCustomerDetails->CustVatAmount))
                	$extraCharges["CustVatAmount"] = $response->OrderCustomerDetails->CustVatAmount;

				/*Set Swipezoom order number in Quote tables*/
				Mage::getSingleton('checkout/session')->getQuote()->setSwipezoomOrderNumber($response->OrderDetails->OrderNo);
				Mage::getSingleton('checkout/session')->setSwipezoomExtraRates($extraCharges);
				Mage::getSingleton('core/session')->setSwipezoomExtraRates($extraCharges);

				
				if (Mage::app()->getStore()->isAdmin()) {
						
					Mage::getSingleton('adminhtml/session_quote')->getQuote()->setSwipezoomOrderNumber($response->OrderDetails->OrderNo);
				}

			}
		}

		$result = Mage::getModel('shipping/rate_result');
		if (!($flag)) {
			$error = Mage::getModel('shipping/rate_result_error');
			$error->setCarrier($this->_code);
			$error->setCarrierTitle($this->getConfigData('title'));
			$error->setErrorMessage($errorTitle);
			$result->append($error);
		} else {
			foreach ($priceArr as $method=>$price) {
				$rate = Mage::getModel('shipping/rate_result_method');
				$rate->setCarrier($this->_code);
				$rate->setCarrierTitle($this->getConfigData('title'));
				$rate->setMethod($method);
				$rate->setMethodTitle("DHL Express - ".$response->OrderCustomerDetails->TransitDays.(($response->OrderCustomerDetails->TransitDays > 1)?" Days":" Day"));
				$rate->setMethodDescription($extraCharges);
				$rate->setCost($costArr[$method]);
				$rate->setPrice($price);

				$result->append($rate);
			}
		}

		return $result;
	}
	public function getAllowedMethods()
	{
		return array('echo'=>$this->getConfigData('name'));
	}

	/*
	 * PREPARE (TRANS_LOGISTICS_CONFIRMAION) REQUEST
	 * ON SUCCESSFUL ORDER
	 */
	public function confirmOrderInvoiceRequest($shippingMethod,$szOrderNumber,$localOrderNumber,$orderRetry = 0){

		(strpos($shippingMethod,"withduties") !== FALSE)?$PrepaidDuties="Y":$PrepaidDuties="N";
		(strpos($shippingMethod,"withinsurance") !== FALSE)?$PrepaidInsurance="Y":$PrepaidInsurance="N";

		//  $merchantRefKey = $this->getQuoteObj()->getEntityId();
		$userAgent = Mage::helper('core/http')->getHttpUserAgent();
		$remoteAddr = Mage::helper('core/http')->getRemoteAddr(); 

		$fingurePrint = $this->generateFingurePrint($this->getConfigData('merchantid'),$this->getConfigData('merchantkey'),$this->getQuoteObj()->getSubtotal(),$this->getQuoteObj()->getItemsQty(),$this->getQuoteObj()->getEntityId());
		$ratesRequest = array("Caller" => array("MerchantID" => $this->getConfigData('merchantid'),
        "MerchantKey" => $this->getConfigData('merchantkey'),
        "Version"=> "SW0101",
        "Datetime"      => date("Y-m-d h:i:s"),
        "MerchantRefNo"      => $localOrderNumber,
        "RequestFingerprint" => $fingurePrint),
        "OrderNo"=>$szOrderNumber,
        "PrepaidDuties"=>$PrepaidDuties,
        "PrepaidInsurance"=>$PrepaidInsurance,
        );

		// making sure payment is enabled
		$paymentmode = Mage::getStoreConfig('carriers/swipezoom/paymentenabled',Mage::app()->getStore());
          if($paymentmode == 1) {
          $ratesRequest["Payment"] = array(
              "PaymentType" => 'ccard',
              "SuccessURL" => Mage::getBaseUrl().'cardpayment/payment/process?status=success&orderid='.$localOrderNumber,
              "CancelURL" => Mage::getBaseUrl().'cardpayment/payment/process?status=cancel&orderid='.$localOrderNumber,
              "FailureURL" => Mage::getBaseUrl().'cardpayment/payment/process?status=failure&orderid='.$localOrderNumber,
              "ServiceURL" => Mage::getBaseUrl(),
              "CustomerUserAgent" => $_SERVER['HTTP_USER_AGENT'],
              "CustomerIpAddress" => $_SERVER['REMOTE_ADDR']
          );
         }

		$response = $this->_doConfirmOrderRequest($ratesRequest,$orderRetry);
        
        if (is_object($response)) {
            
            if($paymentmode == 1)
            	$confirmation_response = array(array($response->ResponseStatusCode,$response->ResponseStatusDesc),$response);
            else
            	$confirmation_response = array($response->ResponseStatusCode,$response->ResponseStatusDesc);
            return $confirmation_response;
        }

        return false;
	}


	/**
	 * Set Payment Data For Order
	 */
	public function setPaymentAdditionalData($sz_orderid, $orderid, $status = 'F') {

		$additionalInformation       = Array();
        $additionalInformationString = '';

		$order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
		$payment = $order->getPayment();
        
		$client = Mage::helper('internationalshipping')->_createSoapClient();
		$params = array("Caller" => $this->getCallerArray(),"OrderNo" =>$sz_orderid );

		$response = $client->OrderDetails($params);
        
        $statuscheck=$response->ResponseStatusCode;
        $ResponseStatusDesc=$response->ResponseStatusDesc;

        if($statuscheck == "000") {
            $payment_details = $response->Order->PaymentGateway;
            $payment_details = (array) $payment_details;
            
            if(!empty($payment_details)) {

            	if($status == "S")
            		$payment_details = array_merge(array('Status' => 'Successfully Processed'),$payment_details);
            	else if($status == "F")
            		$payment_details = array_merge(array('Status' => 'Failed'),$payment_details);
            	else
            		$payment_details = array_merge(array('Status' => 'Cancelled'),$payment_details);

                foreach ($payment_details as $key => $value) {
                  $additionalInformation[htmlentities($key)] = htmlentities($value);
                }           
		        
		        if (count($additionalInformation) != 0) {
		            $payment->setAdditionalInformation($additionalInformation);
		            $payment->setAdditionalData(serialize($additionalInformation));
		            $payment->save();
		        }
            }
        }

	}	

	/*
	 * EXECUTE (TRANS_LOGISTICS_CONFIRMAION) REQUEST
	 */
	protected function _doConfirmOrderRequest($ratesRequest, $retry = 0)
	{
		$service_error = false;
		if ($response === null) {
			try {
				Mage::log($ratesRequest,null,'SW_TransLogisticConfirm.log');

				$client = Mage::helper('internationalshipping')->_createSoapClient("frontend");
				$response = $client->TransLogisticsConfirm($ratesRequest);
					
				if (is_object($response)) {
					$confirmation_response = array($response->ResponseStatusCode,$response->ResponseStatusDesc);
		            if(is_array($confirmation_response) && $confirmation_response[0] != "000"){ // failure
		                // send email alert    
		                Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'TransLogisticsConfirm');
		            }
	        	} else {
	        		$service_error = true;
	        		Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'TransLogisticsConfirm');
	        	}

				Mage::log($response,null,'SW_TransLogisticConfirm.log');
			} catch (Exception $e) {
				// sends an alert if php related issue occured
				$service_error = true;
				Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'TransLogisticsConfirm',$e->getMessage());
				Mage::logException($e);
			} catch (SoapFault $e) {
				// timeout case will catch the exception here
				$service_error = true;
				Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'TransLogisticsConfirm',$e->faultstring);
			}

			if($service_error == true && $retry < 4) {
				
				if($retry != 0) {  
                	sleep(5);  // sleep for 5 seconds 
                }

                $retry++;
                $this->_doConfirmOrderRequest($ratesRequest, $retry);           
            }

		} else {
			$response = unserialize($response);
		}
		return $response;
	}

	/**
	 * Address Validation
	 */
	public function AddressVerifacation()
	{

		$country1 = Mage::app()->getRequest()->getParam("country");

		$type = Mage::app()->getRequest()->getParam("type");

		$city = Mage::app()->getRequest()->getParam("city");
		$zipcode = Mage::app()->getRequest()->getParam("postcode");
		$street1 = Mage::app()->getRequest()->getParam("street1");
		$street2 = Mage::app()->getRequest()->getParam("street2");

		$regionid=Mage::app()->getRequest()->getParam("region");
		$regionname=Mage::app()->getRequest()->getParam("regionname");
		$country = Mage::getResourceModel('directory/country_collection')->addFieldToFilter("country_id",$country1)->getData();
		$country = $country[0]["iso3_code"];
		$region = (empty($regionname))?Mage::getModel('directory/region')->load($regionid)->getCode():$regionname;

		$client = Mage::helper('internationalshipping')->_createSoapClient("frontend");
		$params = array("Caller" => $this->getCallerArray(),
        "CountryCode" => "$country" ,                                            
        "FieldValues" => array(array("FieldType"=>1,"FieldValue"=>"$street1"),
		array("FieldType"=>2,"FieldValue"=>"$street2"),
		array("FieldType"=>"C","FieldValue"=>"$city"),
		array("FieldType"=>"D","FieldValue"=>"$region"),
		array("FieldType"=>"P","FieldValue"=>"$zipcode")),);

		Mage::log($params,null,'SW_AddressValidation.log');
		$response = $client->TransAddressValidation($params);
		 Mage::log($response,null,'SW_AddressValidation.log');
		$statuscheck=$response->ResponseStatusCode;
		$ResponseStatusDesc=$response->ResponseStatusDesc;
		if($statuscheck=="000")
		{
			$error["success"]=1;
			$addressString = $response->FormatedAddress;
			$sameAs = Mage::app()->getRequest()->getParam("sameasshipping");
			if($type == "billing"){
				Mage::getSingleton('checkout/session')->getQuote()->setSwipezoomAddressBillingString($addressString);
				if($sameAs == "true"){
					Mage::getSingleton('checkout/session')->getQuote()->setSwipezoomAddressShippingString($addressString);
				}
			}
			 
			else if($type == "shipping"){
				Mage::getSingleton('checkout/session')->getQuote()->setSwipezoomAddressShippingString($addressString);
			}

			Mage::getSingleton('checkout/session')->getQuote()->save();
			echo json_encode($error);
		}
		else
		{
			$response1=json_decode(json_encode($response), true);
			$errormessage1=$response1['AddressValidationErrors']['AddressValidationError'];
			if($errormessage1['ErrorDescription']==NULL)
			{
				for($i=0;$i<count($errormessage1);$i++)
				{
					if($errormessage1[$i]["AddressLabelType"] == "C")
					{
						$error[$i]["name"] = "city";
						$error[$i]["error"] = $errormessage1[$i]['ErrorDescription'];
					}

					if($errormessage1[$i]["AddressLabelType"] == "D"){
						$error[$i]["name"] = "region";
						$error[$i]["error"] = $errormessage1[$i]['ErrorDescription'];
					}
					if($errormessage1[$i]["AddressLabelType"] == "P"){
						$error[$i]["name"] = "postcode";
						$error[$i]["error"] = $errormessage1[$i]['ErrorDescription'];
					}
					if($errormessage1[$i]["AddressLabelType"] == "1"){
						$error[$i]["name"] = "street1";
						$error[$i]["error"] = $errormessage1[$i]['ErrorDescription'];
					}
				}
			}
			else
			{
				if($errormessage1["AddressLabelType"] == "C")
				{
					$error[0]["name"] = "city";
					$error[0]["error"] = $errormessage1['ErrorDescription'];
				}

				if($errormessage1["AddressLabelType"] == "D"){
					$error[0]["name"] = "region";
					$error[0]["error"] = $errormessage1['ErrorDescription'];
				}
				if($errormessage1["AddressLabelType"] == "P"){
					$error[0]["name"] = "postcode";
					$error[0]["error"] = $errormessage1['ErrorDescription'];
				}
				if($errormessage1["AddressLabelType"] == "1"){
					$error[0]["name"] = "street1";
					$error[0]["error"] = $errormessage1['ErrorDescription'];
				}
			}
			echo json_encode($error);
		}
	}

	/**
	 * Address Request Data
	 */
	protected function prepareAddressFormatRequest(){

		$country2= Mage::app()->getRequest()->getParam("countrycode");
		$country = Mage::getResourceModel('directory/country_collection')->addFieldToFilter("country_id",$country2)->getData();
		$country = $country[0]["iso3_code"];
		$languageCode = Mage::getStoreConfig('general/locale/code', Mage::app()->getStore()->getId());
		$languageCode = substr($languageCode, 0,2);

		$params = array("Caller" => $this->getCallerArray(),
        "LanguageCode" => "$languageCode",                                            
        "CountryCode" => "$country"
		);
		return $params;

	}

	/**
	 * Address Labeling
	 */
	public function doAddressFormatRequest()
	{
		$ratesRequest = $this->prepareAddressFormatRequest();
		
		$requestString = serialize($ratesRequest);
		$debugData = array('request' => $ratesRequest);
		if ($response === null) {
			try {
				$client = Mage::helper('internationalshipping')->_createSoapClient("frontend");
				$response = $client->TransAddressLabeling($ratesRequest);
				
				if($response->ResponseStatusCode != "000") {
					Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'TransAddressLabeling');
					$response = $client->TransAddressLabeling($ratesRequest);
				}
				if($response->ResponseStatusCode != "000") {
					Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'TransAddressLabeling');
					$response = $client->TransAddressLabeling($ratesRequest);
				}
				if($response->ResponseStatusCode != "000") {
					Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'TransAddressLabeling');
					$response = $client->TransAddressLabeling($ratesRequest);
				}

				$chk=json_decode(json_encode($response), true);
				$chk1=$chk['AddressFormats'];
				$chk2=$chk1['AddressFormat'];
				for($i=0;$i<count($chk2);$i++)
				{
					$labelcol[]=$chk2[$i];
				}
				return $labelcol;
				echo json_encode($labelcol);
				$debugData['result'] = $response;
			} catch (Exception $e) {
				$debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
				Mage::helper('internationalshipping')->sendServiceFailureAlert($ratesRequest,$response,'TransAddressLabeling',$e->getMessage());
				Mage::logException($e);
			}
		} else {
			$response = unserialize($response);
			$debugData['result'] = $response;
		}
		$this->_debug($debugData);
		return $response;
	}
	
	/**
	 * Get Cities AutoComplete
	 */
	public function getCitiesForAutoComplete() {
		$responseAutoComplete = '';
		try {
			// $billing=Mage::app()->getRequest()->getParam("billing");
			$city = Mage::app()->getRequest()->getParam('city');
			$country2= Mage::app()->getRequest()->getParam("countrycode");
			$country = Mage::getResourceModel('directory/country_collection')->addFieldToFilter("country_id",$country2)->getData();
			$country = $country[0]["iso3_code"];
			
			$params = array("Caller" => $this->getCallerArray(),
					        "CityName" => $city,                                            
					        "CountryCode" => $country,
							"ResponseSize" => 200
			);
			$client = Mage::helper('internationalshipping')->_createSoapClient("frontend");
			$response = $response = $client->GlobalCityLookup($params);

			$statuscheck=$response->ResponseStatusCode;
			$ResponseStatusDesc=$response->ResponseStatusDesc;
			$cityNamesCol = array();

			if($statuscheck=="000") {

				if (1 < sizeof($response->Cities->City) ) {
					foreach ($response->Cities->City as &$city) {
						// if city name already not added up
						if(!in_array(trim($city->CityName),$cityNamesCol)) {
							$responseAutoComplete = $responseAutoComplete.$city->CityName."\n";
							$cityNamesCol[] = trim($city->CityName);
						}
					}
				} else if (0 < sizeof($response->Cities->City) ) {
					$responseAutoComplete =  $responseAutoComplete.$response->Cities->City->CityName."\n";
				}

			}
		} catch (Exception $e) {
			  	Mage::helper('internationalshipping')->sendServiceFailureAlert($params,$response,'GlobalCityLookup',$e->getMessage());
				$debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
				Mage::logException($e);
		}
		return  $responseAutoComplete ;
	}

	/**
	 * Get States AutoComplete
	 */
	public function getStatesForAutoComplete (){
		$responseAutoComplete = '';
		try {
			// $billing=Mage::app()->getRequest()->getParam("billing");
			$city = Mage::app()->getRequest()->getParam('city');
			$state_id = Mage::app()->getRequest()->getParam('state');
			$country2= Mage::app()->getRequest()->getParam("countrycode");
			$country = Mage::getResourceModel('directory/country_collection')->addFieldToFilter("country_id",$country2)->getData();
			$country = $country[0]["iso3_code"];
			$state_id = (!empty($state_id))?Mage::getModel('directory/region')->load($state_id)->getCode():$state_id;
			$params = array("Caller" => $this->getCallerArray(),
					        "CityName" => $city,                                            
					        "CountryCode" => $country,
							"StateCode" => $state_id,
							"ResponseSize" => 200
			);
			$client = Mage::helper('internationalshipping')->_createSoapClient("frontend");
			$response = $response = $client->GlobalStateLookup($params);
			$statuscheck=$response->ResponseStatusCode;
			$ResponseStatusDesc=$response->ResponseStatusDesc;
			if($statuscheck=="000") {
				foreach ($response->States->State as &$state) {
					$responseAutoComplete = $responseAutoComplete.$state->StateName.'\n';
				}
			}
		} catch (Exception $e) {
				Mage::helper('internationalshipping')->sendServiceFailureAlert($params,$response,'GlobalStateLookup',$e->getMessage());
				$debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
				Mage::logException($e);
		}
		return  $responseAutoComplete ;
		
	
	} 
	
	/**
	 * Get City States
	 */
	public function getCityStates() {
		$responseAutoComplete = '';
		try {
			// $billing=Mage::app()->getRequest()->getParam("billing");
			$city = Mage::app()->getRequest()->getParam('city');
			$state_id = Mage::app()->getRequest()->getParam('state');
			$country2= Mage::app()->getRequest()->getParam("countrycode");
			$country = Mage::getResourceModel('directory/country_collection')->addFieldToFilter("country_id",$country2)->getData();
			$country = $country[0]["iso3_code"];
			$params = array("Caller" => $this->getCallerArray(),
					        //"CityName" => $city,                                            
					        "CountryCode" => $country,
							"StateCode" => $state_id,
							"ResponseSize" => 200
			);
			$client = Mage::helper('internationalshipping')->_createSoapClient("frontend");
			$response = $response = $client->GlobalStateLookup($params);
			$statuscheck=$response->ResponseStatusCode;
			$ResponseStatusDesc=$response->ResponseStatusDesc;
			$states = array();
			if($statuscheck=="000") {
				foreach ($response->States->State as &$state) {
					$region = Mage::getModel('directory/region')->loadByCode($state->StateCode, $country2);
					$state_id_m = $region->getId();
					$state_id_m = (!empty($state_id_m))?$state_id_m: $state->StateCode;
					$currentState = array('stateCode'=>$state_id_m,'stateName'=>$state->StateName);
					array_push($states,$currentState); 
				}
			}
			$responseAutoComplete = json_encode($states);
		} catch (Exception $e) {
				Mage::helper('internationalshipping')->sendServiceFailureAlert($params,$response,'GlobalStateLookup',$e->getMessage());
				$debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
				Mage::logException($e);
		}
		return  $responseAutoComplete ;
	}
	
	/**
	 * Postal Code Autocomplete
	 *
	 */
	public function getPostCodesForAutoComplete () {
		$responseAutoComplete = '';
		try {
			$city = Mage::app()->getRequest()->getParam('city');
			$state_id = Mage::app()->getRequest()->getParam('state');
			$country2= Mage::app()->getRequest()->getParam("countrycode");
			$country = Mage::getResourceModel('directory/country_collection')->addFieldToFilter("country_id",$country2)->getData();
			$country = $country[0]["iso3_code"];
			$state_id = (!empty($state_id))?Mage::getModel('directory/region')->load($state_id)->getCode():$state_id;
			$postCode =  Mage::app()->getRequest()->getParam('q');
			
			$params = array("Caller" => $this->getCallerArray(),
					        "CountryCode" => $country,
							"PostCode" => $postCode,
							"ResponseSize" => 200
			);
			Mage::log($params,null,'SW_PostCodeAutoComplete.log');
			$client = Mage::helper('internationalshipping')->_createSoapClient("frontend");
			$response = $client->GlobalPostCodeLookup($params);
			Mage::log($response,null,'SW_PostCodeAutoComplete.log');
			$statuscheck=$response->ResponseStatusCode;
			$ResponseStatusDesc=$response->ResponseStatusDesc;
			$ZipCodesCol = array();

			if($statuscheck=="000") {
				if (1 < sizeof($response->PostCodes->PostCode) ) {
					foreach ($response->PostCodes->PostCode as &$postCodeResp) {
						// if city name already not added up
						if(!in_array(trim($postCodeResp->PostCode),$ZipCodesCol)) {
							$responseAutoComplete = $responseAutoComplete.$postCodeResp->PostCode."\n";
							$ZipCodesCol[] = trim($postCodeResp->PostCode);
						}
					}
				} else if (0 < sizeof($response->PostCodes->PostCode) ) {
					$responseAutoComplete =  $responseAutoComplete.$response->PostCodes->PostCode->PostCode."\n";
				}
				
			}
			
		} catch (Exception $e) {
				Mage::helper('internationalshipping')->sendServiceFailureAlert($params,$response,'GlobalPostCodeLookup',$e->getMessage());
				$debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
				Mage::logException($e);
		}
		return $responseAutoComplete;
	
	} 
		
	
	
}
