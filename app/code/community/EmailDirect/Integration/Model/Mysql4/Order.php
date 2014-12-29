<?php

class EmailDirect_Integration_Model_Mysql4_Order extends Mage_Core_Model_Mysql4_Abstract
{
	protected $_read = null;
	protected $_write = null;

	protected function _construct()
	{
		$this->_init('emaildirect/order', 'id');
		$this->_read = $this->_getReadAdapter();
		$this->_write = $this->_getWriteAdapter();
	}
	
	public function loadByOrderId($order_id)
	{
		$select = $this->_read->select()
			->from($this->getTable('emaildirect/order'))
			->where('order_id=?', $order_id);

		if ($result = $this->_read->fetchRow($select))
			return $result;
		
		return false;
	}
}	