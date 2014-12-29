<?php

class EmailDirect_Integration_Block_Adminhtml_System_Convert_Profile_Export_Orders extends EmailDirect_Integration_Block_Adminhtml_System_Convert_Profile_Export
{
   public function __construct()
	{
		$this->_record_name = "order";
		parent::__construct();
	}
	
	public function getExportType()
	{
		return "order";
	}
	
	public function getCollection()
	{
		$from = Mage::app()->getRequest()->getParam('export_from');
		$to = Mage::app()->getRequest()->getParam('export_to');
		$include = Mage::app()->getRequest()->getParam('include_already_sent');
		$store = Mage::app()->getRequest()->getParam('store');
		
		$orders = Mage::helper('emaildirect')->getOrderExportCollection($from, $to, $include, $store);
		
		return $orders;
	}
}