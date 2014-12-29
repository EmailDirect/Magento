<?php

class EmailDirect_Integration_Model_System_Config_Source_Statuses
{	
	public function toOptionArray()
	{
		$sales_config = Mage::getSingleton('sales/order_config');
		
		$statuses = $sales_config->getStatuses();
		
		$options = array();

		foreach($statuses as $value => $label)
		{
			$options[] = array(
								'value' => $value,
								'label' => $label);
		}
		return $options;
	}
}