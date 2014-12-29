<?php

class EmailDirect_Integration_Block_Adminhtml_Troubleshooting_View_Tab_Help
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	private $_columns = 3;
	
	public function getTabLabel()
	{
		return Mage::helper('emaildirect')->__('Help');
	}

	public function getTabTitle()
	{
		return Mage::helper('emaildirect')->__('Help');
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
		$this->setTemplate('emaildirect/troubleshooting/view/tab/help.phtml');
	}
	
	private function getFields($data)
	{
		$fields = array();
		
		foreach ($data as $key => $value)
		{
			if (!is_object($value) && !is_array($value))
				$fields[] = $key;
		}

		asort($fields);
		return array_chunk($fields, ceil(count($fields) / $this->_columns));
	}
	
	public function getCustomerFields()
	{
		$customer = Mage::getModel('customer/customer')->getCollection()->getFirstItem();
		
		$customer = Mage::getModel('customer/customer')->load($customer->getId());
		
		return $this->getFields($customer->getData());
	}
	
	public function getAddressFields()
	{
		$address = Mage::getModel('customer/address')->getCollection()->getFirstItem();
		
		$address = Mage::getModel('customer/address')->load($address->getId());
		
		return $this->getFields($address->getData());
	}
	
	public function getShippingFields()
	{
		//$address = Mage::getModel('customer/address')->getCollection()->getFirstItem();
		
		//$address = Mage::getModel('customer/address')->load($address->getId());
		
		$shipping_fields = array('Shipping Code' => 1, 'Shipping Description' => 1, 'Tracking Carrier Code' => 1, 'Tracking Title' => 1, 'Tracking Number' => 1);
		
		return $this->getFields($shipping_fields);
	}
}
