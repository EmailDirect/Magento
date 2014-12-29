<?php

class EmailDirect_Integration_Helper_Diagnostic extends Mage_Core_Helper_Abstract
{
	private $_quote = null;
	private $_cart = null;
	
	public function getCurrentStoreId()
	{
		$order = $this->getOrder();
		if ($order)
			return $order->getStore()->getId();
		
		return Mage::app()->getRequest()->getParam('store_id');
	}
	
	public function getOrder()
	{
		return Mage::registry('current_order');
	}
	
	public function getQuote()
	{
		if (!$this->_quote)
		{
			$quote_id = Mage::app()->getRequest()->getParam('id');
			$store_id = Mage::app()->getRequest()->getParam('store_id');
			
			if (!$quote_id || !$store_id)
				throw Exception("Invalid Parameters");
			
			$store = Mage::getModel('core/store')->load($store_id);
			
			$this->_quote = Mage::getModel('sales/quote')->setStore($store)->load($quote_id);
		}
		
		return $this->_quote;
	}
	
	public function getCartItems()
	{
		$quote = $this->getQuote();
		return $quote->getItemsCollection(false);
	}
	
	public function getAbandonedCart($quote_id = null)
	{
		if (!$this->_cart)
		{
			if (!$quote_id)
				$quote_id = Mage::app()->getRequest()->getParam('id');
			
			$collection = Mage::getResourceModel('emaildirect/abandoned_collection')->filterByQuoteId($quote_id);
			
			$this->_cart = $collection->getFirstItem();
		}
		return $this->_cart;
	}
}