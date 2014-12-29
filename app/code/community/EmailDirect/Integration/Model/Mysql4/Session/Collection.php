<?php

class EmailDirect_Integration_Model_Mysql4_Session_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	protected function _construct()
	{
		$this->_init('emaildirect/session');
	}

	public function addStoreFilter($store_ids)
	{
		$this->getSelect()->where('main_table.store_id IN (?)', $store_ids);
		return $this;
	}
}
