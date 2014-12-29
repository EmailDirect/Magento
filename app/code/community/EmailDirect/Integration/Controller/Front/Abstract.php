<?php

class EmailDirect_Integration_Controller_Front_Abstract extends Mage_Core_Controller_Front_Action
{
	protected $_helper = null;
	protected $_logger = null;
	
	public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
	{
		$this->_helper = Mage::helper('emaildirect');
		$this->_logger = Mage::helper('emaildirect/troubleshooting');
		
		return parent::__construct($request, $response, $invokeArgs);
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