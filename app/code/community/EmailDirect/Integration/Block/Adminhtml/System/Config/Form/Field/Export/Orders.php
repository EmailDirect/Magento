<?php

class EmailDirect_Integration_Block_Adminhtml_System_Config_Form_Field_Export_Orders extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('emaildirect/system/config/form/field/export/orders.phtml');
	}

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$this->setElement($element);
		$html = $this->_toHtml();
		return $html;
	}
}