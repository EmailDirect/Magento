<?php

class EmailDirect_Integration_Block_Adminhtml_System_Config_Form_Field_Info extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	private $_helper = null;

	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('emaildirect/system/config/form/field/info.phtml');
		$this->_helper = Mage::helper('emaildirect');
	}
	
	public function render(Varien_Data_Form_Element_Abstract $element)
	{
		$useContainerId = $element->getData('use_container_id');
		return sprintf('<tr class="system-fieldset-sub-head" id="row_%s"><td colspan="5">%s</td></tr>',
			$element->getHtmlId(), $this->_toHtml()
		);
	}
	
	public function isWebsiteConfig()
	{
		return $this->_helper->isWebsiteConfig();
	}
}