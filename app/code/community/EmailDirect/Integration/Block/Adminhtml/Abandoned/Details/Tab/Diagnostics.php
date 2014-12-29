<?php

class EmailDirect_Integration_Block_Adminhtml_Abandoned_Details_Tab_Diagnostics
    extends Mage_Adminhtml_Block_Sales_Order_Abstract
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	
	public function getDiagnosticOptions()
	{
		$options = array(
				array('code' => 'abandoned_url', 'label' => 'Restore Cart URL', 'description' => 'Get the URL that customers will use to restore their carts.'),
				array('code' => 'abandoned_custom_fields', 'label' => 'Custom Fields', 'description' => 'Custom Fields'),
				array('code' => 'abandoned_request', 'label' => 'Abandoned Request', 'description' => 'Custom Fields'),
			);
			
		$full = array(
							'code' => 'abandoned_request_full', 
							'label' => 'Abandoned Request and Response',
							'description' => 'Diagnose the request and response for this Cart.', 
							'note' => 'This will send the cart to EmailDirect but it will not mark it as sent.' 
							);
		
		if (!$this->isEmailDirectEnabled() || !$this->isEmailDirectSetup() || !$this->isAbandonedEnabled())
		{
			$full['disabled'] = true;
			$full['disabled_reason'] = 'EmailDirect must be enabled (and setup to send abandoned carts) to perform this diagnostic.';
		}
		
		$options[] = $full;
		
		return $options;
	}
	
	public function getStoreId()
	{
		return Mage::app()->getRequest()->getParam('store_id');
	}
	
	public function getStore()
	{
		$store_id = $this->getStoreId();
		
		return Mage::getModel('core/store')->load($store_id);
	}
	
	public function getItemId()
	{
		return Mage::app()->getRequest()->getParam('id');
	}
	
	public function isEmailDirectEnabled()
	{
		return Mage::helper('emaildirect')->config('active',$this->getOrderStore()) == 1;
	}
	
	public function isEmailDirectSetup()
	{
		return Mage::helper('emaildirect')->config('setup',$this->getOrderStore()) == 1;
	}
	
	public function isAbandonedEnabled()
	{
		return Mage::helper('emaildirect')->getAbandonedEnabled();
	}
	
	public function getTabLabel()
	{
		return Mage::helper('emaildirect')->__('EmailDirect Diagnostics');
	}

	public function getTabTitle()
	{
		return Mage::helper('emaildirect')->__('EmailDirect Diagnostics');
	}

	public function canShowTab()
	{
		return Mage::helper('emaildirect/troubleshooting')->isDiagnosticEnabled();
	}

	public function isHidden()
	{
		return false;
	}

	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('emaildirect/order/view/tab/diagnostics.phtml');
	}
}