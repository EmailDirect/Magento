<?php

class EmailDirect_Integration_Block_Adminhtml_Abandoned_Details extends Mage_Adminhtml_Block_Widget_Form_Container
{

	public function __construct()
	{
		$this->_blockGroup = "emaildirect";
		$this->_controller  = 'adminhtml_abandoned';
		$this->_mode        = 'details';

		parent::__construct();
		
		if (!$this->isGuestCustomer())
		{
			$this->_addButton('view_customer', array(
                'label' => Mage::helper('emaildirect')->__('View Customer'),
                'onclick' => 'setLocation(\'' . $this->getUrl('adminhtml/customer/edit', array('id'=> $this->getCustomerId(), 'active_tab'=>'cart')) . '\')',
            ), 0);
		}
		
		$this->_removeButton('delete');
		$this->_removeButton('reset');
		$this->_removeButton('save');
		$this->setId('abandoned_details');
	}
	
	public function getCustomerId()
	{
		$abandoned_cart = Mage::helper('emaildirect/diagnostic')->getAbandonedCart();
		
		return $abandoned_cart->getCustomerId();
	}
	
	public function isGuestCustomer()
	{
		$abandoned_cart = Mage::helper('emaildirect/diagnostic')->getAbandonedCart();
		
		return $abandoned_cart->getCustomerGroupId() == 0;
	}

	public function getHeaderText()
	{
		$abandoned_cart = Mage::helper('emaildirect/diagnostic')->getAbandonedCart();
		
		$name = "";
		$additional = "";
		
		if ($abandoned_cart->getCustomerGroupId() == 0)
		{
			// Guest
			$email = $abandoned_cart->getEmail();
			
			$additional = "Guest Customer ({$email})";
		}
		else
		{
			$email = $abandoned_cart->getCustomerEmail();
			$name = $abandoned_cart->getCustomerFirstname() . " " . $abandoned_cart->getCustomerLastname();
			$additional = "{$name} ({$email})";
		}
		
		$date = Mage::helper('core')->formatTime($abandoned_cart->getUpdatedAt(), 'medium', true);
		
		return Mage::helper('emaildirect')->__('Abandoned Cart - ' . $additional . " - Abandoned On: " . $date);
	}

	public function getUrl($params='', $params2=array())
	{
		return parent::getUrl($params, $params2);
	}
	
	public function getDownloadUrl()
	{
		return $this->getUrl('ed_integration/admin_troubleshooting/download/');
	}
	
	public function getBackUrl()
	{
		return Mage::helper('emaildirect')->getAdminUrl('*/*/index');
	}
}
