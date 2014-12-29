<?php

class EmailDirect_Integration_Model_System_Config_Source_States
{
	private function getStates()
	{
		$states = array();
		
		$config_states = Mage::getConfig()->getNode('global/sales/order/states');
		
		foreach ($config_states->children() as $state)
		{
			$label = (string) $state->label;
			$states[$state->getName()] = Mage::helper('sales')->__($label);
		}
        
		return $states;
	}
	
	public function toOptionArray()
	{
		$sales_config = Mage::getSingleton('sales/order_config');
		
		if (!method_exists($sales_config,'getStates'))
			$states = $this->getStates();
		else
			$states = $sales_config->getStates();
		
		$options = array();

		foreach($states as $value => $label)
		{
			$options[] = array(
								'value' => $value,
								'label' => $label);
		}
		return $options;
	}
}