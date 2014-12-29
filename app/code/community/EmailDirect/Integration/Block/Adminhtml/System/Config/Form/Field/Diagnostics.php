<?php

class EmailDirect_Integration_Block_Adminhtml_System_Config_Form_Field_Diagnostics extends Mage_Adminhtml_Block_System_Config_Form_Field
{	
	private $_helper = null;
	private $_status = null;

	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('emaildirect/system/config/form/field/diagnostics.phtml');
		$this->_helper = Mage::helper('emaildirect');
	}

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$this->setElement($element);
		$html = $this->_toHtml();
		return $html;
	}
	
	public function getDiagnosticStatus()
	{
		return Mage::helper('emaildirect/troubleshooting')->isDiagnosticEnabled();
	}	
}