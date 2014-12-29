<?php

class EmailDirect_Integration_Model_System_Config_Source_Publication
{
	public function toOptionArray()
	{
		Mage::helper('emaildirect/troubleshooting')->setLogArea(EmailDirect_Integration_Helper_Troubleshooting::CONFIG);
		Mage::helper('emaildirect/troubleshooting')->setLogLevel(EmailDirect_Integration_Helper_Troubleshooting::LOG_LEVEL_LOW);
		
		$publications = Mage::getSingleton('emaildirect/wrapper_publications')->getPublications();
		
		$options =  array();
		
		foreach($publications as $publication)
		{
			if($publication['active'])
				$options[] = array(
								'value' => $publication['id'],
								'label' => $publication['name']);
		}
		return $options;
	}
}