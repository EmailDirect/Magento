<?php

class EmailDirect_Integration_Block_Adminhtml_Troubleshooting_View_Tab_Settings
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	private $_helper = null;	
	 
	public function getTabLabel()
	{
		return Mage::helper('emaildirect')->__('Settings');
	}

	public function getTabTitle()
	{
		return Mage::helper('emaildirect')->__('Settings');
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
		$this->_helper = Mage::helper('emaildirect/troubleshooting');
		parent::__construct();
		$this->setTemplate('emaildirect/troubleshooting/view/tab/settings.phtml');
	}
	
	public function getLoggingStatus()
	{
		return $this->_helper->getLoggingStatus();
	}
	
	public function getDurationOptions()
	{
		return array(10 => "10 Minutes", 20 => "20 Minutes", 30 => "30 Minutes", 60 => "1 Hour", 1440 => "1 Day");
	}
	
	public function getSetting($setting)
	{
		return Mage::helper('emaildirect/troubleshooting')->config($setting);
	}
	
	public function getArraySetting($setting)
	{
		return Mage::helper('emaildirect/troubleshooting')->arrayConfig($setting);
	}
	
	public function getCurrentIP()
	{
		return Mage::helper('core/http')->getRemoteAddr();
	}
}
