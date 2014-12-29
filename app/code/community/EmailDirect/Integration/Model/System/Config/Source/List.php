<?php

class EmailDirect_Integration_Model_System_Config_Source_List
{
	public function toOptionArray()
	{
		Mage::helper('emaildirect/troubleshooting')->setLogArea(EmailDirect_Integration_Helper_Troubleshooting::CONFIG);
		Mage::helper('emaildirect/troubleshooting')->setLogLevel(EmailDirect_Integration_Helper_Troubleshooting::LOG_LEVEL_LOW);
		
		$lists = Mage::getSingleton('emaildirect/wrapper_lists')->getLists();
		
		$options =  array();
		$options[] = array (
						'value' => -1,
						'label' => '--select--',
					);
		
		foreach($lists as $list)
		{
			if($list['active'])
				$options[] = array(
								'value' => $list['id'],
								'label' => $list['name']);
		}
		return $options;
	}
}
