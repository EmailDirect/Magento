<?php

class EmailDirect_Integration_Model_Abandoned_Observer extends EmailDirect_Integration_Model_Observer_Abstract
{
	// START ABANDONED CRON
   private function getAbandonedTime()
   {
      $time = $this->_helper->config('abandonedtime');
      $time *= 60; // Adjust to seconds.
 
      $date = date(Mage::getModel('core/date')->gmtTimestamp());
		
      $date = $date - $time;
		
      return date($this->_date_format, $date);
   }
	
	private function setSentToEmailDirectDate($quote_id, $date)
	{
		try
		{
			$abandoned = Mage::getModel('emaildirect/abandoned')->loadByQuoteId($quote_id);
			
			$abandoned->setDateSent($date);
			$abandoned->save();
      }
      catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}
	
   private function processAbandoned($quote)
   {
   	$this->_log("ProcessAbandoned Start");
      $email = $quote->getCustomerEmail();
		
		if ($email == null)
			$email = $quote->getEmail();
      
      $abandonedDate = $quote->getUpdatedAt();
      
      $merge_vars = array();
      
      $merge_vars['FirstName'] = $quote->getData('customer_firstname');
      $merge_vars['LastName'] = $quote->getData('customer_lastname');
      
      $merge_vars['AbandonedDate'] = $abandonedDate;
      $merge_vars['AbandonedUrl'] = Mage::helper('emaildirect/abandoned')->getAbandonedUrl($quote);
		
		Mage::helper('emaildirect/abandoned')->addSequence($merge_vars);
		
		$merge_vars = Mage::helper('emaildirect/order')->getMergeOrderItems($quote, $merge_vars, "AB");
		
		if ($this->_helper->config('save_latest_order'))
		{
			$this->_log("Processing Last Order");
			$order = Mage::helper('emaildirect/abandoned')->getLastOrder($quote);
			
			if ($order != null)
				$merge_vars = Mage::helper('emaildirect/order')->processOrderItems($order, $merge_vars);
			else
				$this->_log("Order not found");
		}

      $xml = Mage::getSingleton('emaildirect/wrapper_abandoned')->getOneSubscriber($email,$merge_vars);
      
		$this->_log("ProcessAbandoned End");
		
      return $xml;
   }

	private function _abandonedCartsProcessor($collection, $store = null, $mark_time = true)
	{
		// Store the time we last run
		$date = date(Mage::getModel('core/date')->gmtTimestamp());
      $date = date($this->_date_format, $date);
		
		if ($mark_time)
		{
			$this->_log("Saving Last Run Date: {$date}");
			$this->_helper->updateConfig("abandoned_last_run", $date, $store);
		}
		
      $subscribers = false;
      
      $xml = "<Subscribers>";
		
		$quote_list = array();
      
      // Get the data for each abandoned cart
      foreach ($collection as $quote)
      {
      	$quote_list[] = $quote->getId();	
         $xml .= $this->processAbandoned($quote, $date);
         $subscribers = true;
      }
      
      $xml .= "</Subscribers>";
      
      if (!$subscribers)
      {
      	$this->_logReason("No Carts Found");
         return; // No abandoned carts found
      }

      $this->_log("Sending Abandoned Carts");
      // Send them all at once
      $rc = Mage::getSingleton('emaildirect/wrapper_abandoned')->sendSubscribers($xml);
		
		if (isset($rc->ErrorCode))
		{
         $this->_log("EmailDirect Error: (" . (string) $rc->ErrorCode . "): " . (string)$rc->Message);
      }
		else
		{
			$this->_log("Setting date sent for all quotes Start");
			$this->setDateSent($quote_list,$date);
			$this->_log("Setting date sent for all quotes End");
		}
	}

	private function setDateSent($quote_list, $date)
	{
		foreach ($quote_list as $quote_id)
		{
			$this->setSentToEmailDirectDate($quote_id, $date);
		}
	}
	
	public function abandonedStoreProcessor($store, $check_date)
	{
		try
		{
			$store_code = $store->getCode();
			
			$this->_log("Processing Store: {$store_code}");
			
			if (!$this->_helper->getAbandonedEnabled())
			{
				$this->_logReason($this->_helper->getAbandonedDisabledReason());
				return;
			}
			
			Mage::helper('emaildirect/fields')->checkFields();
			
			// Setup sequence for this store
			Mage::helper('emaildirect/abandoned')->setupSequence();
			
	      // Get abandoned collection
	      $collection = Mage::getResourceModel('emaildirect/abandoned_collection');
	      
			$collection->prepareForAbandonedProcess($check_date, $store->getId());
			
			$this->_log("SQL: " . $collection->getSelect()->__toString());
			
			$this->_abandonedCartsProcessor($collection, $store->getId());
			
			Mage::helper('emaildirect/abandoned')->saveCurrentSequence();
			
			$this->_log("Finished Processing Store: {$store_code}");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}

	public function manualCartsProcessor($store_id = null)
   {
   	try
   	{
   		Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
   		$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::ABANDONED_CART);
	   	$this->_log("Manual Abandoned Carts Processor Start");
			
			$check_date = $this->getAbandonedTime();
			
			$this->_log("Check Date: {$check_date}");
			
			$stores = Mage::app()->getStores();
			
			foreach ($stores as $store)
			{
				if ($store_id == null || $store_id == $store->getId())
				{
					Mage::app()->setCurrentStore($store->getCode());
					
					$this->abandonedStoreProcessor($store, $check_date);
				}
			}
			
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			
			$this->_log("Manual Abandoned Carts Processor End");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
   }
   
   public function abandonedCartsProcessor()
   {
   	try
   	{
   		Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
	   	$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::ABANDONED_CART);
	   	$this->_log("Abandoned Carts Processor Start");
			
			$check_date = $this->getAbandonedTime();
			
			$this->_log("Check Date: {$check_date}");
			
			$stores = Mage::app()->getStores();
			
			//$starting_store = Mage::app()->getStore()->getCode();
			
			foreach ($stores as $store)
			{
				Mage::app()->setCurrentStore($store->getCode());
				
				$this->abandonedStoreProcessor($store, $check_date);
			}
			
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			
			$date = date(Mage::getModel('core/date')->gmtTimestamp());
      	$date = date($this->_date_format, $date);
			
			$this->_log("Saving Last Cron Run Date: {$date}");
			$this->_helper->updateConfig("cron_last_run", $date);
			
			$this->_log("Abandoned Carts Processor End");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
   }

	public function SendAbandonedCarts($id_list)
   {
   	try
   	{
   		$count = 0;
			
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			
	   	$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::ABANDONED_CART);
	   	$this->_log("Sending Specific Abandoned Carts Start");
			
			Mage::helper('emaildirect/fields')->checkFields();
	      
			$this->_log($id_list, "ID LIST");
			
			$stores = Mage::app()->getStores();
			
			foreach ($stores as $store)
			{
				$this->_log("Checking for valid carts in store: {$store->getName()} ({$store->getId()})");
				if (!Mage::helper('emaildirect')->getAbandonedEnabled($store->getId()))
				{
					$this->_log('Abandoned Carts not enabled...');
					continue;
				}
				
				Mage::app()->setCurrentStore($store->getCode());
				
				// Get abandoned collection
		      $collection = Mage::getResourceModel('emaildirect/abandoned_collection');
				
				$collection->prepareForManualAbandonedProcess($id_list, $store->getId());
				
				$count += count($collection);
		      
				$this->_log("SQL: " . $collection->getSelect()->__toString());
				
				// Setup sequence for this store
				Mage::helper('emaildirect/abandoned')->setupSequence();
				
		      $this->_abandonedCartsProcessor($collection, $store, false);
				
				Mage::helper('emaildirect/abandoned')->saveCurrentSequence();
			}
							
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			
			$this->_log("Sending Specific Abandoned Carts End");
			
			return $count;
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
   }
   
   // END ABANDONED CRON
   
   // START QUOTE SAVE AFTER
   public function quoteSaveAfter(Varien_Event_Observer $observer)
   {
   	try
   	{
	   	$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::ABANDONED_CART);
			$this->_logger->setLogLevel(EmailDirect_Integration_Helper_Troubleshooting::LOG_LEVEL_LOW);
	   	$this->_log("Quote Save After Start");
			
			Mage::getSingleton('emaildirect/session')->init();
			
			$this->_log("Quote Save After End");
			$this->_logger->resetLogLevel();
		}
		catch (Exception $e)
		{
			$this->_logger->resetLogLevel();
			Mage::logException($e);
			$this->_logException($e);
		}
   }
   // END QUOTE SAVE AFTER
}	