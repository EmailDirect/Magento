<?php

class EmailDirect_Integration_Block_Adminhtml_System_Convert_Profile_Export_Products extends EmailDirect_Integration_Block_Adminhtml_System_Convert_Profile_Export
{
	public function __construct()
	{
		$this->_record_name = "product";
		parent::__construct();
	}
	
	public function getExportType()
	{
		return "product";
	}
	
	public function getCollection()
	{
		$include = Mage::app()->getRequest()->getParam('include_disabled') == 1;
		$store = Mage::app()->getRequest()->getParam('store');
		
		return Mage::helper('emaildirect')->getProductExportCollection($include, $store);
	}
}