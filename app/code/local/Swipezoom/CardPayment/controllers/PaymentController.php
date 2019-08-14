<?php

/**
 *	Swipezoom_CardPayment_PaymentController
 *
 * @author Swipezoom
 */

class Swipezoom_CardPayment_PaymentController extends Mage_Core_Controller_Front_Action {
	
	// The redirect action is triggered when someone places an order
	public function redirectAction() {
		$this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template','cardpayment',array('template' => 'cardpayment/redirect.phtml'));
		$this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
	}
	
	// The response action is triggered when your gateway sends back a response after processing the customer's payment
	public function responseAction() {
			
		$responseParams = $this->getRequest()->getParams();

		if(!empty($responseParams['orderid'])) {
			$orderId = $responseParams['orderid']; 
			$status = $responseParams['status']; 
			
			if($status == 'success') {
				// Payment was successful, so update the order's state, send order email and move to the success page
				$order = Mage::getModel('sales/order');
				$order->loadByIncrementId($orderId);
				$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Payment Processed Successfully');
				$order->setEmailSent(true); // email already sent by swipezoom
				$order->save();
				
				Mage::getSingleton('checkout/session')->unsQuoteId();
				
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=>true));

			} else if($status == 'cancel') {
				// cancel status returned by wirecard
				$this->cancelAction();
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure'=>true));

			} else {
				// failure status returned by wirecard
				$this->failureAction();
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure'=>true));
			}
		}
	}

	// generate parent window url 
	public function processAction() {
		
		$processParams = $this->getRequest()->getParams();

		if(!empty($processParams['status']) && !empty($processParams['orderid'])) {
			$url = Mage::getBaseUrl().'/cardpayment/payment/response?status='.$processParams['status'].'&orderid='.$processParams['orderid'];
			?>

			<script type="text/javascript">
				window.top.location.href = "<?php echo $url ?>"; 
			</script>
			
			<?php
		}
	}

	// The failure action is triggered when an order has failed
	public function failureAction() {
		if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
            if($order->getId()) {
				$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, 'Payment Processing Failed')->save();
				Mage::getModel("Swipezoom_InternationalShipping_Model_Carrier_Swipezoom")->setPaymentAdditionalData(Mage::getSingleton('sales/quote')->load($order->getQuoteId())->getSwipezoomOrderNumber(),Mage::getSingleton('checkout/session')->getLastRealOrderId(),'F');
			}
        }
	}
	
	// The cancel action is triggered when an order is cancelled
	public function cancelAction() {
        if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
            if($order->getId()) {
				$order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Payment Processing Cancelled')->save();
				Mage::getModel("Swipezoom_InternationalShipping_Model_Carrier_Swipezoom")->setPaymentAdditionalData(Mage::getSingleton('sales/quote')->load($order->getQuoteId())->getSwipezoomOrderNumber(),Mage::getSingleton('checkout/session')->getLastRealOrderId(),'C');
			}
        }
	}
}