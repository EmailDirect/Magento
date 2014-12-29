<?php

class EmailDirect_Integration_Block_Adminhtml_Abandoned_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

	/**
	 * ids of current stores
	 */
	protected $_store_ids			= array();
	
	protected $_abandoned_status = null;
	
	/**
	 * stores current currency code
	 */
	protected $_currentCurrencyCode = null;

   public function __construct()
	{
		parent::__construct();
		$this->setId('abandonedGrid');
		$this->setUseAjax(true);
		$this->setDefaultSort('updated_at');
		$this->setSaveParametersInSession(true);
		$this->setPagerVisibility(true);
		$this->setTemplate('widget/grid.phtml');
		$this->setRowClickCallback('openGridRow');
		
		$this->_abandoned_status = Mage::helper('emaildirect')->getAbandonedStatus();
	}
	
	/**
	 * Retrieve currency code based on selected store
	 *
	 * @return string
	 */
	public function getCurrentCurrencyCode()
	{
		if (is_null($this->_currentCurrencyCode))
		{
			reset($this->_store_ids);
			$this->_currentCurrencyCode = (count($this->_store_ids) > 0)
				? Mage::app()->getStore(current($this->_store_ids))->getBaseCurrencyCode()
				: Mage::app()->getStore()->getBaseCurrencyCode();
		}
		return $this->_currentCurrencyCode;
	}
	
	/**
	 * store_ids setter
	 *
	 * @param  array $store_ids
	 * @return Mage_Adminhtml_Block_Report_Grid_Shopcart_Abstract
	 */
	public function setStoreIds()
	{
		if ($this->getRequest()->getParam('website'))
			$this->_store_ids = Mage::app()->getWebsite($this->getRequest()->getParam('website'))->getStoreIds();
		else if ($this->getRequest()->getParam('group'))
			$this->_store_ids = Mage::app()->getGroup($this->getRequest()->getParam('group'))->getStoreIds();
		else if ($this->getRequest()->getParam('store'))
			$this->_store_ids = array((int)$this->getRequest()->getParam('store'));
		else
			$this->_store_ids = array();
	}
	
	protected function _prepareCollection()
   {
		$collection = Mage::getResourceModel('emaildirect/abandoned_collection');
		
		$filter = $this->getParam($this->getVarNameFilter(), array());
		
		if ($filter)
		{
			$filter = base64_decode($filter);
			parse_str(urldecode($filter), $data);
		}
	  
		$this->setCollection($collection);

		if (!empty($data))
			$collection->prepareForAbandonedReport($this->_store_ids, $data);
		else
			$collection->prepareForAbandonedReport($this->_store_ids);
		
		return parent::_prepareCollection();
	}

	protected function _addColumnFilterToCollection($column)
	{
		$field = ($column->getFilterIndex()) ? $column->getFilterIndex() : $column->getIndex();

		parent::_addColumnFilterToCollection($column);
		return $this;
	}
	 
	private function getCustomerGroups($blank = false)
	{
		$options = array();

		$groups = Mage::Helper('customer')->getGroups();
		
		if ($blank)
			$options[""] = "";

		foreach($groups as $group) 
		{
			$options[$group->getData('customer_group_id')] = Mage::helper('catalog')->__($group->getData('customer_group_code'));
		}
		
		$options[0] = Mage::helper('catalog')->__('NOT LOGGED IN');

		return $options;
	}

	protected function _prepareColumns()
	{
		$this->addColumn('customer_firstname', array(
			'header'		=> Mage::helper('emaildirect')->__('First Name'),
			'index'		=> 'customer_firstname'
		));
			
		$this->addColumn('customer_lastname', array(
			'header'		=> Mage::helper('emaildirect')->__('Last Name'),
			'index'		=> 'customer_lastname'
		));

		$this->addColumn('email', array(
			'header'		=> Mage::helper('emaildirect')->__('Email'),
			'filter_index' => 'IF(main_table.customer_email IS NOT NULL, main_table.customer_email, email)',
			'index'		=> 'email'
		));
		  
		$this->addColumn('customer_group_id', array(
			'header'		=> Mage::helper('emaildirect')->__('Customer Group'),
			'index'		=> 'customer_group_id',
			'type'		=> 'options',	
			'options' 	=> $this->getCustomerGroups()
			));

		$this->addColumn('items_count', array(
			'header'		=> Mage::helper('emaildirect')->__('Number of Items'),
			'align'		=> 'right',
			'index'		=> 'items_count',			
			'type'		=> 'number'
		));

		$this->addColumn('items_qty', array(
			'header'		=> Mage::helper('emaildirect')->__('Quantity of Items'),
			'align'		=> 'right',
			'index'		=> 'items_qty',
			'type'		=> 'number'
		));

		$this->setStoreIds();
		
		$currencyCode = $this->getCurrentCurrencyCode();
		
		$this->addColumn('store_id', array(
			'header'        	=> Mage::helper('catalog')->__('Store'),
			'index'         	=> 'store_id',
			'type'          	=> 'store',
			'filter_index'		=> 'main_table.store_id',
			'store_view'    	=> true,
			'sortable'      	=> false
      ));
		
		$this->addColumn('subtotal', array(
			'header'				=> Mage::helper('emaildirect')->__('Subtotal'),
			'width'		 		=> '80px',
			'type'		  		=> 'currency',
			'currency_code' 	=> $currencyCode,
			'index'		 		=> 'subtotal',
			'renderer'	  		=> 'adminhtml/report_grid_column_renderer_currency',
			'rate'		  		=> $this->getRate($currencyCode),
		));
		
		$this->addColumn('updated_at', array(
			'header'				=> Mage::helper('emaildirect')->__('Abandoned Date'),
			'type'				=> 'datetime',
			'index'				=> 'updated_at',
			'filter_index'		=> 'main_table.updated_at'
		));
		
		$this->addColumn('abandoned_minutes', array(
			'header'				=> Mage::helper('emaildirect')->__('Minutes'),
			'width'				=> '70px',
			'renderer'			=> 'EmailDirect_Integration_Block_Widget_Grid_Column_Renderer_Abandoned_Minutes',
			'index'				=> 'abandoned_minutes',
			'sortable'			=> false,
			'filter'				=> false,
		));

		$this->addColumn('remote_ip', array(
			'header'				=> Mage::helper('emaildirect')->__('IP Address'),
			'width'				=> '80px',
			'index'				=> 'remote_ip'
		));
		
		$this->addColumn('date_sent', array(
			'header'				=> Mage::helper('emaildirect')->__('Date Sent to EmailDirect'),
			'index'				=> 'date_sent',
			'type'				=> 'datetime'	
		));
		
		if ($this->_abandoned_status['enabled'])
		{
			$this->addColumn('action',
			array(
				'header'			=>  Mage::helper('emaildirect')->__('Action'),
				'width'			=> '50px',
				'type'			=> 'action',
				'getter'			=> 'getId',
				'actions'		=> array(
					array(
						'caption'   => Mage::helper('emaildirect')->__('Send'),
						'url'	   	=> array('base'=> '*/*/send', 'params' => Mage::helper('emaildirect')->getUrlParams()),
						'field'	 	=> 'id',
						'sent'		=> false
					),
					array(
						'caption'   => Mage::helper('emaildirect')->__('Resend'),
						'url'	   	=> array('base'=> '*/*/send', 'params' => Mage::helper('emaildirect')->getUrlParams()),
						'field'	 	=> 'id',
						'sent'		=> true
					)
				),
				'filter'			=> false,
				'sortable'  	=> false,
				'renderer'		=> 'EmailDirect_Integration_Block_Widget_Grid_Column_Renderer_Action',
				'index'	 		=> 'stores',
				'is_system' 	=> true,
				));
		}

		return parent::_prepareColumns();
	}
	
	protected function _prepareMassaction()
	{
		if (!$this->_abandoned_status['enabled'])
			return $this;
		
		$this->setMassactionIdField('post_id');
		$this->getMassactionBlock()->setFormFieldName('id');

		$this->getMassactionBlock()->addItem('send', array(
			 'label'		=> Mage::helper('emaildirect')->__('Send or Resend'),
			 'url'		=> $this->getUrl('*/*/massSend', Mage::helper('emaildirect')->getUrlParams())
		));

		return $this;
	}
	
	public function getGridUrl()
   {
	  return $this->getUrl('*/*/grid', array('_current'=> true));
   }

	public function getRowUrl($row)
	{
		return $this->getUrl('*/*/details', array('id'=>$row->getId(),'store_id' => $row->getStoreId(), 'active_tab'=>'cart'));
	}
}