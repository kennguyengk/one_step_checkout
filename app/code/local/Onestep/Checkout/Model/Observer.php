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
 
class Onestep_Checkout_Model_Observer
{
    public function getDefaultCheckoutUpdate($observer)
    {
        $block = $observer->getEvent()->getBlock();

        switch ($block->getNameInLayout()) {
            case 'checkout.onepage.review':
                $this->_addReviewAdditionalHtml($block);
                return;
            default:
                return;
        }
    }

    protected function _addReviewAdditionalHtml($block)
    {
        try {
            $payment = Mage::getSingleton('checkout/type_onepage')
                ->getQuote()
                ->getPayment();
            $payment->getMethodInstance();
        } catch (Exception $e) {
            return;
        }
        $layout = $this->_prepareLayout('checkout_onepage_review');
        if ($info = $block->getChild('info')) {
            $info->setChild("items_before", $layout->getBlock("checkout.onepage.review.info.items.before"));
            $info->setChild("items_after", $layout->getBlock("checkout.onepage.review.info.items.after"));
            $info->setChild("button", $layout->getBlock("checkout.onepage.review.button"));
        }
    }

    protected function _prepareLayout($updateHandle)
    {
        $layout = Mage::app()->getLayout();
        $update = $layout->getUpdate();
        $update->load($updateHandle);
        $layout->generateXml();
        $layout->generateBlocks();
        return $layout;
    }


    public function productAfterAddToCart($observer)
    {
        if (!$observer->getEvent()->getIsAjax()) {
            if (Mage::getStoreConfig('onestepcheckout/settings/bypass_cart', Mage::app()->getStore()->getId())) {
                $response = $observer->getResponse();
                $response->setRedirect(Mage::getUrl('checkout/onepage'));
                Mage::getSingleton('checkout/session')->setNoCartRedirect(true);
            }
        }
    }

    public function controllerActionLayoutLoadBefore(Varien_Event_Observer $observer)
    {
        /** @var $layout Mage_Core_Model_Layout */

        if (Mage::app()->getRequest()->getRouteName() == 'onestepcheckout' && Mage::app()->getRequest()->getActionName() != "success") {
            $update = $update = Mage::getSingleton('core/layout')->getUpdate();
            if (Mage::helper('onestepcheckout')->isHorisontalLayout()) {
                $update->addHandle('onestepcheckout_horizontal');
            } elseif (Mage::helper('onestepcheckout')->isStandartLayout()) {
      
            }
        }
        return $this;
    }

}
