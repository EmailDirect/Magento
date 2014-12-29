<?php

class EmailDirect_Integration_Block_Adminhtml_Troubleshooting_Status extends Mage_Adminhtml_Block_Template
{ 	
	public function getLoggingStatus()
	{
		return Mage::helper('emaildirect/troubleshooting')->getLoggingStatus();
	}
}
