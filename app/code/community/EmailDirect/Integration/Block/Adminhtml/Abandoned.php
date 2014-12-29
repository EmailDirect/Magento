<?php

class EmailDirect_Integration_Block_Adminhtml_Abandoned extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_abandoned';
		$this->_blockGroup = 'emaildirect';
		
		$status = Mage::helper('emaildirect')->getAbandonedStatus();
		
		if ($status['enabled'])
		{
			$this->_headerText = "Abandoned Carts - Cron Last Run: " . Mage::helper('emaildirect')->getCronLastRunHtml();
			
			$label = 'Run now on all stores';
			
			if (Mage::app()->isSingleStoreMode())
				$label = 'Run now';
			
			$this->addButton('refresh', array(
				'label'     => Mage::helper('emaildirect')->__('Refresh'),
				'onclick'   => 'abandonedGridJsObject.reload();return false;',
			));
			
			$this->_addButton('run_now', array(
				'label'	 	=> Mage::helper('emaildirect')->__($label),
				'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/run') .'\')',
			));
		}
		else
			$this->_headerText = "Abandoned Carts - <span class='ab_ng'>Disabled</span>";
		
		$data = Mage::helper('emaildirect')->getCronLastRun();
		
		if ($data['class'] == 'ab_ng')
			Mage::getSingleton('adminhtml/session')->addWarning("There appears to be a problem with your cron settings.  Please make sure that cron is running so that Abandoned Carts can be processed.");
		
		parent::__construct();
		$this->_removeButton('add');
	}
	
	public function getStatusHtml()
	{
		return $this->getChildHtml('abandoned_status');
	}
	 
	protected function _prepareLayout()
	{
		$this->setChild('store_switcher',
			$this->getLayout()->createBlock('adminhtml/store_switcher')
				->setUseConfirm(false)
				->setSwitchUrl($this->getUrl('*/*/*', array('store'=>null)))
				->setTemplate('report/store/switcher.phtml')
		);
		
		$this->setChild('abandoned_status',
			$this->getLayout()->createBlock('emaildirect/adminhtml_abandoned_status')
				->setTemplate('emaildirect/abandoned/status.phtml')
		);

		return parent::_prepareLayout();
	}
	
	public function getGridHtml()
	{
		return $this->getStatusHtml() . parent::getGridHtml();
	}
}
