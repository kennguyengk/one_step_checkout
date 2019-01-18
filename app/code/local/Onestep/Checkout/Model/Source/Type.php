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
 
class Onestep_Checkout_Model_Source_Type
{

    public function toOptionArray()
    {
        return array(
            array('value' => Onestep_Checkout_Helper_Data::CHECKOUT_LAYOUT_STANDARD, 'label'=>Mage::helper('onestepcheckout')->__('Standard vertical steps')),
            array('value' => Onestep_Checkout_Helper_Data::CHECKOUT_LAYOUT_HORIZONTAL, 'label'=>Mage::helper('onestepcheckout')->__('Tabbed steps')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            Onestep_Checkout_Helper_Data::CHECKOUT_LAYOUT_STANDARD => Mage::helper('onestepcheckout')->__('Standard vertical steps'),
            Onestep_Checkout_Helper_Data::CHECKOUT_LAYOUT_HORIZONTAL => Mage::helper('onestepcheckout')->__('Tabbed steps'),
        );
    }

}
