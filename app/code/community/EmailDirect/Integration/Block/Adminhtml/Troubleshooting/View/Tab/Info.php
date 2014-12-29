<?php

class EmailDirect_Integration_Block_Adminhtml_Troubleshooting_View_Tab_Info
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	public function getTabLabel()
	{
		return Mage::helper('emaildirect')->__('Configuration Info');
	}

	public function getTabTitle()
	{
		return Mage::helper('emaildirect')->__('Configuration Information');
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
		$this->setTemplate('emaildirect/troubleshooting/view/tab/info.phtml');
	}
	
	public function getEnvironmentInfo()
	{
		return Mage::helper('emaildirect/troubleshooting')->getEnvironmentInfo();
	}
	
	public function getConfigurationInfo()
	{
		return Mage::helper('emaildirect/troubleshooting')->getConfigurationInfo();
	}
}
