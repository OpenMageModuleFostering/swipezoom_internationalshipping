<?php
/**
 *  Swipezoom_InternationalShipping_Model_Order
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Model_Order extends Mage_Sales_Model_Order
{
    const ENTITY                                = 'order';
    /**
    * XML configuration paths
    */
    const XML_PATH_EMAIL_TEMPLATE               = 'sales_email/order/template';
    const XML_PATH_EMAIL_GUEST_TEMPLATE         = 'sales_email/order/guest_template';
    const XML_PATH_EMAIL_IDENTITY               = 'sales_email/order/identity';
    const XML_PATH_EMAIL_COPY_TO                = 'sales_email/order/copy_to';
    const XML_PATH_EMAIL_COPY_METHOD            = 'sales_email/order/copy_method';
    const XML_PATH_EMAIL_ENABLED                = 'sales_email/order/enabled';

    const XML_PATH_UPDATE_EMAIL_TEMPLATE        = 'sales_email/order_comment/template';
    const XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE  = 'sales_email/order_comment/guest_template';
    const XML_PATH_UPDATE_EMAIL_IDENTITY        = 'sales_email/order_comment/identity';
    const XML_PATH_UPDATE_EMAIL_COPY_TO         = 'sales_email/order_comment/copy_to';
    const XML_PATH_UPDATE_EMAIL_COPY_METHOD     = 'sales_email/order_comment/copy_method';
    const XML_PATH_UPDATE_EMAIL_ENABLED         = 'sales_email/order_comment/enabled';

    /**
    * Order states
    */
    const STATE_NEW             = 'new';
    const STATE_PENDING_PAYMENT = 'pending_payment';
    const STATE_PROCESSING      = 'processing';
    const STATE_COMPLETE        = 'complete';
    const STATE_CLOSED          = 'closed';
    const STATE_CANCELED        = 'canceled';
    const STATE_HOLDED          = 'holded';
    const STATE_PAYMENT_REVIEW  = 'payment_review';

    /**
    * Order statuses
    */
    const STATUS_FRAUD  = 'fraud';

    /**
    * Order flags
    */
    const ACTION_FLAG_CANCEL    = 'cancel';
    const ACTION_FLAG_HOLD      = 'hold';
    const ACTION_FLAG_UNHOLD    = 'unhold';
    const ACTION_FLAG_EDIT      = 'edit';
    const ACTION_FLAG_CREDITMEMO= 'creditmemo';
    const ACTION_FLAG_INVOICE   = 'invoice';
    const ACTION_FLAG_REORDER   = 'reorder';
    const ACTION_FLAG_SHIP      = 'ship';
    const ACTION_FLAG_COMMENT   = 'comment';

    /**
    * Report date types
    */
    const REPORT_DATE_TYPE_CREATED = 'created';
    const REPORT_DATE_TYPE_UPDATED = 'updated';
    /*
    * Identifier for history item
    */
    const HISTORY_ENTITY_NAME = 'order';

    protected $_eventPrefix = 'sales_order';
    protected $_eventObject = 'order';

    protected $_addresses       = null;
    protected $_items           = null;
    protected $_payments        = null;
    protected $_statusHistory   = null;
    protected $_invoices;
    protected $_tracks;
    protected $_shipments;
    protected $_creditmemos;

    protected $_relatedObjects  = array();
    protected $_orderCurrency   = null;
    protected $_baseCurrency    = null;

    /**
    * Array of action flags for canUnhold, canEdit, etc.
    *
    * @var array
    */
    protected $_actionFlag = array();

    /**
    * Flag: if after order placing we can send new email to the customer.
    *
    * @var bool
    */
    protected $_canSendNewEmailFlag = true;

    /*
    * Identifier for history item
    *
    * @var string
    */
    protected $_historyEntityName = self::HISTORY_ENTITY_NAME;

    /**
    * Initialize resource model
    */
  
    /**
    * Send email with order data
    *
    * @return Mage_Sales_Model_Order
    */
    public function sendNewOrderEmail()
    {
        /* Changes BY SB on 23082013 */
        $swpchk=Mage::getModel('checkout/cart')->getQuote()->getShippingAddress()->getShippingMethod();
        if((strpos($swpchk,'swipezoom_custshippingcharges')) !== FALSE)
        {
            return $this;
        }   
        /* Changes end by Sb */    
        $storeId = $this->getStore()->getId();

        if (!Mage::helper('sales')->canSendNewOrderEmail($storeId)) {
            return $this;
        }
        // Get the destination email addresses to send copies to
        $copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $storeId);

        // Start store emulation process
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        try {
            // Retrieve specified view block from appropriate design package (depends on emulated store)
            $paymentBlock = Mage::helper('payment')->getInfoBlock($this->getPayment())
            ->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($storeId);
            $paymentBlockHtml = $paymentBlock->toHtml();
        } catch (Exception $exception) {
            // Stop store emulation process
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            throw $exception;
        }

        // Stop store emulation process
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        // Retrieve corresponding email template id and customer name
        if ($this->getCustomerIsGuest()) {
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $storeId);
            $customerName = $this->getBillingAddress()->getName();
        } else {
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
            $customerName = $this->getCustomerName();
        }

        $mailer = Mage::getModel('core/email_template_mailer');
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($this->getCustomerEmail(), $customerName);
        if ($copyTo && $copyMethod == 'bcc') {
            // Add bcc to customer email
            foreach ($copyTo as $email) {
                $emailInfo->addBcc($email);
            }
        }
        $mailer->addEmailInfo($emailInfo);

        // Email copies are sent as separated emails if their copy method is 'copy'
        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $emailInfo = Mage::getModel('core/email_info');
                $emailInfo->addTo($email);
                $mailer->addEmailInfo($emailInfo);
            }
        }

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
        'order'        => $this,
        'billing'      => $this->getBillingAddress(),
        'payment_html' => $paymentBlockHtml
        )
        );      

        $mailer->send();

        $this->setEmailSent(true);
        $this->_getResource()->saveAttribute($this, 'email_sent');

        return $this;
    }
	
	public function cretejustorder($customer,$newitems,$lastorder,$sworder,$shippingcharge){
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
		$order->setShippingAmount($shippingcharge);
		
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
		->setGrandTotal($subTotal+$shippingcharge)
		->setBaseGrandTotal($subTotal+$shippingcharge);
	
		$transaction->addObject($order);
		$transaction->addCommitCallback(array($order, 'place'));
		$transaction->addCommitCallback(array($order, 'save'));
		$transaction->save();
		
		$order=  Mage::getModel('sales/order')->load($order->getId());
		$order->setSwipezoomOrderNumberTemp($sworder)->save();
		
		
	           $transactionSave = Mage::getModel('core/resource_transaction')
					->addObject($order);
					
				$transactionSave->save();
			
		return $order->getId();
	}
	public function creteorder($customer,$newitems,$lastorder,$sworder,$shippingcharge){
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
		$order->setShippingAmount($shippingcharge);
		
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
		->setGrandTotal($subTotal+$shippingcharge)
		->setBaseGrandTotal($subTotal+$shippingcharge);
	
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

   }
