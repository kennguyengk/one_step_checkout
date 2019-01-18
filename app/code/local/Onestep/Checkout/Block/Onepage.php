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
 
class Onestep_Checkout_Block_Onepage extends Mage_Checkout_Block_Onepage
{
    protected $_address = null;
    protected $_messagesHtml = '';

    protected function _getStepCodes()
    {
        return array('billing' => 'one', 'shipping' => 'one', 'shipping_method' => 'two', 'payment' => 'two', 'review' => 'two');
    }

    public function getSteps()
    {
        $steps = array();
        $stepCodes = $this->_getStepCodes();

        foreach ($stepCodes as $step => $column) {
            if(!isset($steps[$column])){
                $steps[$column] = array();
            }
            $steps[$column][$step] = $this->getCheckout()->getStepData($step);
        }

        return $steps;
    }

    public function getStepsJson()
    {
        $steps = array();
        $codes = $this->_getStepCodes();
        foreach($codes as $code => $column){
            if(!isset($steps[$code])){
                $steps[$code] = array('selector' => ".content-{$code}", 'url' => $this->getStepUrl($code));
            }
            $current = false;
            foreach($codes as $loadCodeKey => $column){
                if($column !== 'one' && $current){
                    $steps[$code]['blocks'][$loadCodeKey]  = ".content-{$loadCodeKey}";
                }
                if($loadCodeKey == $code){
                    $current = true;
                }
            }
        }

        return Mage::helper('core')->jsonEncode($steps);
    }

    public function getStepUrl($stepId)
    {
        $stepUrls = array(
            'billing' => $this->getUrl('checkout/onepage/saveBilling'),
            'shipping' => $this->getUrl('checkout/onepage/saveShipping'),
            'shipping_method' => $this->getUrl('checkout/onepage/saveShippingMethod'),
            'payment' => $this->getUrl('checkout/onepage/savePayment'),
        );
        return isset($stepUrls[$stepId]) ? $stepUrls[$stepId] : '';
    }

    public function getMessagesHtml()
    {
        $messages = '';
        $block = $this->getLayout()->getMessagesBlock();
        foreach($block->getMessages('error') as $message){
            $messages .= $messages ? '<br/>' . $message->getText() : $message->getText();
        }
        return $messages;
    }

    public function hasMessages()
    {
        $block = $this->getLayout()->getMessagesBlock();
        $messages = Mage::getSingleton('customer/session')->getMessages(true);
        $block->addMessages($messages);
        $messages = Mage::getSingleton('checkout/session')->getMessages(true);
        $block->addMessages($messages);
        return count($block->getMessages('error'));
    }

    protected function _beforeToHtml()
    {
        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        $payment = $quote->getPayment()->getData('method');
        if($payment == 'authorizenet_directpost'){
            $quote->removePayment();
        }
        return parent::_beforeToHtml();
    }
}
