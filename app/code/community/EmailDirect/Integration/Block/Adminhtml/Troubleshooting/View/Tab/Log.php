<?php

class EmailDirect_Integration_Block_Adminhtml_Troubleshooting_View_Tab_Log
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	
	public function getTabLabel()
	{
		return Mage::helper('emaildirect')->__('Log File');
	}

	public function getTabTitle()
	{
		return Mage::helper('emaildirect')->__('Log file');
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
		$this->setTemplate('emaildirect/troubleshooting/view/tab/log.phtml');
	}
	
	public function getLogFile()
	{
		return Mage::helper('emaildirect/troubleshooting')->getLogFileContents();
	}
}
