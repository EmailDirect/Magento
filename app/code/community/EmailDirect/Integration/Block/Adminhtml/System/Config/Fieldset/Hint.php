<?php

class EmailDirect_Integration_Block_Adminhtml_System_Config_Fieldset_Hint
	extends Mage_Adminhtml_Block_Abstract
	implements Varien_Data_Form_Element_Renderer_Interface
{
	protected $_template = 'emaildirect/system/config/fieldset/hint.phtml';

	/**
	 * Render fieldsetset html
	 *
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string
	 */
	public function render(Varien_Data_Form_Element_Abstract $element)
	{
		return $this->toHtml();
	}

	public function getEmaildirectVersion()
	{
		return (string) Mage::getConfig()->getNode('modules/EmailDirect_Integration/version');
	}
	
	public function getAbandonedLastRunHtml()
	{
		return Mage::helper('emaildirect')->getAbandonedLastRunHtml();
	}
	
	public function getAbandonedEnabled()
	{
		return Mage::helper('emaildirect')->getAbandonedEnabled();
	}
	
	public function isWebsiteConfig()
	{
		return Mage::helper('emaildirect')->isWebsiteConfig();
	}
}
