<?php

class EmailDirect_Integration_Model_Order_Observer extends EmailDirect_Integration_Model_Observer_Abstract
{
	
	public function salesOrderShipmentTrackSaveAfter(Varien_Event_Observer $observer)
	{
		try
		{
			$order_helper = Mage::helper('emaildirect/order');
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::ORDERS);
			$this->_log("Sales Order Shipment Track Save After Start");
			if(!$this->_helper->canEdirect())
			{
				$this->_logReason($this->_helper->getDisabledReason());
				return;
			}
			$track = $observer->getEvent()->getTrack();
			
			$order = $track->getShipment()->getOrder();
			$shippingMethod = $order->getShippingMethod(); // String in format of 'carrier_method'
			if (!$shippingMethod)
			{
				$this->_logReason("Shipping Method not found");
		      return;
			}
	
			$email = $order->getCustomerEmail();
			
			$merge_vars = array();
			
			$merge_vars = $order_helper->getTrackingMergeVars($track, $order);
			
			if ($merge_vars == null)
			{
				$this->_logReason("No shipping fields setup");
				return;
			}
			
			$this->_log($merge_vars,"Merge Vars");
			
			$rc = Mage::getSingleton('emaildirect/wrapper_orders')->addSubscriberTracking($email, $merge_vars);
			
			$this->_log("Sales Order Shipment Track Save After Start");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}

	private function canSendOrder($order, $batch = false)
	{
		$ed_order = Mage::getModel('emaildirect/order')->loadByOrderId($order->getId());
		
		if ($ed_order != null && $ed_order->getDateSent() != null)
		{
			$this->_logReason("Order has already been sent.");
			return false;
		}
		
		// Check the old data just in case
		if ($order->getData('sent_to_emaildirect') != false)
		{
			$this->_logReason("Order has already been sent. (Older Version)");
			$this->setOrderSentToEmailDirect($order, $order->getData('updated_at'));
			return false;
		}
		
		if ($batch)
		{
			$this->_log("Batch process. Skipping State/Status Check");
			return true;
		}
		
		$mode = $this->_helper->config('send_field');
		
		$this->_log("Send Field: {$mode}");
		
		if ($mode == "state")
		{
			$this->_log("Order State: " . $order->getState());
			
			$states = Mage::helper('emaildirect')->config('send_states');
			$state_list = explode(",",$states);
			
			$this->_log("Check States: " . $states);
			$this->_log($state_list, "State array");
			
			if (array_search($order->getState(),$state_list) === FALSE)
		   {
	      	$this->_logReason("State not setup to send (" . $order->getState() . ")");
	      	return false;
			}
		}
		else
		{
			$this->_log("Order Status: " . $order->getStatus());
			
			$statuses = Mage::helper('emaildirect')->config('send_statuses');
			$status_list = explode(",",$statuses);
			
			$this->_log("Check Statuses: " . $statuses);
			$this->_log($status_list, "Status array");
			
			if (array_search($order->getStatus(),$status_list) === FALSE)
		   {
	      	$this->_logReason("Status not setup to send (" . $order->getStatus() . ")");
	      	return false;
			}
		}
		
		return true;
	}
	
	private function processBatchStoreOrders($store)
	{
		try
		{
			$store_code = $store->getCode();
			
			$this->_log("Processing Store: {$store_code}");
			
			if (!$this->_helper->getBatchEnabled())
			{
				$this->_logReason($this->_helper->getBatchDisabledReason());
				return;
			}
			
			Mage::helper('emaildirect/fields')->checkFields();
			
	      // Get order collection
	      $collection = Mage::getResourceModel('emaildirect/order_collection');
			
			$limit = $this->_helper->config('batch_size');
			
			$this->_log("Batch Size: [{$limit}]");
			
			if ($limit != "")
			{
				if (!is_numeric($limit))
					$limit = 100;
				else
					$limit = (int)$limit;
			}
	      
			$collection->getUnsentOrders($store->getId(), $limit);
			
			$this->_log("SQL: " . $collection->getSelect()->__toString());
			
			foreach ($collection as $order)
			{
				$this->processSavedOrder($order, true);
			}
			
			$this->_log("Finished Processing Store: {$store_code}");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}
	
	public function processBatchOrders()
	{
		try
		{
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::ORDERS);
			$this->_log("Process Batch Orders Start");
			
			$stores = Mage::app()->getStores();
			
			foreach ($stores as $store)
			{
				Mage::app()->setCurrentStore($store->getCode());
				
				$this->processBatchStoreOrders($store);
			}
			
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			
			$this->_log("Process Batch Orders End");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}
	
	public function processSavedOrder($order, $batch = false)
	{
		try
		{
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::ORDERS);
			$this->_log("Process Saved Order Start");
			
			$this->_log("Order ID: " . $order->getIncrementId());
			
			$this->_log("Order Store: " . $order->getStoreId());
			$starting_store = Mage::app()->getStore()->getCode();
			
			Mage::app()->setCurrentStore($order->getStoreId());
			
			if (!$this->_helper->getOrdersEnabled())
			{
				$this->_logReason($this->_helper->getOrdersDisabledReason());
				Mage::app()->setCurrentStore($starting_store);
				return;
			}
			
			if (!$this->canSendOrder($order, $batch))
			{
				Mage::app()->setCurrentStore($starting_store);
				return;
			}
	      
	      $this->_log("Order is ready to send. Processing...");
			
	      $customer = Mage::helper('emaildirect/order')->getOrderCustomer($order);
			
		   $merge_vars = $this->_helper->getMergeVars($customer);
			
			$merge_vars = Mage::helper('emaildirect/order')->processOrderItems($order, $merge_vars);
			
			$this->_log($merge_vars, "Merge Vars");
			
			Mage::helper('emaildirect/fields')->checkFields();
	      
	      $rc = Mage::getSingleton('emaildirect/wrapper_orders')->addSubscriberOrder($order->getCustomerEmail(), $order, $merge_vars);
			
			if (!isset($rc->ErrorCode))
				$this->setOrderSentToEmailDirect($order);
			
			$this->_log("Process Saved Order End");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
		
		Mage::app()->setCurrentStore($starting_store);
	}
	
	public function orderSaveAfter(Varien_Event_Observer $observer)
	{
		try
		{
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::ORDERS);
			$this->_log("Order Save After Start");
			
			if ($this->_helper->getBatchOnly())
			{
				$this->_log("Skipping... Batch Processing Only");
				return;
			}
			
			$order = $observer->getEvent()->getOrder();
			
			$this->processSavedOrder($order);
			
			$this->_log("Order Save After End");
			
			return;
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}
	
	public function orderSaveAfterAdmin(Varien_Event_Observer $observer)
	{
		try
		{
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::ORDERS);
			$this->_log("Order Save After (Admin) Start");
			
			if ($this->_helper->getBatchOnly())
			{
				$this->_log("Skipping... Batch Processing Only");
				return;
			}
			
			$order = $observer->getEvent()->getOrder();
			
			$this->processSavedOrder($order);
			
			$this->_log("Order Save After (Admin) End");
			
			return;
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}
	
	private function setOrderSentToEmailDirect($order, $date = null)
	{
		try
		{
			if ($date == null)
			{
				$date = date(Mage::getModel('core/date')->gmtTimestamp());
      		$date = date($this->_date_format, $date);
			}
			
			$ed_order = Mage::getModel('emaildirect/order')->saveSent($order, $date);
      }
      catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}
}