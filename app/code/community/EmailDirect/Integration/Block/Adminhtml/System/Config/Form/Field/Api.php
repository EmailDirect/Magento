<?php

class EmailDirect_Integration_Block_Adminhtml_System_Config_Form_Field_Api extends Mage_Adminhtml_Block_System_Config_Form_Field
{	
	private $_helper = null;

	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('emaildirect/system/config/form/field/api.phtml');
		$this->_helper = Mage::helper('emaildirect');
	}

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$this->setElement($element);
		$html = $this->_toHtml();
		return $html;
	}
	
	public function isValid()
	{
		return Mage::helper('emaildirect')->config('setup') == 1;
	}
	
	public function getCurrentApiKey()
	{
		return $this->_helper->getApiKey();
	}
}