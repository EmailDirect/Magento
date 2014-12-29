<?php

class EmailDirect_Integration_Model_System_Config_Source_Source 
{
	public function toOptionArray()
	{
		Mage::helper('emaildirect/troubleshooting')->setLogArea(EmailDirect_Integration_Helper_Troubleshooting::CONFIG);
		Mage::helper('emaildirect/troubleshooting')->setLogLevel(EmailDirect_Integration_Helper_Troubleshooting::LOG_LEVEL_LOW);
		
		$sources = Mage::getSingleton('emaildirect/wrapper_sources')->getSources();
		
		$options =  array();
		
		foreach($sources as $source)
		{
			if($source['active'])
				$options[] = array(
								'value' => $source['id'],
								'label' => $source['name']);
		}
		return $options;
	}
}