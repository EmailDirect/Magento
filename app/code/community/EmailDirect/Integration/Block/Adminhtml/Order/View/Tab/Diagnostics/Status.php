<?php

class EmailDirect_Integration_Block_Adminhtml_Order_View_Tab_Diagnostics_Status extends EmailDirect_Integration_Block_Adminhtml_Diagnostics_Status_Abstract
{
	public function isSendOrdersEnabled()
	{
		return $this->_helper->config('sendorder',$this->_helper->getCurrentStore()) == 1;
	}

	public function canSendOrders()
	{
		if (!$this->isEmailDirectEnabled() || !$this->isEmailDirectSetup() || !$this->isSendOrdersEnabled())
			return false;
		
		return true;
	}
	
	public function getEmailDirectDate()
	{
		$ed_order = Mage::getModel('emaildirect/order')->loadByOrderId($this->getOrder()->getId());
		
		if ($ed_order != null && $ed_order->getDateSent() != null)
			return $ed_order->getDateSent();
		
		return null;
	}
	
	public function getStateList()
	{
		$state_options = Mage::getSingleton("emaildirect/system_config_source_states")->toOptionArray();
		
		$set_states = Mage::helper('emaildirect')->config('send_states');
		$state_list = explode(",",$set_states);
		
		$display_list = array();
		
		foreach ($state_options as $option)
		{
			if (in_array($option['value'],$state_list))
				$display_list[] = $option['label'];
		}
		
		return $display_list;
	}
	
	public function getStatusList()
	{
		$sales_config = Mage::getSingleton('sales/order_config');
		
		$statuses = $sales_config->getStatuses();
		
		$set_statuses = Mage::helper('emaildirect')->config('send_statuses');
		$status_list = explode(",",$set_statuses);
		
		$display_list = array();
		
		foreach ($statuses as $value => $label)
		{
			if (in_array($value,$status_list))
				$display_list[] = $label;
		}
		
		return $display_list;
	}
	
	public function getSendField()
	{
		return Mage::helper('emaildirect')->config('send_field');
	}
	
	public function getOrderState()
	{
		$order = $this->getOrder();
		
		return $order->getConfig()->getStateLabel($order->getState());
	}
	
	public function getOrder()
	{
		return Mage::registry('current_order');
	}
}
