<?php

/**
 *  Swipezoom_InternationalShipping_OnepageController
 *
 * @author Swipezoom
 */

require_once 'Mage/Checkout/controllers/OnepageController.php';
class Swipezoom_InternationalShipping_OnepageController extends Mage_Checkout_OnepageController {

	public function savePaymentAction()
    {

        $hidereview = Mage::getStoreConfig('carriers/swipezoom/hidereview',Mage::app()->getStore());

        if($hidereview) {
            if ($this->_expireAjax()) {
                return;
            }
            try {
                if (!$this->getRequest()->isPost()) {
                    $this->_ajaxRedirectResponse();
                    return;
                }

                // set payment to quote
                $result = array();
                $data = $this->getRequest()->getPost('payment', array());
                $result = $this->getOnepage()->savePayment($data);

                // get section and redirect data
                $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
                if (empty($result['error']) && !$redirectUrl) {
                    $this->loadLayout('checkout_onepage_review');
                    $result['goto_section'] = 'review';
                    $result['update_section'] = array(
                        'name' => 'review',
                        'html' => $this->_getReviewHtml()
                    );
                }
                if ($redirectUrl) {
                    $result['redirect'] = $redirectUrl;
                }
            } catch (Mage_Payment_Exception $e) {
                if ($e->getFields()) {
                    $result['fields'] = $e->getFields();
                }
                $result['error'] = $e->getMessage();
            } catch (Mage_Core_Exception $e) {
                $result['error'] = $e->getMessage();
            } catch (Exception $e) {
                Mage::logException($e);
                $result['error'] = $this->__('Unable to set Payment Method.');
            }
      
            $result['redirect'] = '';
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            $this->saveOrderAction();
        } else {

            if ($this->_expireAjax()) {
                return;
            }
            try {
                if (!$this->getRequest()->isPost()) {
                    $this->_ajaxRedirectResponse();
                    return;
                }

                $data = $this->getRequest()->getPost('payment', array());
                $result = $this->getOnepage()->savePayment($data);

                // get section and redirect data
                $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
                if (empty($result['error']) && !$redirectUrl) {
                    $this->loadLayout('checkout_onepage_review');
                    $result['goto_section'] = 'review';
                    $result['update_section'] = array(
                        'name' => 'review',
                        'html' => $this->_getReviewHtml()
                    );
                }
                if ($redirectUrl) {
                    $result['redirect'] = $redirectUrl;
                }
            } catch (Mage_Payment_Exception $e) {
                if ($e->getFields()) {
                    $result['fields'] = $e->getFields();
                }
                $result['error'] = $e->getMessage();
            } catch (Mage_Core_Exception $e) {
                $result['error'] = $e->getMessage();
            } catch (Exception $e) {
                Mage::logException($e);
                $result['error'] = $this->__('Unable to set Payment Method.');
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        
        }
    }

    public function saveBillingAction()
    {
        $hideshipping = Mage::getStoreConfig('carriers/swipezoom/hideshipping',Mage::app()->getStore());

        if($hideshipping) {

            if ($this->_expireAjax()) {
                return;
            }
            if ($this->getRequest()->isPost()) {
                $data = $this->getRequest()->getPost('billing', array());
                $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
                $result = $this->getOnepage()->saveBilling($data, $customerAddressId);
                
                if (!isset($result['error'])) {

                    if($data['use_for_shipping'] == 1) {

                        $method = 'swipezoom_custshippingcharges_noduties_noinsurance';
                        $swipezoomCountry = true;
                        $logistic_success = false;
                        $existing_carrier = '';

                        $countrycode= $data['country_id'];
                        $specificCountry =  Mage::getStoreConfig('carriers/swipezoom/sallowspecific',Mage::app()->getStore());
                        $specificCountryList =  Mage::getStoreConfig('carriers/swipezoom/specificcountry',Mage::app()->getStore());
                        if($specificCountry == 1 && strpos($specificCountryList,$countrycode) === FALSE){
                              $swipezoomCountry = false; 
                        }else{
                              $swipezoomCountry = true;               
                        }

                        $cart = Mage::getSingleton('checkout/cart');
                        $address = $cart->getQuote()->getShippingAddress();
                        $rates = $address->collectShippingRates()
                         ->getGroupedAllShippingRates();

                        foreach ($rates as $carrier) {
                            foreach ($carrier as $rate) {
                                $shipping_method = $rate->getData();

                                if($swipezoomCountry) {
                                    if($shipping_method['code'] == $method) {
                                        $logistic_success = true;
                                    }
                                } else {
                                    $existing_carrier = $shipping_method['code'];
                                }
                            }
                        }

                        $shippingaddress = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();    

                        if($swipezoomCountry) {
                            if(!$logistic_success) {
                                $specificerrmsg = Mage::getStoreConfig('carriers/swipezoom/specificerrmsg',Mage::app()->getStore());
                                $result = array('error' => -1, 'message' => Mage::helper('checkout')->__($specificerrmsg));
                                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                                return;
                            } else {
                                $shippingaddress->setShippingMethod($method);
                                $shippingaddress->save(); 
                                $result = $this->getOnepage()->saveShippingMethod($method);
                            }
                        } else {
                            $shippingaddress->setShippingMethod($existing_carrier);
                            $shippingaddress->save(); 
                            $result = $this->getOnepage()->saveShippingMethod($existing_carrier);
                        }

                    }

                    if ($this->getOnepage()->getQuote()->isVirtual()) {

                        $result['goto_section'] = 'payment';
                        $result['update_section'] = array(
                            'name' => 'payment-method',
                            'html' => $this->_getPaymentMethodsHtml()
                        );
                    }
                    elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
      
                        $result['goto_section'] = 'payment';
                        $result['update_section'] = array(
                            'name' => 'payment-method',
                            'html' => $this->_getPaymentMethodsHtml()
                        );
                    }
                    else {
                        $result['goto_section'] = 'shipping';
                    }
                }
      
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            }
        } else {
            
            if ($this->_expireAjax()) {
                return;
            }
            if ($this->getRequest()->isPost()) {
                $data = $this->getRequest()->getPost('billing', array());
                $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

                if (isset($data['email'])) {
                    $data['email'] = trim($data['email']);
                }
                $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

                if (!isset($result['error'])) {
                    if ($this->getOnepage()->getQuote()->isVirtual()) {
                        $result['goto_section'] = 'payment';
                        $result['update_section'] = array(
                            'name' => 'payment-method',
                            'html' => $this->_getPaymentMethodsHtml()
                        );
                    } elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                        $result['goto_section'] = 'shipping_method';
                        $result['update_section'] = array(
                            'name' => 'shipping-method',
                            'html' => $this->_getShippingMethodsHtml()
                        );

                        $result['allow_sections'] = array('shipping');
                        $result['duplicateBillingInfo'] = 'true';
                    } else {
                        $result['goto_section'] = 'shipping';
                    }
                }

                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            }
    
        }
    }

    
    public function saveShippingAction()
    {
        $hideshipping = Mage::getStoreConfig('carriers/swipezoom/hideshipping',Mage::app()->getStore());

        if($hideshipping) {
            if ($this->_expireAjax()) {
                return;
            }
            if ($this->getRequest()->isPost()) {
                $data = $this->getRequest()->getPost('shipping', array());
                $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
                $result = $this->getOnepage()->saveShipping($data, $customerAddressId);
      
                if (!isset($result['error'])) {
                        
                    $method = 'swipezoom_custshippingcharges_noduties_noinsurance';
                    $swipezoomCountry = true;
                    $logistic_success = false;
                    $existing_carrier = '';

                    $countrycode= $data['country_id'];
                    $specificCountry =  Mage::getStoreConfig('carriers/swipezoom/sallowspecific',Mage::app()->getStore());
                    $specificCountryList =  Mage::getStoreConfig('carriers/swipezoom/specificcountry',Mage::app()->getStore());
                    if($specificCountry == 1 && strpos($specificCountryList,$countrycode) === FALSE){
                          $swipezoomCountry = false; 
                    }else{
                          $swipezoomCountry = true;               
                    }

                    $cart = Mage::getSingleton('checkout/cart');
                    $address = $cart->getQuote()->getShippingAddress();
                    $rates = $address->collectShippingRates()
                     ->getGroupedAllShippingRates();


                    foreach ($rates as $carrier) {
                        foreach ($carrier as $rate) {
                            $shipping_method = $rate->getData();

                            if($swipezoomCountry) {
                                if($shipping_method['code'] == $method) {
                                    $logistic_success = true;
                                }
                            } else {
                                $existing_carrier = $shipping_method['code'];
                            }
                        }
                    }

                    $shippingaddress = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();    

                    if($swipezoomCountry) {
                        if(!$logistic_success) {
                            $specificerrmsg = Mage::getStoreConfig('carriers/swipezoom/specificerrmsg',Mage::app()->getStore());
                            $result = array('error' => -1, 'message' => Mage::helper('checkout')->__($specificerrmsg));
                            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                            return;
                        } else {
                            $shippingaddress->setShippingMethod($method);
                            $shippingaddress->save(); 
                            $result = $this->getOnepage()->saveShippingMethod($method);
                        }
                    } else {
                        $shippingaddress->setShippingMethod($existing_carrier);
                        $shippingaddress->save(); 
                        $result = $this->getOnepage()->saveShippingMethod($existing_carrier);
                    }
                
                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                    );
                }
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            }
        } else {

            if ($this->_expireAjax()) {
                return;
            }
            if ($this->getRequest()->isPost()) {
                $data = $this->getRequest()->getPost('shipping', array());
                $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
                $result = $this->getOnepage()->saveShipping($data, $customerAddressId);

                if (!isset($result['error'])) {
                    $result['goto_section'] = 'shipping_method';
                    $result['update_section'] = array(
                        'name' => 'shipping-method',
                        'html' => $this->_getShippingMethodsHtml()
                    );
                }
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            }
    
        }
    }

	public function saveOrderAction()
    {
        $hidereview = Mage::getStoreConfig('carriers/swipezoom/hidereview',Mage::app()->getStore());

        if($hidereview) {
            if ($this->_expireAjax()) {
                return;
            }

            $result = array();
            try {
                if ($requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds()) {
                    $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
                    if ($diff = array_diff($requiredAgreements, $postedAgreements)) {
                        $result['success'] = false;
                        $result['error'] = true;
                        $result['error_messages'] = $this->__('Please agree to all the terms and conditions before placing the order.');
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                        return;
                    }
                }
                if ($data = $this->getRequest()->getPost('payment', false)) {
                    $this->getOnepage()->getQuote()->getPayment()->importData($data);
                }
                $this->getOnepage()->saveOrder();

                $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
                $result['success'] = true;
                $result['error']   = false;
            } catch (Mage_Payment_Model_Info_Exception $e) {
                $message = $e->getMessage();
                if( !empty($message) ) {
                    $result['error_messages'] = $message;
                }
                $result['goto_section'] = 'payment';
                $result['update_section'] = array(
                    'name' => 'payment-method',
                    'html' => $this->_getPaymentMethodsHtml()
                );
            } catch (Mage_Core_Exception $e) {
                Mage::logException($e);
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                $result['success'] = false;
                $result['error'] = true;
                $result['error_messages'] = $e->getMessage();

                if ($gotoSection = $this->getOnepage()->getCheckout()->getGotoSection()) {
                    $result['goto_section'] = $gotoSection;
                    $this->getOnepage()->getCheckout()->setGotoSection(null);
                }

                if ($updateSection = $this->getOnepage()->getCheckout()->getUpdateSection()) {
                    if (isset($this->_sectionUpdateFunctions[$updateSection])) {
                        $updateSectionFunction = $this->_sectionUpdateFunctions[$updateSection];
                        $result['update_section'] = array(
                            'name' => $updateSection,
                            'html' => $this->$updateSectionFunction()
                        );
                    }
                    $this->getOnepage()->getCheckout()->setUpdateSection(null);
                }
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                $result['success']  = false;
                $result['error']    = true;
                $result['error_messages'] = $this->__('There was an error processing your order. Please contact us or try again later.');
            }
            $this->getOnepage()->getQuote()->save();
            /**
             * when there is redirect to third party, we don't want to save order yet.
             * we will save the order in return action.
             */
            if (isset($redirectUrl)) {
                $result['redirect'] = $redirectUrl;
            }
    		  else
    		  {
    		   $result['redirect'] = Mage::getUrl('*/*/success');
    		  }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {

            if (!$this->_validateFormKey()) {
                $this->_redirect('*/*');
                return;
            }

            if ($this->_expireAjax()) {
                return;
            }

            $result = array();
            try {
                $requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
                if ($requiredAgreements) {
                    $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
                    $diff = array_diff($requiredAgreements, $postedAgreements);
                    if ($diff) {
                        $result['success'] = false;
                        $result['error'] = true;
                        $result['error_messages'] = $this->__('Please agree to all the terms and conditions before placing the order.');
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                        return;
                    }
                }

                $data = $this->getRequest()->getPost('payment', array());
                if ($data) {
                    $data['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT
                        | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
                        | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
                        | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
                        | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
                    $this->getOnepage()->getQuote()->getPayment()->importData($data);
                }

                $this->getOnepage()->saveOrder();

                $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
                $result['success'] = true;
                $result['error']   = false;
            } catch (Mage_Payment_Model_Info_Exception $e) {
                $message = $e->getMessage();
                if (!empty($message)) {
                    $result['error_messages'] = $message;
                }
                $result['goto_section'] = 'payment';
                $result['update_section'] = array(
                    'name' => 'payment-method',
                    'html' => $this->_getPaymentMethodsHtml()
                );
            } catch (Mage_Core_Exception $e) {
                Mage::logException($e);
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                $result['success'] = false;
                $result['error'] = true;
                $result['error_messages'] = $e->getMessage();

                $gotoSection = $this->getOnepage()->getCheckout()->getGotoSection();
                if ($gotoSection) {
                    $result['goto_section'] = $gotoSection;
                    $this->getOnepage()->getCheckout()->setGotoSection(null);
                }
                $updateSection = $this->getOnepage()->getCheckout()->getUpdateSection();
                if ($updateSection) {
                    if (isset($this->_sectionUpdateFunctions[$updateSection])) {
                        $updateSectionFunction = $this->_sectionUpdateFunctions[$updateSection];
                        $result['update_section'] = array(
                            'name' => $updateSection,
                            'html' => $this->$updateSectionFunction()
                        );
                    }
                    $this->getOnepage()->getCheckout()->setUpdateSection(null);
                }
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                $result['success']  = false;
                $result['error']    = true;
                $result['error_messages'] = $this->__('There was an error processing your order. Please contact us or try again later.');
            }
            $this->getOnepage()->getQuote()->save();
            
            if (isset($redirectUrl)) {
                $result['redirect'] = $redirectUrl;
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        
        }

    }

}