<?php

class EmailDirect_Integration_Block_Adminhtml_Troubleshooting_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('troubleshooting_view_tabs');
		$this->setDestElementId('troubleshooting_view');
		$this->setTitle(Mage::helper('emaildirect')->__('Troubleshooting'));
	}
	
	protected function _beforeToHtml()
	{
		$this->_updateActiveTab();
		return parent::_beforeToHtml();
	}
	
	protected function _updateActiveTab()
	{
		$tabId = $this->getRequest()->getParam('tab');
		if ($tabId)
		{
			$tabId = preg_replace("#{$this->getId()}_#", '', $tabId);
			if ($tabId)
			{
				$this->setActiveTab($tabId);
			}
		}
	}
}
