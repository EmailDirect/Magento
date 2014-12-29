<?php

class EmailDirect_Integration_Model_Order extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		parent::_construct();
		$this->_init('emaildirect/order');
	}

	public function loadByOrderId($order_id)
	{
		try
		{
			if ($data = $this->getResource()->loadByOrderId($order_id))
				$this->addData($data);
			else
				return false;
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			Mage::helper('emaildirect')->logException($e);
			return false;
		}
		return $this;
	}
	
	public function saveSent($order, $date)
	{
		try
		{
			if (!$this->loadByOrderId($order->getId()))
				$this->setOrderId($order->getId());
			
			$this->setDateSent($date);
			
			$this->save();
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			Mage::helper('emaildirect')->logException($e);
			return false;
		}
		
		return $this;
	}
}