<?php

class EmailDirect_Integration_Model_Observer_Abstract 
{
	protected $_helper = null;
	protected $_logger = null;
	protected $_date_format = EmailDirect_Integration_Helper_Data::DATE_FORMAT;
	
	public function __construct()
	{
		$this->_helper = Mage::helper('emaildirect');
		$this->_logger = Mage::helper('emaildirect/troubleshooting');
	}
	
	protected function _log($data, $prefix = "")
	{
		$this->_logger->log($data, $prefix);
	}
	
	protected function _logException($e)
	{
		$this->_logger->logException($e);
	}
	
	protected function _logReason($data)
	{
		$this->_logger->logReason($data);
	}
	
	protected function _setLogArea($area)
	{
		$this->_logger->setLogArea($area);
	}
}