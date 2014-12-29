<?php

class EmailDirect_Integration_Model_System_Config_Source_Abandoned extends Varien_Data_Form_Abstract
{
	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value' => 0, 'label' => Mage::helper('emaildirect')->__('Do not send through Magento')),
			array('value' => 1, 'label' => Mage::helper('emaildirect')->__('1 Day')),
			array('value' => 2, 'label' => Mage::helper('emaildirect')->__('2 Days')),
			array('value' => 3, 'label' => Mage::helper('emaildirect')->__('3 Days')),
			array('value' => 4, 'label' => Mage::helper('emaildirect')->__('4 Days')),
			array('value' => 5, 'label' => Mage::helper('emaildirect')->__('5 Days')),
			array('value' => 6, 'label' => Mage::helper('emaildirect')->__('6 Days')),
			array('value' => 7, 'label' => Mage::helper('emaildirect')->__('7 Days')),
			array('value' => 8, 'label' => Mage::helper('emaildirect')->__('8 Days')),
			array('value' => 9, 'label' => Mage::helper('emaildirect')->__('9 Days')),
			array('value' => 10, 'label' => Mage::helper('emaildirect')->__('10 Days')),
			array('value' => 11, 'label' => Mage::helper('emaildirect')->__('11 Days')),
			array('value' => 12, 'label' => Mage::helper('emaildirect')->__('12 Days')),
			array('value' => 13, 'label' => Mage::helper('emaildirect')->__('13 Days')),
			array('value' => 14, 'label' => Mage::helper('emaildirect')->__('14 Days')),
			array('value' => 15, 'label' => Mage::helper('emaildirect')->__('15 Days')),
		);
	}
}