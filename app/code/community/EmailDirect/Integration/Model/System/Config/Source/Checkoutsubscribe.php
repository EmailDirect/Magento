<?php

class EmailDirect_Integration_Model_System_Config_Source_Checkoutsubscribe
{

	public function toOptionArray()
	{
		return array(
            array('value' => 1, 'label' => Mage::helper('emaildirect')->__('Enabled - Checked by default')),
            array('value' => 2, 'label' => Mage::helper('emaildirect')->__('Enabled - Not Checked by default')),
            array('value' => 3, 'label' => Mage::helper('emaildirect')->__('Enabled - Force subscription')),
            array('value' => 0, 'label' => Mage::helper('emaildirect')->__('-- Disabled --'))
			);
	}
}