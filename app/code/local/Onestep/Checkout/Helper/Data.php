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
 
class Onestep_Checkout_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CHECKOUT_LAYOUT_STANDARD = 1;
    const CHECKOUT_LAYOUT_HORIZONTAL = 2;
    const CHECKOUT_LAYOUT_ONE_STEP = 3;


    public function isStandartLayout()
    {
        return Mage::getStoreConfig('onestepcheckout/settings/type') == self::CHECKOUT_LAYOUT_STANDARD;
    }

    public function isHorisontalLayout()
    {
        return Mage::getStoreConfig('onestepcheckout/settings/type') == self::CHECKOUT_LAYOUT_HORIZONTAL;
    }

    public function isOneStepLayout()
    {
        return Mage::getStoreConfig('onestepcheckout/settings/type') == self::CHECKOUT_LAYOUT_ONE_STEP;
    }

    public function isResponsive()
    {
        return Mage::getStoreConfig('onestepcheckout/settings/responsive');
    }

}
