<?php

class EmailDirect_Integration_Helper_Order extends EmailDirect_Integration_Helper_Data
{
	protected function getTrackingData($order)
	{
		$shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
					->setOrderFilter($order)
					->load();
		foreach($shipmentCollection as $_shipment)
		{
			foreach($_shipment->getAllTracks() as $tracknum)
			{
				return $tracknum->getData();
			}
		}
		
		return null;
	}
	
	public function getTrackingMergeVars($track, $order)
	{
		$this->_logger->log('getTrackingMergeVars Start');
		
		$merge_vars = array();
		$maps = unserialize($this->config('shipping_fields', $order->getStoreId()));
		
		if (!$maps)
			return null;
		
		$this->_logger->log($maps, "Maps");
		
		$this->_logger->log($track->getData(), "Tracking Data");
		
		$this->processMap($merge_vars, $maps, $track);
		
		return $merge_vars;
	}

	protected function getShippingData($order)
	{
		$data = array();
		
		$data['shipping_code'] = $order->getData('shipping_method');
		$data['shipping_description'] = $order->getData('shipping_description');
		
		$track_data = $this->getTrackingData($order);
		
		if ($track_data != null)
		{
			$data['carrier_code'] = $track_data['carrier_code'];
			$data['title'] = $track_data['title'];
			$data['number'] = $track_data['number'];
		}
		
		$shipping_data = new Varien_Object();
		
		$shipping_data->setData($data);
		
		return $shipping_data;
	}

	protected function getOrderMergeVars(&$merge_vars, $order)
	{
		$this->_logger->logAndDebug('getOrderMergeVars (Tracking Data)');
		$maps = unserialize( $this->config('shipping_fields', $order->getStoreId()) );
		
		if ($maps)
		{
			$this->_logger->log($maps, "Maps");
			$shipping_data = $this->getShippingData($order);
			
			$this->_logger->log($shipping_data,'Shipping Data');
			
			$this->processMap($merge_vars, $maps, $shipping_data);
		}
		else
			$this->debug('No Mappings Found');

		return $merge_vars;
	}
	
	public function getOrderCustomer($order)
	{
		$customer = null;
		
		if ($order->getData('customer_is_guest'))
      {
      	$this->_logger->log("Guest Customer");
			
         $customer = new Varien_Object;
         
         $customer->setData('email',$order->getCustomerEmail());
         $customer->setData('firstname',$order->getData('customer_firstname'));
         $customer->setData('lastname',$order->getData('customer_lastname'));
         $customer->setData('store_id',$order->getStoreId());
         
         $customer->setBillingAddress($order->getBillingAddress());
      }
      else
      {
      	$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
         $address = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
         $customer->setBillingAddress($address);
      }
		
		return $customer;
	}
	
	public function processOrderItems($order, &$merge_vars)
	{
		$this->debugHeader("processOrderItems Start",1);
		
		$merge_vars = $this->getOrderMergeVars($merge_vars,$order);
		
		$this->_logger->logAndDebug("Check Save Lastest");
		if ($this->config('save_latest_order'))
		{
			$this->_logger->logAndDebug("Adding Latest Order Information");
			$merge_vars['LastOrderNumber'] = $order->getIncrementId();
			$merge_vars['LastPurchaseDate'] = $order->getData('created_at');
			$merge_vars['LastPurchaseTotal'] = Mage::helper('core')->currency($order->getData('total_paid'), true, false);
			
			$merge_vars = $this->getMergeOrderItems($order, $merge_vars);
			$merge_vars = $this->getRelatedOrderItems($order, $merge_vars);
			$this->_logger->logAndDebug("Finish Save Latest");
		}
		else
			$this->_logger->logAndDebug("Not setup to send latest order info");
		
		return $merge_vars;
	}
	
	protected function getParentOptions($parent_product,$product_id)
	{
		$parent_options = $this->getConfigurableOptions($parent_product,$product_id);
		
		if (count($parent_options) == 0)
			return "";
		
		$options = "";
		
		foreach ($parent_options as $key => $value)
		{
			if ($options == "")
				$options .= "#";
			else
				$options .= "&";
			
			$options .= "{$key}={$value}";
		}
		
		return $options;
	}
	
	protected function getGroupedPrice($grouped_product)
	{
		$min = 0;
		$products = $grouped_product->getTypeInstance()->getAssociatedProducts();
		foreach ($products as $product)
		{
			if ($min == 0)
				$min = $product->getPrice();
			
			$min = min($min, $product->getPrice());
		}
		
		return $min;
	}
	
	protected function getProductImage($product, $parent_product = null)
	{
		try
		{
			$image = "";
			if ($product->getImage() == 'no_selection' || $product->getImage() == "")
			{
				if ($parent_product != null && $parent_product->getImage() != "no_selection" && $parent_product->getImage() != "")
					$image = Mage::getModel('catalog/product_media_config')->getMediaUrl($parent_product->getImage());
			}
			else
				$image = Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getImage());
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logger->logException($e);
		}
		
		return $image;
	}
	
	private function addMergeItem($item_data, $pos, $merge_vars, $prefix = "")
	{
		$name = "";
		$parent_name = "";
		$sku = "";
		$url = "";
		$image = "";
		$cost = "";
		$description = "";
		$parent_item = null;
		$parent_product = null;
		
		if ($item_data != null)
		{
			$product_id = 0;
			
			if (is_array($item_data))
			{
				$item = $item_data['item'];
				
				if (isset($item_data['parent']))
					$parent_item = $item_data['parent'];
			}
			else
				$item = $item_data;
			
			if (is_string($item))
			{
				$product_id = $item;
				
				$product = Mage::getModel('catalog/product')->load($product_id);
				
				if ($product == null || !$product->getId())
					return $merge_vars;
			}
			else
			{
				$product = $this->getProduct($item);
				if ($product == null)
					return $merge_vars; // Can't get product so abort
			}
			
			if ($parent_item != null)
			{
				$parent_product = $this->getProduct($parent_item);
				if ($parent_product == null)
					return $merge_vars; // Can't get product so abort
				
				$parent_name = $parent_product->getName();
				$url = $parent_product->getProductUrl();
				
				if ($parent_product->getTypeId() == 'configurable')
					$url .= $this->getParentOptions($parent_product,$product_id);
			}
			else
				$url = $product->getProductUrl();
			
			$name = $product->getName();
			$sku = $product->getSku();
			
			$image = $this->getProductImage($product, $parent_product);
			
			if (is_string($item))
			{
				if ($product->getTypeId() == 'grouped')
					$cost = $this->getGroupedPrice($product);
				else
					$cost = $product->getPrice();
			}
			else
				$cost = $item->getPrice();
			
			$cost = Mage::helper('core')->currency($cost,true,false);
			$description = $product->getShortDescription();
		}
      
		$merge_vars["{$prefix}ProductName{$pos}"] = $name;
		if ($prefix != 'Related')
			$merge_vars["{$prefix}ParentName{$pos}"] = $parent_name;
		
		$merge_vars["{$prefix}SKU{$pos}"] = $sku;
		$merge_vars["{$prefix}URL{$pos}"] = $url;
		$merge_vars["{$prefix}Image{$pos}"] = $image;
		
		$merge_vars["{$prefix}Cost{$pos}"] = $cost;
		$merge_vars["{$prefix}Description{$pos}"] = $description;
		
		return $merge_vars;
	}

	protected function getRelatedCollection($id_list, $max_count, $grouped_id_list = null)
	{
		$this->debug("getRelatedCollection Start");
		$collection = Mage::getModel('catalog/product_link')
						  ->useRelatedLinks()
                    ->getCollection()
                    ->addFieldToFilter('product_id', array('in' => $id_list))
                    ;
		
		$this->debug("Collection SQL");
		$this->debug($collection->getSelect()->__toString());
		
		// Filter out grouped products from the related list
		if ($grouped_id_list != null && count($grouped_id_list) > 0)
		{
			$this->debug("Merging Grouped ID's to filter");
			$id_list = array_merge($id_list,$grouped_id_list);
			
		}
		
		$this->debug("ID Filters");
		$this->debug($id_list);
		
		$product_ids = array();
		
		// If any of the related products are already in the order we filter them out
		foreach ($collection as $rp)
		{
			$this->debug("");
			$this->debug("-----------------------------");
			$this->debug("Related Product");
			$this->debug($rp->getData());
			$lp_id = $rp['linked_product_id'];
			if (!in_array($lp_id,$id_list))
			{
				$this->debug("Adding Related Product");
				$product_ids[] = $lp_id;
			}
			else
				$this->debug("Related product already in order");
		}
		
		$this->debug("Product IDs");
		$this->debug($product_ids);
		
		$related = Mage::getResourceModel('catalog/product_collection')
						->addFieldToFilter('entity_id', array('in' => $product_ids))
						->setPageSize($max_count);
		
		Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($related);
		
		$this->debug("Related SQL");
		$this->debug($related->getSelect()->__toString());
		
		$related_list = array();
		
		if (count($related) > 0)
		{
			foreach ($related as $rp)
			{
				$related_list[] = $rp;
			}
		}
		
		return $related_list;
	}

	protected function getRelatedProducts($quote)
	{
		$id_list = array();
		$grouped_id_list = array();
		$filter_id_list = array();
		
		foreach ($quote->getAllItems() as $item) 
      {
      	$this->debug("");
			$this->debug("----------------------------------------------------------");
			$type = $item->getProductType();
			$this->debug("Item ID: {$item->getId()}");
			$this->debug("Item Sku: {$item->getSku()}");
			$this->debug("Item Type: {$type}");
			
      	// if it is a grouped product get the parent ID and add it to the list (if not already added)
      	if ($type == 'grouped')
			{
				$grouped_product_id = $this->getGroupedProductId($item);
				
				$this->debug("Grouped Product ID: {$grouped_product_id}");
				
				if (!in_array($grouped_product_id, $grouped_id_list))
      			$grouped_id_list[] = $grouped_product_id;
			}
			
      	if ($product = $this->getProduct($item))
			{
				$product_id = $product->getId();
				if (!in_array($product_id, $id_list))
      			$id_list[] = $product_id;
			}
      }
		
		$this->debug("ID List");
		$this->debug($id_list);
		$this->debug("Grouped ID List");
		$this->debug($grouped_id_list);
		
		$max_count = $this->config('related_fields');
		
		$this->debug("Max Related: {$max_count}");
		
		$this->debug("");
		$this->debug("--------------------------------------------------------------------------");
		$this->debug("Get Related Collection (Non Grouped)");
		$this->debug("");
		
		$related = $this->getRelatedCollection($id_list, $max_count, $grouped_id_list);
		
		$this->debug("");
		$this->debug("# of Related Products Found: " . count($related));
		
		if (count($related) < $max_count && count($grouped_id_list) > 0)
		{
			$this->debug("");
			$this->debug("--------------------------------------------------------------------------");
			$this->debug("Get Related Collection (Grouped)");
			$this->debug("");
			
			// get grouped related
			$grouped_related = $this->getRelatedCollection($grouped_id_list, $max_count - count($related), $id_list);
			
			// Merge the collections
			if (count($grouped_related) > 0)
				return array_merge($related, $grouped_related);
		}
		
		return $related;
	}

	public function getRelatedOrderItems($quote, $merge_vars)
	{
		$prefix = "Related";
		$max_count = $this->config('related_fields');
		
		$this->debug('');
		$this->debug('Getting Related Products');
		
		$related_products = $this->getRelatedProducts($quote);
		
		$count = 0;

		foreach ($related_products as $rp)
		{
			$count++;
			
			if ($count > $max_count)
				break;
			
			$merge_vars = $this->addMergeItem($rp->getId(), $count, $merge_vars, $prefix);
		}
		
		while ($count < $max_count)
		{
			$count++;
			$merge_vars = $this->addMergeItem(null, $count, $merge_vars, $prefix);
		}
		
		return $merge_vars;
	}
	
	protected function getGroupedProductId($item)
	{
		$this->debug('Get Grouped Product Id');
		$options = $item->getProductOptions();
		
		$this->debug('Product Options');
		$this->debug($options);
		
		if (isset($options['super_product_config']))
		{
			$this->debug('Super config found');
			if (isset($options['super_product_config']['product_id']))
			{
				$this->debug('Product Id Found');
				$product_id = $options['super_product_config']['product_id'];
				
  				return $product_id;
			}
			$this->debug('Product Id Not Found!');
		}
		else
		{
			$this->debug('Get Option By Code');
			$option = $item->getOptionByCode('product_type');
			if ($option)
			{
				$this->debug('Option Found');
				$this->debug($option);
				return $option->getProductId();
			}
		}
		
		$this->debug('Unable to get Grouped Product ID');
		
		return false;
	}
	
	protected function getGroupedProduct($item)
	{
		$this->debug('Get Grouped Product');
		$product_id = $this->getGroupedProductId($item);
		
		if ($product_id !== false)
		{
			$this->debug('Loading Product');
			$product = Mage::getModel('catalog/product')->load($product_id);
			
			if ($product->getId())
			{
				$this->debug('Grouped Product Found!');
				return $product;
			}
		}
		
		$this->debug('Unable to get Grouped Product');
		
		return false;
	}
	
	protected function isItemVisible($item)
	{
		$vis_flag = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
		
		if ($item == null)
			return true;
		
		$product = $this->getProduct($item);
		
		if ($product == null)
			return true;
		
		if ($product->getVisibility() != $vis_flag)
			return true;
		
		return false;
	}
	
	protected function processParentItems($quote)
	{
		$this->debug("Processing Parent Items");
		$parent_items = array();
		
		foreach ($quote->getAllItems() as $item)
		{
			$this->debug("");
			$this->debug("----------------------------------------------------------");
			$type = $item->getProductType();
			//Zend_debug::dump($item->getData());
			$this->debug("Item ID: {$item->getId()}");
			$this->debug("Item Sku: {$item->getSku()}");
			$this->debug("Item Type: {$type}");
			if ($type == "configurable" || $type == "bundle")
			{
				$this->debug('*** Adding as Parent Item ***');
				$parent_items[$item->getId()] = $item;
			}
			
			if ($type == "grouped")
			{
				$grouped_product = $this->getGroupedProduct($item);
				
				if ($grouped_product)
				{
					$this->debug('*** Adding Grouped Product to Parent Items ***');
					$parent_item = new Varien_Object;
					$parent_item->setProduct($grouped_product);
					$parent_items[$item->getId()] = $parent_item;
				}
			}
		}
		
		$this->debug('Parent Items Found: ' . count($parent_items));
		
		if (count($parent_items) > 0)
		{
			$this->debug('Parent Items:');
			
			foreach($parent_items as $key => $item)
			{
				$this->debug('-------------------------');
				$this->debug("ID: {$key}");
				if ($item->getId())
					$this->debug("Parent ID: " . $item->getId());
				else
				{
					$parent_product = $this->getProduct($item);
					if ($parent_product)
						$this->debug("Parent ID: " . $parent_product->getId());
					else
						$this->debug("Parent ID: Failed to load product");
				}
			}
		}
		
		return $parent_items;
	}
	
	public function getMergeOrderItems($quote, $merge_vars, $prefix = "")
	{
		$max_count = $this->config('product_fields');
		
		$count = 0;
		
		$item_data = array();
		
		$parent_items = $this->processParentItems($quote);
		
		$this->debug('');
		$this->debug('Processing Order Items');
		
		foreach ($quote->getAllItems() as $item)
		{
			$type = $item->getProductType();
			
			$this->debug('');
			$this->debug("----------------------------------------------------------");
			$this->debug('Item Sku: ' . $item->getSku());
			$this->debug('Item Type: ' . $type);
			
			if ($type == "configurable" || $type == "bundle")
			{
				$this->debug('Skipping Configurable and Bundle Products (this is a parent product)');
				continue;
			}
			
			$item_id = $item->getId();
			
			if ($item->getProductType() == 'grouped')
			{
				$this->debug('Grouped Product');
				
				$parent_id = $item->getId();
			}
			else
			{
				$parent_id = $item->getParentItemId();
			}
			
			$this->debug("Parent Id: {$parent_id}");
			
			if ($parent_id != null && isset($parent_items[$parent_id]))
			{
				$this->debug('Item has a Parent');
				$parent_item = $parent_items[$parent_id];
				
				$this->debug('Checking Visibility of Item and Parent (only one needs to be visible)');
				if ($this->isItemVisible($item) || $this->isItemVisible($parent_item))
				{
					$this->debug('Adding Item to list');
					$item_data[$item_id] = array('item' => $item, 'parent' => $parent_item);
				}
				else
					$this->debug('Visibility check failed');
			}
			else
			{
				$this->debug('Checking Visibility of Item');
				if ($this->isItemVisible($item))
				{
					$this->debug('Adding Item to list');
					$item_data[$item_id] = array('item' => $item);
				}
				else
					$this->debug('Visibility check failed');
			}
		}
		
		foreach ($item_data as $item) 
      {
      	$count++;
			
			if ($count > $max_count)
				break;
			
         $merge_vars = $this->addMergeItem($item, $count, $merge_vars, $prefix);
      }
		
		// Blank out other items
		while ($count < $max_count)
		{
			$count++;
			$merge_vars = $this->addMergeItem(null, $count, $merge_vars, $prefix);
		}
		
		return $merge_vars;
	}
}