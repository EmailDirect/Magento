<?php

class EmailDirect_Integration_Model_Mysql4_Session extends Mage_Core_Model_Mysql4_Abstract
{	
	protected $_read = null;
	protected $_write = null;

	protected function _construct()
	{
		$this->_init('emaildirect/session', 'id');
		$this->_read = $this->_getReadAdapter();
		$this->_write = $this->_getWriteAdapter();
	}
	
	public function loadByMagentoSessionId($magento_session_id)
	{
		$select = $this->_read->select()
			->from($this->getTable('emaildirect/session'))
			->where('magento_session_id=?', $magento_session_id);

		if ($result = $this->_read->fetchRow($select))
			return $result;
		
		return false;
	}
}	