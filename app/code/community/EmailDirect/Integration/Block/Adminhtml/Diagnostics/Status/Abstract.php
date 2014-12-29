<?php

class EmailDirect_Integration_Block_Adminhtml_Diagnostics_Status_Abstract extends Mage_Adminhtml_Block_Template
{
	protected $_helper = null;
	
	public function _construct()
	{
		$this->_helper = Mage::helper('emaildirect');
		parent::_construct();
	}
	
	public function isEmailDirectEnabled()
	{
		return $this->_helper->config('active',$this->_helper->getCurrentStore()) == 1;
	}
	
	public function isEmailDirectSetup()
	{
		return $this->_helper->config('setup',$this->_helper->getCurrentStore()) == 1;
	}
}