<?php

class EmailDirect_Integration_Block_Adminhtml_Abandoned_Status extends Mage_Adminhtml_Block_Template
{
	public function getAbandonedStatus()
	{
		return Mage::helper('emaildirect')->getAbandonedStatus();
	}
}	