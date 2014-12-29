<?php

class EmailDirect_Integration_Block_Adminhtml_Abandoned_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('abandoned_details_tabs');
		$this->setDestElementId('abandoned_details');
		$this->setTitle(Mage::helper('emaildirect')->__('Abandoned Cart Details'));
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
