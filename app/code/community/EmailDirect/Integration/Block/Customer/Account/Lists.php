<?php

class EmailDirect_Integration_Block_Customer_Account_Lists extends Mage_Core_Block_Template
{
	protected $_form;
	
	public function getSubscriptions()
	{
		return Mage::helper('emaildirect')->getSubscriptions($this->_getEmail());
	}
	
	/**
	 * Return HTML code for list <label> with checkbox, checked if subscribed, otherwise not
	 *
	 * @param array $list List data from 
	 * @return string HTML code
	 */
	public function showCheckbox($newsletter, $type = 'list')
	{
		$checkbox = new Varien_Data_Form_Element_Checkbox;
		$checkbox->setForm($this->getForm());
		$checkbox->setHtmlId($type . '-' . $newsletter['id']);
		
		if ($newsletter['disabled'])
			$checkbox->setData('disabled',true);
		
		$checkbox->setChecked($newsletter['subscribed']);
		
		$checkbox->setTitle( ($checkbox->getChecked() ? $this->__('Click to unsubscribe from this list.') : $this->__('Click to subscribe to this list.')) );
		$checkbox->setLabel($newsletter['name']);

		if ($type == 'list')
			$hname = $this->_htmlGroupName($newsletter);
		else
			$hname = $type;
		$checkbox->setName($hname . '[subscribed]');

		$checkbox->setValue($newsletter['id']);
		$checkbox->setClass("emaildirect-{$type}-subscriber");

		return $checkbox->getLabelHtml() . $checkbox->getElementHtml();
	}
	
	/**
	 * Utility to generate HTML name for element
	 * @param string $list
	 * @param string $group
	 * @param bool $multiple
	 * @return string
	 */
	protected function _htmlGroupName($list, $group = NULL, $multiple = FALSE)
	{
		$htmlName = "list[{$list['id']}]";

		if(!is_null($group))
			$htmlName .= "[{$group['id']}]";

		if (TRUE === $multiple)
			$htmlName .= '[]';

		return $htmlName;
	}

    /**
     * Form getter/instantiation
     *
     * @return Varien_Data_Form
     */
	public function getForm()
	{
		if ($this->_form instanceof Varien_Data_Form)
			return $this->_form;
		
		$this->_form = new Varien_Data_Form();
		return $this->_form;
	}

	/**
	 * Retrieve email from Customer object in session
	 *
	 * @return string Email address
	 */
	protected function _getEmail()
	{
		return $this->helper('customer')->getCustomer()->getEmail();
	}
}
