<?php

class EmailDirect_Integration_Block_Adminhtml_System_Config_Form_Field_Signup_Test extends Mage_Adminhtml_Block_System_Config_Form_Field
{
   /**
     * Check if columns are defined, set template
     *
     */
	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('emaildirect/system/config/form/field/signup/test.phtml');
	}
	
	/**
     * Get the grid and scripts contents
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$this->setElement($element);
		$html = $this->_toHtml();
		
		return $html;
	}
}