<?php

class EmailDirect_Integration_Model_Mysql4_Order_Collection extends Mage_Sales_Model_Mysql4_Order_Collection
{
	protected $_helper = null;
	
	protected function _construct()
	{
		$this->_helper = Mage::helper('emaildirect');
		parent::_construct();
	}
	
	public function prepareCollection($store_ids = '')
	{
		$resource = Mage::getSingleton('core/resource');
		
		$this->getSelect()->joinLeft(array('ed_or' => $resource->getTableName("emaildirect/order")),"ed_or.order_id=main_table.entity_id",array(
					'date_sent' 	=> "date_sent"
	        	));
		
		if (is_array($store_ids) && !empty($store_ids))
			$this->addFieldToFilter('main_table.store_id', array('in' => $store_ids));
		
		$this->getSelect()->order('created_at ASC');
		
		return $this;
	}
	
	private function stateStatusFilter()
	{
		$mode = $this->_helper->config('send_field');
		
		if ($mode == "state")
			$options = $this->_helper->config('send_states');
		else
			$options = $this->_helper->config('send_statuses');
			
		$option_list = explode(",",$options);
		
		$this->addFieldToFilter($mode, array('in' => $option_list));
	}
	
	private function limitByDate()
	{
		$this->_helper = Mage::helper('emaildirect');
		
		$date_adj = $this->_helper->config('batch_date_adjust');
		
		if ($date_adj == null || $date_adj == "")
			$date_adj = "-1 week";
		
		$time = date("Y-m-d",strtotime($date_adj, Mage::getModel('core/date')->timestamp(time())));
		
		$limit_date = Mage::getModel('core/date')->gmtDate(null,strtotime($time));
		
		$this->addAttributeToFilter('created_at', array('from' => $limit_date));
	}
	
	public function getUnsentOrders($store_id = null, $limit = 100)
	{
		$store_ids = null;
		if ($store_id != null)
			$store_ids = array($store_id);
		
		$this->prepareCollection($store_ids);
		
		$this->addFieldToFilter('date_sent', array('null' => true));
		
		$this->limitByDate();
		
		$this->stateStatusFilter();
		
		if ($limit != null && $limit != "")
			$this->getSelect()->limit($limit);
		
		return $this;
	}
}