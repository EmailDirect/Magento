<?php

class EmailDirect_Integration_Admin_DiagnosticController extends Mage_Adminhtml_Controller_Action
{
	private $_logger = null;
	private $_order = null;
	private $_quote = null;
	private $_result = array();
	
	public function downloadAction()
	{
		$filename = $this->getRequest()->getParam('filename');
		
		$output = $this->getRequest()->getParam('output');
		
		$this->_prepareDownloadResponse($filename, $output);
	}
	
	public function ajaxAction()
	{
		$this->_logger = Mage::helper('emaildirect/troubleshooting');
		$this->_logger->turnOnDebug();
		
		$method = $this->getRequest()->getParam('method');
		$success = false;
		
		switch ($method)
		{
			// Orders
			case "order_last_items": $success = $this->lastOrderItems(); break;
			case "order_related_items": $success = $this->relatedOrderItems(); break;
			case "order_full": $success = $this->fullOrder(false); break;
			case "order_full_request": $success = $this->fullOrder(); break;
			case "order_custom_fields": $success = $this->customFields(); break;
			case "tracking_info": $success = $this->trackingInfo(); break;
			
			// Abandoned
			case "abandoned_url": $success = $this->AbandonedUrl(); break;
			case "abandoned_custom_fields": $success = $this->AbandonedCustomFields(); break;
			case "abandoned_request": $success = $this->abandonedFull(); break;
			case "abandoned_request_full": $success = $this->abandonedFull(false); break;
		}
		
		$this->_logger->turnOffDebug();
		
		if ($success)
			$this->setSuccess($this->_output, $this->_logger->getDebugData());
		
		$this->getResponse()->setBody(json_encode($this->_result));
	}
		
	private function getCss()
	{
		$css = "<style>

.diagnostic_table { border-collapse: collapse; }
.diagnostic_table tr td { border: solid 1px; padding: 4px; }
.diagnostic_table tr td:first-child { font-weight: bold; }

</style>";
		
		return $css;
	}
	
	private function setSuccess($output, $details)
	{
		$this->_result['success'] = true;
		
		if (is_array($details))
			$this->_result['details'] = implode("<br />",$details) . "<br /><br />";
		else
			$this->_result['details'] = $details;
		
		$this->_result['output'] = $this->getCss() . $output;
		
		return true;
	}
	
	private function setFailure($message)
	{
		$this->_result['success'] = false;
		$this->_result['error'] = true;
		
		$this->_result['message'] = $message;
		
		return false;
	}

	private function debug($data)
	{
		$this->_logger->debug($data);
	}
	
	private function debugHeader($data, $level = 2)
	{
		$this->_logger->debugHeader($data, $level);
	}
	
	private function getOrder()
	{
		if ($this->_order != null)
			return $this->_order;
		
		$order_id = $this->getRequest()->getParam('item_id');
		
		$this->_order = Mage::getModel('sales/order')->load($order_id);
		
		if ($this->_order != null && !$this->_order->getId())
			$this->_order = null;
		else	
			Mage::app()->setCurrentStore($this->_order->getStoreId());
		
		return $this->_order;
	}
	
	private function getQuote()
	{
		if ($this->_quote != null)
			return $this->_quote;
		
		$quote_id = $this->getRequest()->getParam('item_id');
		$store_id = $this->getRequest()->getParam('store_id');
		
		$store = Mage::getModel('core/store')->load($store_id);
		
		$this->_quote = Mage::getModel('sales/quote')->setStore($store)->load($quote_id);
		
		if ($this->_quote != null && !$this->_quote->getId())
			$this->_quote = null;
		else	
			Mage::app()->setCurrentStore($store);
		
		return $this->_quote;
	}
	
	private function decorateArray($array)
	{
		//Zend_debug::dump($array);
		$output = "<table class='diagnostic_table'>";
		$output .= "<tr>";
		$keys = array_keys($array);
		
		foreach ($keys as $key)
		{
			$output .= "<th>{$key}</th>";
		}
		
		$output .= "</tr>";
		
		foreach ($array as $row)
		{
			$output .= "<tr>";
			
			foreach ($row as $data)
			{
				$output .= "<td>{$data}</td>";
			}
			
			$output .= "</tr>";
		}
		
		$output .= "</table>";
		
		return $output;
	}
	
	private function arrayToTable($array)
	{
		$output = "<table class='diagnostic_table' cellspacing='0'>";
		
		foreach ($array as $key => $value)
		{
			$output .= "<tr><td>{$key}</td><td>{$value}</td></tr>";
		}
		
		return $output . "</table>";
	}
	
	private function getNumberedKeys($array)
	{
		$keys = array();
		
		foreach ($array as $key => $value)
		{
			if (strpos($key,"1") === false)
				return $keys;
			
			$keys[] = substr($key,0,strlen($key) - 1);
		}
		
		return $keys;
	}
	
	private function numberedItemsDisplay($array)
	{
		$output = "";
		
		$keys = $this->getNumberedKeys($array);
		
		$first_key = $keys[0];
		
		$count = 1;
		
		while (isset($array["{$first_key}{$count}"]))
		{
			$output .= "<h3>Item # {$count}</h3>";
			
			$output .= "<table class='diagnostic_table' cellspacing='0'>";
			
			foreach ($keys as $key)
			{
				$output .= "<tr><td>{$key}</td><td>" . $array["{$key}{$count}"] . "</td></tr>";
			}
			
			$output .= "</table><br />";
			
			$count++;
		}
		
		
		return $output;
	}
	
	private function lastOrderItems()
	{	
		$order = $this->getOrder();
		
		if ($order == null)
			return $this->setFailure("Order not found");
		
		$this->debugHeader('Last Order Items');
		
		$this->debug('Order #' . $order->getIncrementId());
		
		$merge_vars = array();
		
		$order_helper = Mage::helper('emaildirect/order');
		
		$merge_vars = $order_helper->getMergeOrderItems($order, $merge_vars);
		
		$this->debug('');
		$this->debugHeader('Latest Order Items',1);
		$this->debug($merge_vars);
		
		$this->debugHeader('Last Order Items Complete!');
		
		$this->_output = "<h2>Order Items Diagnostic Results</h2>" . $this->numberedItemsDisplay($merge_vars, 3);
		
		return true;
	}

	private function relatedOrderItems()
	{	
		$order = $this->getOrder();
		
		if ($order == null)
			return $this->setFailure("Order not found");
		
		$this->debugHeader('Related Order Items');
		
		$this->debug('Order #' . $order->getIncrementId());
		$this->debug('');
		
		$merge_vars = array();
		
		$order_helper = Mage::helper('emaildirect/order');
		
		$merge_vars = $order_helper->getRelatedOrderItems($order, $merge_vars);
		
		$this->debugHeader('Related Order Items',1);
		$this->debug($merge_vars);
		
		$this->debugHeader('Related Order Items Complete!');
		
		$this->_output = "<h2>Related Items Diagnostic Results</h2>" . $this->numberedItemsDisplay($merge_vars, 3);
		
		return true;
	}

	private function fullOrder($request_only = true)
	{
		$order = $this->getOrder();
		
		if ($order == null)
			return $this->setFailure("Order not found");
		
		$customer = Mage::helper('emaildirect/order')->getOrderCustomer($order);
		
	   $merge_vars = Mage::helper('emaildirect/order')->getMergeVars($customer);
		
		$merge_vars = Mage::helper('emaildirect/order')->processOrderItems($order, $merge_vars);
	
		//$order_xml = Mage::getSingleton('emaildirect/wrapper_orders')->getOrderXml($order);
		
		//$this->_logger->debugXml($order_xml);
		
		if ($request_only)
			$this->_logger->setDebugExecuteMode('request_only');
		else
			$this->_logger->setDebugExecuteMode('full');
		
		Mage::getSingleton('emaildirect/wrapper_orders')->addSubscriberOrder($order->getCustomerEmail(), $order, $merge_vars);
		
		$this->_output = "<h2>Full Order Diagnostic Results</h2><h3>Request Xml</h3><pre>" . htmlentities($this->_logger->formatXml($this->_logger->getDebugRequest())) . "</pre>";
		
		if (!$request_only)
		{
			$this->_output .= "<br /><h3>Response Xml</h3><pre>" . htmlentities($this->_logger->formatXml($this->_logger->getDebugResponse())) . "</pre>";
		}
		
		return true;
	}
		
	private function customFields()
	{
		$order = $this->getOrder();
		
		if ($order == null)
			return $this->setFailure("Order not found");
		
		$this->debugHeader('Custom Fields Diagnostic Starting');
		
		$customer = Mage::helper('emaildirect/order')->getOrderCustomer($order);
		
		$this->debugHeader('Customer Related Merge Vars',1);
		
	   $customer_vars = Mage::helper('emaildirect/order')->getMergeVars($customer);
		
		$merge_vars = Mage::helper('emaildirect/order')->processOrderItems($order, $merge_vars);
	
		//$order_xml = Mage::getSingleton('emaildirect/wrapper_orders')->getOrderXml($order);
		
		//$this->_logger->debugXml($order_xml);
		
		$this->_output = "<h2>Custom Fields Diagnostic Results</h2><h3>Customer Fields</h3>" . $this->arrayToTable($customer_vars);
		$this->_output .= "<br /><h3>Order Fields</h3>" . $this->arrayToTable($merge_vars);
		
		return true;
	}
	
	private function abandonedUrl()
	{
		$quote = $this->getQuote();
		
		if ($quote == null)
			return $this->setFailure("Quote not found");
		
		$this->debugHeader('Abandoned URL');
		
		//$customer = Mage::helper('emaildirect/order')->getOrderCustomer($order);
		
		//$this->debugHeader('Customer Related Merge Vars',1);
		
	   $url = Mage::helper('emaildirect/abandoned')->getAbandonedUrl($quote);
		
		$this->_output = "<h2>Abandoned Cart Url (Restore Cart)</h2><p>Click on the link to test it (opens in a new window and won't replace customer cart data)</p>URL: <a target='_blank' href='{$url}&test_mode=true'>{$url}</a>";
		
		return true;
	}
	
	private function abandonedCustomFields()
	{
		$quote = $this->getQuote();
		
		if ($quote == null)
			return $this->setFailure("Quote not found");
		
		$this->debugHeader('Custom Fields Diagnostic Starting');
		
		$abandonedDate = $quote->getUpdatedAt();
      
      $merge_vars = array();
      
      $merge_vars['FirstName'] = $quote->getData('customer_firstname');
      $merge_vars['LastName'] = $quote->getData('customer_lastname');
      
      $merge_vars['AbandonedDate'] = $abandonedDate;
      $merge_vars['AbandonedUrl'] = Mage::helper('emaildirect/abandoned')->getAbandonedUrl($quote);
		
		// Setup sequence for this store
		Mage::helper('emaildirect/abandoned')->setupSequence();
		
		Mage::helper('emaildirect/abandoned')->addSequence($merge_vars);
		
		$merge_vars = Mage::helper('emaildirect/order')->getMergeOrderItems($quote, $merge_vars, "AB");
		
		
		
		if (Mage::helper('emaildirect')->config('save_latest_order'))
		{
			$this->debug("Processing Last Order");
			$order = Mage::helper('emaildirect/abandoned')->getLastOrder($quote);
			
			if ($order != null)
				$merge_vars = Mage::helper('emaildirect/order')->processOrderItems($order, $merge_vars);
			else
				$this->debug("Order not found");
				//$this->_log("Order not found");
		}
		
	
		//$order_xml = Mage::getSingleton('emaildirect/wrapper_orders')->getOrderXml($order);
		
		//$this->_logger->debugXml($order_xml);
		
		$this->_output = "<h2>Custom Fields Diagnostic Results</h2>";
		$this->_output .= "<br /><h3>Fields</h3>" . $this->arrayToTable($merge_vars);
		
		return true;
	}

	private function getQuoteEmail($quote)
	{
		$abandoned_cart = Mage::helper('emaildirect/diagnostic')->getAbandonedCart($quote->getId());
		
		return $abandoned_cart->getEmail();
	}

	private function abandonedFull($request_only = true)
	{
		$quote = $this->getQuote();
		
		if ($quote == null)
			return $this->setFailure("Quote not found");
		
		$abandonedDate = $quote->getUpdatedAt();
		
		$email = $quote->getCustomerEmail();
		
		if ($email == null)
			$email = $this->getQuoteEmail($quote);
      
      $merge_vars = array();
      
      $merge_vars['FirstName'] = $quote->getData('customer_firstname');
      $merge_vars['LastName'] = $quote->getData('customer_lastname');
      
      $merge_vars['AbandonedDate'] = $abandonedDate;
      $merge_vars['AbandonedUrl'] = Mage::helper('emaildirect/abandoned')->getAbandonedUrl($quote);
      
      // Setup sequence for this store
		Mage::helper('emaildirect/abandoned')->setupSequence();
		
		Mage::helper('emaildirect/abandoned')->addSequence($merge_vars);
		
		$merge_vars = Mage::helper('emaildirect/order')->getMergeOrderItems($quote, $merge_vars, "AB");
		
		if (Mage::helper('emaildirect')->config('save_latest_order'))
		{
			$this->debug("Processing Last Order");
			$order = Mage::helper('emaildirect/abandoned')->getLastOrder($quote);
			
			if ($order != null)
				$merge_vars = Mage::helper('emaildirect/order')->processOrderItems($order, $merge_vars);
			else
				$this->debug("Order not found");
				//$this->_log("Order not found");
		}
		
		$xml = Mage::getSingleton('emaildirect/wrapper_abandoned')->getOneSubscriber($email,$merge_vars);
		
		if ($request_only)
			$this->_logger->setDebugExecuteMode('request_only');
		else
			$this->_logger->setDebugExecuteMode('full');
		
		$xml = "<Subscribers>{$xml}</Subscribers>";
		
		//Mage::getSingleton('emaildirect/wrapper_orders')->addSubscriberOrder($order->getCustomerEmail(), $order, $merge_vars);
		$rc = Mage::getSingleton('emaildirect/wrapper_abandoned')->sendSubscribers($xml);
		
		$this->_output = "<h2>Abandoned Cart Xml</h2><h3>Request Xml</h3><pre>" . htmlentities($this->_logger->formatXml($this->_logger->getDebugRequest())) . "</pre>";
		
		if (!$request_only)
		{
			$this->_output .= "<br /><h3>Response Xml</h3><pre>" . htmlentities($this->_logger->formatXml($this->_logger->getDebugResponse())) . "</pre>";
		}
		return true;
	}
	
	private function trackingInfo()
	{
		
		return true;
	}
}