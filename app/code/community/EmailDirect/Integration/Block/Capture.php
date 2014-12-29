<?php

class EmailDirect_Integration_Block_Capture extends Mage_Core_Block_Template
{
	private $_active = false;
	
	public function __construct()
	{
		if ($this->helper('emaildirect')->canCapture())
			$this->_active = true;
	}
	
	protected function _toHtml()
	{
		if (!$this->_active)
			return "";
		
		return parent::_toHtml();
	}
	
	public function getFields()
	{
		$fields = array(
			'login-email',
			'newsletter',
			'email',
			'billing:email'
		);
		
		return $fields;
	}
	
	public function getCaptureUrl()
	{
		if (Mage::app()->getStore()->isCurrentlySecure()) 
		{
			return Mage::getUrl('emaildirect/capture',array(
    							'_secure' => true
 								));
		}
		
		return Mage::getUrl('emaildirect/capture');
	}
}