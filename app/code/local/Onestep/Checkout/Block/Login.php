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
 
class Onestep_Checkout_Block_Login extends Mage_Customer_Block_Form_Login
{
    public function __construct()
    {
        parent::__construct();
        if(!$this->isCustomerLoggedIn()){
            Mage::getSingleton('customer/session')->getAfterAuthUrl(Mage::helper('core/url')->getCurrentUrl());
        }
    }

    public function getPostActionUrl()
    {
        return $this->getUrl('customer/account/loginPost');
    }

    public function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    public function getForgotPasswordActionUrl()
    {
        return $this->getUrl('customer/account/forgotPasswordPost');
    }

    protected function _prepareLayout()
    {
        return Mage_Core_Block_Template::_prepareLayout();
    }
}
