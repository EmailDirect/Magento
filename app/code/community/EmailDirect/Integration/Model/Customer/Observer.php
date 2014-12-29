<?php

class EmailDirect_Integration_Model_Customer_Observer extends EmailDirect_Integration_Model_Observer_Abstract
{
	private function hasCustomerChanged($customer)
	{
		$maps = unserialize( $this->_helper->config('map_fields', $customer->getStoreId()) );
		
		$this->_log('Checking for mapped data changes');
		
		foreach ($maps as $map)
		{
			$field = $map['magento'];
			$original = $customer->getOrigData($field);
			$current = $customer->getData($field);
			
			$this->_log("Original: {$original} - Current: {$current}");
			
			if ($original != $current)
				return true;
		}
		
		return false;
	}
	
	protected function getMergeCustomer($object = NULL)
	{
		//Initialize as GUEST customer
		$customer = new Varien_Object;
		
		$this->_log("Observer Merge Vars");

		$regCustomer   = Mage::registry('current_customer');
		$guestCustomer = Mage::registry('ed_guest_customer');

		if (Mage::helper('customer')->isLoggedIn())
		{
			$this->_log("Logged in Customer");
		   $customer = Mage::helper('customer')->getCustomer();
		}
		else if ($regCustomer)
		{
			$this->_log("Current Customer");
		   $customer = $regCustomer;
		}
		else if ($guestCustomer)
		{
			$this->_log("Guest Customer");
		   $customer = $guestCustomer;
		}
		else
		{
			$this->_log("Parameter Customer");
         $customer = $object;
		}
		
      $address = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
      if ($address)
         $customer->setBillingAddress($address);
		
		return $customer;
	}
	
	public function customerLogin(Varien_Event_Observer $observer)
	{
		try
		{
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::CUSTOMER);
			$this->_log("Customer Login Start");
			
			if (!$this->_helper->canSendLastLogin())
			{
				$this->_logReason($this->_helper->getLastLoginDisabledReason());
				return;
			}
			
			$customer = $observer->getEvent()->getCustomer();
			
			if (!$customer)
			{
				$this->_logReason("Customer not found");
				return;
			}
			
			$date = date(Mage::getModel('core/date')->gmtTimestamp());
      	$date = date($this->_date_format, $date);
			
			$this->_log("Last Login Date: {$date}");
			
			$email = $customer->getEmail();
			
			$this->_log("Email: {$email}");
			
			Mage::getSingleton('emaildirect/wrapper_subscribers')->sendLastLogin($email, $date);
			
			$this->_log("Customer Login End");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}
		
	public function updateCustomer(Varien_Event_Observer $observer)
	{
		try
		{
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::CUSTOMER);
			$this->_log("Update Customer Start");
			
			if (!$this->_helper->canEdirect())
			{
				$this->_logReason($this->_helper->getDisabledReason());
				Mage::app()->setCurrentStore($starting_store);
				return;
			}
			
			$customer = $observer->getEvent()->getCustomer();
			
			if (!$customer)
			{
				$this->_logReason("Customer not found");
				return;
			}
			
			$customer = $this->getMergeCustomer($customer);
			
			$merge_vars = $this->_helper->getMergeVars($customer);
			
			$this->_log($merge_vars,"Merge Vars");
			
			$api = Mage::getSingleton('emaildirect/wrapper_subscribers');
			
			$oldEmail = $customer->getOrigData('email');
			$email = $customer->getEmail();
			
			if ($oldEmail == '')
			{
				$this->_log("Original Email was blank. Adding Subscriber");
			   $rc = $api->subscriberAdd($email,$merge_vars,"",false);
			}
			elseif ($oldEmail != $email)
			{
				$this->_log("Modifying Email");
				
				// If this fails we just add the subscriber
				$rc = $api->mailModify($oldEmail,$email);
				
				$this->_log("Adding Subscriber");
	         $rc = $api->subscriberAdd($email,$merge_vars,"",false);
			}
			else
			{
				if ($this->hasCustomerChanged($customer))
				{
					$this->_log("Updating Subscriber");
	         	$rc = $api->subscriberAdd($email,$merge_vars,"",false);
				}
				else
					$this->_logReason("Neither Email nor Mapped Data changed");
			}
			
			$this->_log("Update Customer End");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}

	public function updateCustomerAdmin(Varien_Event_Observer $observer)
	{
		try
		{
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::CUSTOMER);
			$this->_log("Update Customer (Admin) Start");
			
			$starting_store = Mage::app()->getStore()->getCode();
			
			$customer = $observer->getEvent()->getCustomer();
			
			if (!$customer)
			{
				$this->_logReason("Customer not found");
				return;
			}
			
			$customer_store = $customer->getStore()->getId();
			
			$current_store = Mage::app()->getStore()->getId();
			
			$this->_log("Checking store");
			
			if ($current_store == 0)
			{
				$this->_log("Admin store current");
				if ($customer_store == 0)
				{
					$this->_logReason("Customer store can't be determined");
					return;
				}
				else
					Mage::app()->setCurrentStore($customer_store);
			}
			
			$this->_log("Correct store found");
			
			if (!$this->_helper->canEdirect())
			{
				$this->_logReason($this->_helper->getDisabledReason());
				Mage::app()->setCurrentStore($starting_store);
				return;
			}
			
			$merge_vars = $this->_helper->getMergeVars($customer);
			
			$this->_log($merge_vars,"Merge Vars");
			
			$api = Mage::getSingleton('emaildirect/wrapper_subscribers');
			
			$oldEmail = $customer->getOrigData('email');
			$email = $customer->getEmail();
			
			if ($oldEmail == '')
			{
				$this->_log("Original Email was blank. Adding Subscriber");
			   $rc = $api->subscriberAdd($email,$merge_vars,"",false);
			}
			elseif ($oldEmail != $email)
			{
				$this->_log("Modifying Email");
				
				// If this fails we just add the subscriber
				$rc = $api->mailModify($oldEmail,$email);
				
				$this->_log("Adding Subscriber");
	         $rc = $api->subscriberAdd($email,$merge_vars,"",false);
			}
			else
			{
				if ($this->hasCustomerChanged($customer))
				{
					$this->_log("Updating Subscriber");
	         	$rc = $api->subscriberAdd($email,$merge_vars,"",false);
				}
				else
					$this->_logReason("Neither Email nor Mapped Data changed");
			}
			
			$this->_log("Update Customer End");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}

		Mage::app()->setCurrentStore($starting_store);
		return;
	}
}	