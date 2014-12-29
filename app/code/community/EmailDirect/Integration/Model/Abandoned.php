<?php

class EmailDirect_Integration_Model_Abandoned extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		parent::_construct();
		$this->_init('emaildirect/abandoned');
	}

	public function loadByQuoteId($quote_id)
	{
		if ($data = $this->getResource()->loadByQuoteId($quote_id))
			$this->addData($data);
		else
			return false;
		
		return $this;
	}
	
	public function init(EmailDirect_Integration_Model_Session $session)
	{
		try
		{
			$quote_id = Mage::helper('checkout/cart')->getQuote()->getId();
			
			if (!$quote_id)
				return;
			
			if (!$this->loadByQuoteId($quote_id))
			{
				$this->setQuoteId($quote_id);
				$this->setSessionId($session->getId());
				$this->save();
			}
		}
		catch (Exception $e)
		{
			Mage::helper('emaildirect')->logException($e);
			Mage::logException($e);
		}
		return $this;
	}
}