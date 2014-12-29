<?php

class EmailDirect_Integration_Block_Signup extends Mage_Core_Block_Template
{	
	protected function _toHtml()
	{
		if (!$this->helper('emaildirect')->canEdirect() || !$this->helper('emaildirect')->config('signup_enabled'))
			return "";
		
		return parent::_toHtml();
	}
	
	public function isSignupEnabled()
	{
		return $this->helper('emaildirect')->isSignupEnabled() ? 'true' : 'false';
	}
	
	public function getSignupDelay()
	{
		return (int)$this->helper('emaildirect')->config('signup_delay') * 1000;
	}
	
	public function getSignupCheckUrl()
	{
		return $this->getUrl('emaildirect/signup/check');
	}
}	