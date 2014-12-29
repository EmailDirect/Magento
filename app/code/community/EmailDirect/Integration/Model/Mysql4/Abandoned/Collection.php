<?php

class EmailDirect_Integration_Model_Mysql4_Abandoned_Collection extends Mage_Sales_Model_Mysql4_Quote_Collection
{
	protected function _construct()
	{
		parent::_construct();
	}
	
	private function prepareCollection($store_ids)
	{
		$resource = Mage::getSingleton('core/resource');
		
		$this->getSelect()->joinInner(array('ed_ac' => $resource->getTableName("emaildirect/abandoned")),"ed_ac.quote_id=main_table.entity_id",array(
					'date_sent' 	=> "date_sent"
	        	));
		$this->getSelect()->joinInner(array('ed_s' => $resource->getTableName("emaildirect/session") ),"ed_s.id=ed_ac.session_id",array(
					'email' 			=> "IF(main_table.customer_email IS NOT NULL, main_table.customer_email, email)"
	        	));
		
		$this->addFieldToFilter('items_count', array('neq' => '0'))
			->addFieldToFilter('main_table.is_active', '1');
		
		$this->getSelect()->where('(customer_email IS NOT NULL) OR (email IS NOT NULL)');
		
		$this->setOrder('updated_at');
		
		if (is_array($store_ids) && !empty($store_ids))
			$this->addFieldToFilter('main_table.store_id', array('in' => $store_ids));
	}
	
	public function filterByQuoteId($quote_id)
	{
		$this->prepareCollection(array());
		$this->getSelect()->where("quote_id = {$quote_id}");
		
		return $this;
	}
	
	/**
	 * Prepare for abandoned report
	 *
	 * @param array $store_ids
	 * @param string $filter
	 * @return Mage_Reports_Model_Resource_Quote_Collection
	 */
	public function prepareForAbandonedReport($store_ids, $filter = null)
	{
		$this->prepareCollection($store_ids);
		
		$this->addSubtotal($store_ids, $filter);

		return $this;
	}
	
	public function prepareForAbandonedProcess($check_date, $store_id = null)
	{
		$store_ids = array($store_id);
		
		$this->prepareCollection($store_ids);
		
		$this->addFieldToFilter('date_sent',array('null' => true));
		$this->addFieldToFilter('main_table.updated_at', array('lt' => $check_date));
		
		return $this;
	}
	
	public function prepareForManualAbandonedProcess($id_list, $store_id)
	{
		$store_ids = array($store_id);
		
		$this->prepareCollection($store_ids);
		
		$this->addFieldToFilter('entity_id', array('in' => $id_list))
		            ->setOrder('updated_at');
		
		return $this;
	}
	
	/**
	 * Add subtotals
	 *
	 * @param array $store_ids
	 * @param array $filter
	 * @return Mage_Reports_Model_Resource_Quote_Collection
	 */
	public function addSubtotal($store_ids = '', $filter = null)
	{
		if (is_array($store_ids))
		{
			$this->getSelect()->columns(array(
				'subtotal' => '(main_table.base_subtotal_with_discount*main_table.base_to_global_rate)'
			));
			$this->_joinedFields['subtotal'] =
				'(main_table.base_subtotal_with_discount*main_table.base_to_global_rate)';
		}
		else
		{
			$this->getSelect()->columns(array('subtotal' => 'main_table.base_subtotal_with_discount'));
			$this->_joinedFields['subtotal'] = 'main_table.base_subtotal_with_discount';
		}

		if ($filter && is_array($filter) && isset($filter['subtotal']))
		{
			if (isset($filter['subtotal']['from']))
			{
				$this->getSelect()->where(
					$this->_joinedFields['subtotal'] . ' >= ?',
					$filter['subtotal']['from'], Zend_Db::FLOAT_TYPE
				);
			}
			if (isset($filter['subtotal']['to']))
			{
				$this->getSelect()->where(
					$this->_joinedFields['subtotal'] . ' <= ?',
					$filter['subtotal']['to'], Zend_Db::FLOAT_TYPE
				);
			}
		}

		return $this;
	}
}