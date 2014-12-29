<?php

class EmailDirect_Integration_Block_Adminhtml_System_Config_Form_Field_Export_Range extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('emaildirect/system/config/form/field/export/date_range.phtml');
	}
	
	public function getFromDate()
	{
		$time = strtotime("-1 year", Mage::getModel('core/date')->timestamp(time()));
		return date("Y-m-d", $time);
	}
	
	public function getToDate()
	{
		$time = strtotime("+1 day", Mage::getModel('core/date')->timestamp(time()));
		return date("Y-m-d", $time);
	}
	
	public function getOrdersCount()
	{
		try
		{
			$store = Mage::helper('emaildirect')->getAdminStore();
			$orders = Mage::helper('emaildirect')->getOrderExportCollection($this->getFromDate(),$this->getToDate(), Mage::helper('emaildirect')->exportConfig('include_already_sent'), $store);
			
			return $orders->getSize();
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			return "Unknown";
		}
	}

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$this->setElement($element);
		$html = $this->_toHtml();
		return $html;
	}
}