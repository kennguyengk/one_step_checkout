<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Payment
 * @package    Red_Star_Solution
 * @copyright  Copyright (c) 2015 KenNguyen <teogk89@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
 
require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'OnepageController.php';

class Onestep_Checkout_OnepageController extends Mage_Checkout_OnepageController
{
    public function preDispatch()
    {
        if (!$this->_isOneStepCheckoutLayout()) {
            return parent::preDispatch();
        }
        parent::preDispatch();
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if (!$quote->getShippingAddress()->getCollectShippingRates()) {
            $this->getOnepage()->saveCheckoutMethod('register');
            $quote->getShippingAddress()->setSameAsBilling(1);
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->getShippingAddress()->setCountryId(Mage::helper('core')->getDefaultCountry());
        }
        if ($messages = $this->getRequest()->getParam('giftmessage', array())) {
            $this->getOnepage()->createGiftMessage($messages);
        }
    }

    public function getUpdateHtml($update)
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load($update);
        $layout->generateXml();
        $layout->generateBlocks();
        return $layout->getOutput();
    }

    public function postDispatch()
    {
        parent::postDispatch();
        if (!$this->_isOneStepCheckoutLayout()) {
            return $this;
        }
        if (!$this->getRequest()->getHeader('X-Requested-With')) {
            return $this;
        }
        $result = array('blocks' => array());
        if ($blocks = $this->getRequest()->getParam('blocks', array())) {
            $blocks = explode(',', $blocks);
        }
        $body = Mage::helper('core')->jsonDecode($this->getResponse()->getBody());
        if (isset($body['message'])) {
            $result['error'] = $body['message'];
        }
        if (isset($body['success'])) {
            $result['success'] = $body['success'];
        }
        if (isset($body['redirect'])) {
            $result['redirect'] = $body['redirect'];
        }
        if(Mage::helper('onestepcheckout')->isOneStepLayout()) {
            $this->getLayout()->getUpdate()->merge('onestepcheckout_onepage_index');

        }


        $this->getLayout()->generateXml();
        $this->getLayout()->generateBlocks();
        $onepageBlock = $this->getLayout()->getBlock('custom.checkout.onepage');
        foreach ($blocks as $blockName) {
            $result['blocks'][$blockName] = $onepageBlock->getChild($blockName)->toHtml();
        }
        echo Mage::helper('core')->jsonEncode($result);
        exit;
    }


    public function indexAction()
    {
        if (!$this->_isOneStepCheckoutLayout()) {
            return parent::indexAction();
        }

        if (!Mage::helper('checkout')->canOnepageCheckout()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The onepage checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }
        $quote = $this->getOnepage()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                Mage::getStoreConfig('sales/minimum_order/error_message') :
                Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');

            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }
        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
        Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*',
            array('_secure' => true)));
        $this->getOnepage()->initCheckout();
        $update = $this->getLayout()->getUpdate();
        $update->addHandle('default');
        $this->addActionLayoutHandles();



        if(Mage::helper('onestepcheckout')->isStandartLayout()) {
            $update->addHandle('checkout_onepage_index');
        } elseif(Mage::helper('onestepcheckout')->isHorisontalLayout()) {
            $update->addHandle('onestepcheckout_horizontal');
        }

        $this->loadLayoutUpdates();
        $this->generateLayoutXml();
        $this->generateLayoutBlocks();
        $this->_isLayoutLoaded = true;
        $this->getLayout()->getBlock('head')->setTitle($this->__('Checkout'));
        $this->renderLayout();
    }

    public function savePaymentAction()
    {
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
                $this->loadLayout('checkout_onepage_review_horizontal');
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


    public function saveOrderAction()
    {
        if (!$this->_isOneStepCheckoutLayout()) {
            return parent::saveOrderAction();
        }
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
                    $result['message'] = $this->__('Please agree to all the terms and conditions before placing the order.');
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }

            }

            if ($error = $this->_saveBilling()) {
                $result['success'] = false;
                $result['error'] = true;
                $result['message'] = $error;
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            } elseif ($error = $this->_saveShipping()) {
                $result['success'] = false;
                $result['error'] = true;
                $result['message'] = $error;
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            } elseif ($error = $this->_saveShippingMethod()) {
                $result['success'] = false;
                $result['error'] = true;
                $result['message'] = $error;
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }

            if ($data = $this->getRequest()->getPost('payment', false)) {
                $this->getOnepage()->getQuote()->getPayment()->importData($data);
            }

            $this->getOnepage()->saveOrder();
            $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
            $result['success'] = true;
            $result['error'] = false;
        } catch (Mage_Payment_Model_Info_Exception $e) {
            $message = $e->getMessage();
            if (!empty($message)) {
                $result['message'] = $message;
            }
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $result['success'] = false;
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $result['success'] = false;
            $result['error'] = true;
            $result['message'] = $this->__('There was an error processing your order. Please contact us or try again later.');
        }
        $this->getOnepage()->getQuote()->save();
        /**
         * when there is redirect to third party, we don't want to save order yet.
         * we will save the order in return action.
         */
        if (isset($redirectUrl)) {
            $result['redirect'] = $redirectUrl;
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }


    protected function _saveBilling()
    {
        $data = $this->getRequest()->getPost('billing', array());
        $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
        if (isset($data['email'])) {
            $data['email'] = trim($data['email']);
        }
        $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

        return isset($result['error']) ? $result['message'] : '';
    }

    protected function _saveShipping()
    {
        if (Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getSameAsBilling()
            || $this->getOnepage()->getQuote()->isVirtual()
        ) {
            return '';
        }
        $data = $this->getRequest()->getPost('shipping', array());
        $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
        $result = $this->getOnepage()->saveShipping($data, $customerAddressId);
        return isset($result['error']) ? $result['message'] : '';
    }

    protected function _saveShippingMethod()
    {
        if ($this->getOnepage()->getQuote()->isVirtual()) {
            return '';
        }
        $data = $this->getRequest()->getPost('shipping_method', '');
        $result = $this->getOnepage()->saveShippingMethod($data);
        return isset($result['error']) ? $result['message'] : '';
    }

    protected function _savePayment()
    {
        $data = $this->getRequest()->getPost('payment', array());
        $result = $this->getOnepage()->savePayment($data);
        return isset($result['error']) ? $result['message'] : '';
    }

    protected function _isOneStepCheckoutLayout()
    {
        return Mage::helper('onestepcheckout')->isOneStepLayout();
    }


    public function renderLayout($output = '')
    {

        if (Mage::helper('onestepcheckout')->isHorisontalLayout()) {
            if ($head = $this->getLayout()->getBlock('head')) {
                $head->addItem('skin_css', 'css/onestepcheckout/horizontal.css');
                if(Mage::helper('onestepcheckout')->isResponsive()) {
                    $head->addItem('skin_css', 'css/onestepcheckout/horizontal-responsive.css');
                }
            }
            $this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');
        } elseif (Mage::helper('onestepcheckout')->isStandartLayout()) {
            if ($head = $this->getLayout()->getBlock('head')) {
                $head->addItem('skin_css', 'css/onestepcheckout/standard.css');
            }
            if(Mage::helper('onestepcheckout')->isResponsive()) {
                $head->addItem('skin_css', 'css/onestepcheckout/standard-responsive.css');
            }
        }
        return parent::renderLayout($output);
    }
}
