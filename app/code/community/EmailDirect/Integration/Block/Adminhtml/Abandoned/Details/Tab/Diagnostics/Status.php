<?php

class EmailDirect_Integration_Block_Adminhtml_Abandoned_Details_Tab_Diagnostics_Status extends EmailDirect_Integration_Block_Adminhtml_Diagnostics_Status_Abstract
{
	public function isAbandonedEnabled()
	{
		return Mage::helper('emaildirect')->getAbandonedEnabled();
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
	
	public function getQuoteId()
	{
		return Mage::app()->getRequest()->getParam('id');
	}
	
	public function getQuote()
	{
		$quote = Mage::getModel('sales/quote')->setStore($this->getStore())->load($this->getQuoteId());
		
		return $quote;
	}
	
	public function getAbandonedQuote()
	{
		$abandoned = Mage::getModel('emaildirect/abandoned')->loadByQuoteId($this->getQuoteId());
		
		return $abandoned;
	}
	
	public function getEmailDirectDate()
	{
		$abandoned = $this->getAbandonedQuote();
		
		return $abandoned->getDateSent();
		//if ($ed_order != null && $ed_order->getDateSent() != null)
			//return $ed_order->getDateSent();
		
		return null;
	}
}
