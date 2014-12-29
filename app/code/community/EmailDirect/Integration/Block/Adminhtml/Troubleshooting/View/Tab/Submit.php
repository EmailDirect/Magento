<?php

class EmailDirect_Integration_Block_Adminhtml_Troubleshooting_View_Tab_Submit
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{ 
	public function getTabLabel()
	{
		return Mage::helper('emaildirect')->__('Send to EmailDirect');
	}

	public function getTabTitle()
	{
		return Mage::helper('emaildirect')->__('Send Information to EmailDirect');
	}

	public function canShowTab()
	{
		return true;
	}

	public function isHidden()
	{
		return false;
	}

	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('emaildirect/troubleshooting/view/tab/submit.phtml');
	}
	
	public function getLogFileSize()
	{
		return Mage::helper('emaildirect/troubleshooting')->getLogFileSize();
	}
	
	public function getMaxLogFileSize()
	{
		return Mage::helper('emaildirect')->formatSize(Mage::helper('emaildirect/troubleshooting')->getMaxLogFileSize());
	}
	
	public function isLogFileTooLarge()
	{
		return Mage::helper('emaildirect/troubleshooting')->isLogFileTooLarge();
	}
	
	public function getTroubleEmail()
	{
		return Mage::helper('emaildirect')->troubleConfig('email');
	}
	
	public function getLogFileName()
	{
		return Mage::helper('emaildirect/troubleshooting')->getLogFileName();
	}
}