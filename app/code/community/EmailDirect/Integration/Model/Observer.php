<?php

class EmailDirect_Integration_Model_Observer extends EmailDirect_Integration_Model_Observer_Abstract
{
	//--------------------------------------------------------------------------------------------------
	// WISHLIST
	
	public function onWishlistProductAddAfter(Varien_Event_Observer $observer)
	{
		try
		{
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::WISHLIST);
			//$this->_helper->setLogLevel(EmailDirect_Integration_Helper_Troubleshooting::LOG_LEVEL_LOW);
			$this->_log('onWishlistProductAddAfter Start');
			
			if (!$this->_helper->canSendWishlist())
			{
				$this->_logReason($this->_helper->getWishlistDisabledReason());
				return;
			}
			
			$customer = Mage::getSingleton('customer/session')->getCustomer();
			
			$merge_vars = array(
					'WishListUrl' => Mage::getUrl('wishlist'),
					'WishListDate' => Mage::getModel('core/date')->date($this->_date_format)
				);
			
			$rc = Mage::getSingleton('emaildirect/wrapper_wishlist')->sendWishlist($customer->getEmail(), $merge_vars);
			
			$this->_log('onWishlistProductAddAfter End');
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}
	
	// WISHLIST END
	//--------------------------------------------------------------------------------------------------
}