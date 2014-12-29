<?php
class EmailDirect_Integration_Model_Wrapper_Wishlist extends EmailDirect_Integration_Model_Wrapper_Abstract 
{
   public function sendWishlist($email, $merge_vars)
   {
   	$subscribe = Mage::helper('emaildirect')->forceSubscribe();
		
      return Mage::getSingleton('emaildirect/wrapper_subscribers')->subscriberAdd($email, $merge_vars, "", $subscribe);
   }
}
