<?php

class EmailDirect_Integration_Block_Checkout_Subscribe extends Mage_Core_Block_Template
{
	protected function _toHtml()
	{
		$alreadySubscribed = Mage::getModel('newsletter/subscriber')
							->loadByEmail($this->getQuote()->getCustomerEmail())
							->isSubscribed();

		if (!$this->helper('emaildirect')->canCheckoutSubscribe() || $alreadySubscribed) 
			return "";
		
		return parent::_toHtml();
	}

	public function getQuote()
	{
		return Mage::getSingleton('checkout/session')
				->getQuote();
	}

	public function checkStatus()
	{
		return (int)$this->helper('emaildirect')->config('checkout_subscribe');
	}
}