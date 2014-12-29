<?php

class EmailDirect_Integration_Block_Customer_Account_Dashboard_Info extends Mage_Customer_Block_Account_Dashboard_Info 
{
	protected function _toHtml()
	{
		if (!Mage::helper('emaildirect')->canEDirect())
			return parent::_toHtml();
		
		return parent::_toHtml();
    }
	
	public function getSubscriptions()
	{
		return Mage::helper('emaildirect')->getSubscriptions($this->_getEmail());
	}
	
	/**
	 * Retrieve email from Customer object in session
	 *
	 * @return string Email address
	 */
	protected function _getEmail()
	{
		return $this->helper('customer')->getCustomer()->getEmail();
	}
}