<?php

class EmailDirect_Integration_Block_Adminhtml_Troubleshooting_View_Tab_Download
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    
   public function getTabLabel()
	{
		return Mage::helper('emaildirect')->__('Download Report');
	}

	public function getTabTitle()
	{
		return Mage::helper('emaildirect')->__('Download Report');
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
		$this->setTemplate('emaildirect/troubleshooting/view/tab/download.phtml');
	}
	
	public function getTroubleEmail()
	{
		return Mage::helper('emaildirect')->troubleConfig('email');
	}
	
	public function getTroubleSubject()
	{
		return Mage::helper('emaildirect')->troubleConfig('subject');
	}
}
