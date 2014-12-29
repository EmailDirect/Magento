<?php

class EmailDirect_Integration_Block_Adminhtml_Abandoned_Details_Tab_Cart
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	public function getTabLabel()
	{
		return Mage::helper('emaildirect')->__('Cart Items');
	}

	public function getTabTitle()
	{
		return Mage::helper('emaildirect')->__('Cart Items');
	}

	public function canShowTab()
	{
		return true;
	}

	public function isHidden()
	{
		return false;
	}
	
	public function getCartItems()
	{
		return Mage::helper('emaildirect/diagnostic')->getCartItems();
	}

	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('emaildirect/abandoned/details/tab/cart.phtml');
	}
}