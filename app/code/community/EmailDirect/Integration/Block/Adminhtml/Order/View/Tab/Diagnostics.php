<?php

class EmailDirect_Integration_Block_Adminhtml_Order_View_Tab_Diagnostics
    extends Mage_Adminhtml_Block_Sales_Order_Abstract
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	
	public function getDiagnosticOptions()
	{
		$options = array(
				array('code' => 'order_custom_fields', 'label' => 'Custom Fields', 'description' => 'Get the custom fields'),
				array('code' => 'order_last_items', 'label' => 'Order Items', 'description' => 'Test of getting the order items to send'),
				array('code' => 'order_related_items', 'label' => 'Related Order Items', 'description' => 'Test of the Related Order Items'),
				array('code' => 'order_full_request', 'label' => 'Full Order Request', 'description' => 'Diagnose the request that will be sent to EmailDirect for this Order.'),
				
			);
			
		$full_order = array(
							'code' => 'order_full', 
							'label' => 'Full Order (Request and Response)',
							'description' => 'Diagnose the request and response for this Order.', 
							'note' => 'This will send the order to EmailDirect but it will not mark it as sent.' 
							);
		
		if (!$this->isEmailDirectEnabled() || !$this->isEmailDirectSetup() || !$this->isSendOrdersEnabled())
		{
			$full_order['disabled'] = true;
			$full_order['disabled_reason'] = 'EmailDirect must be enabled (and setup to send orders) to perform this diagnostic.';
		}
		
		$options[] = $full_order;
		return $options;
	}
	
	public function getItemId()
	{
		return $this->getOrder()->getId();
	}
	
	public function getStoreId()
	{
		return $this->getOrder()->getStoreId();
	}
	
	public function isEmailDirectEnabled()
	{
		return Mage::helper('emaildirect')->config('active',$this->getOrderStore()) == 1;
	}
	
	public function isEmailDirectSetup()
	{
		return Mage::helper('emaildirect')->config('setup',$this->getOrderStore()) == 1;
	}

	public function isSendOrdersEnabled()
	{
		return Mage::helper('emaildirect')->config('sendorder',$this->getOrderStore()) == 1;
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
		//$this->setTemplate('emaildirect/order/view/tab/diagnostics.phtml');
	}
}
