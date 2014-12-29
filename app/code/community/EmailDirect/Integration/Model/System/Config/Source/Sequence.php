<?php

class EmailDirect_Integration_Model_System_Config_Source_Sequence
{
	public function toOptionArray()
	{
		Mage::helper('emaildirect/troubleshooting')->setLogArea(EmailDirect_Integration_Helper_Troubleshooting::CONFIG);
		Mage::helper('emaildirect/troubleshooting')->setLogLevel(EmailDirect_Integration_Helper_Troubleshooting::LOG_LEVEL_LOW);
		
		$fields = Mage::helper('emaildirect')->getEmailDirectColumnOptions();
		
		$options =  array();
		
		foreach ($fields as $field)
		{
				$options[] = array(
								'value' => $field,
								'label' => $field);
		}
		return $options;
	}
}