<?php

class EmailDirect_Integration_Block_Adminhtml_Abandoned_Details_Form extends Mage_Adminhtml_Block_Template
{
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('emaildirect/abandoned/details/form.phtml');
	}
}
