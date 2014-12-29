<?php

class EmailDirect_Integration_AbandonedController extends EmailDirect_Integration_Controller_Front_Abstract
{
	public function restoreAction()
	{
		try
		{
			$coded_cart = Mage::app()->getRequest()->getParam('cart');
			
			$cart_data = unserialize(base64_decode($coded_cart));
			
			$this->clearCart($cart_data['quote']);
			$this->restoreCartItems($cart_data);
			
			$this->_redirect("checkout/cart");
		}
		catch (Exception $e)
		{
			$this->getCheckout()->addError('Unable to Restore your cart');
			Mage::logException($e);
			$this->_redirect("checkout/cart");
		}
	}
	
	private function processBundleOptions($option)
	{
		$option_data = explode("-", $option);
		
		$bundle_option = array();
		$bundle_option_qty = array();
		
		foreach ($option_data as $od)
		{
			$od_parts = explode("=",$od);
			
			$option_id = $od_parts[0];
			
			$products = $od_parts[1];
			
			if (strpos($products,"~"))
			{
				// Single Item with Qty
				$option_products = explode("~",$products);
				
				$bundle_option[$option_id] = $option_products[0];
				
				$bundle_option_qty[$option_id] = $option_products[1];
			}
			else
			{
				// 1 or more items with no qty
				$option_products = explode(":",$products);
				
				foreach ($option_products as $op)
				{
					$bundle_option[$option_id][] = $op;
				}
			}
		}
		
		return array('bundle_option' => $bundle_option, 'bundle_option_qty' => $bundle_option_qty);
	}
	
	private function prepareCartItems($cart_data)
	{
		$id_list = explode(",",$cart_data['id']);
		$qty_list = explode(",",$cart_data['qty']);
		
		$item_list = array();
		$group_list = array();
		$position = 0;
		
		foreach ($id_list as $key => $item_id)
		{
			$position++;
			$qty = $qty_list[$key];
			
			$id_parts = explode('|',$item_id);
			
			// If there is more than just an id then we have a complex product
			if (count($id_parts) > 1)
			{
				$id = $id_parts[0];
				$option = $id_parts[1];
				
				// MAKE SURE WE CAN LOAD THE PRODUCT
				$product = Mage::getModel('catalog/product')->load($id);
				if (!$product)
					continue;
				
				switch ($product->getTypeId())
				{
					case "configurable":
					{
						$item_list[$position] = array(
								'product_type' => 'configurable',
								'product' => $product,
								'qty' => $qty,
								'option' => array('super_attribute' => Mage::helper('emaildirect')->getConfigurableOptions($product,$option))
								);
					} break;
					case "simple": // Grouped simple products
					{
						$parent_product = Mage::getModel('catalog/product')->load($option);
					
						if (!$parent_product)
							continue;
						
						if (!isset($group_list[$option]))
						{
							$group_list[$option] = array(
										'position' => $position,
										'product_type' => 'grouped',
										'product' => $parent_product,
										'option' => array('super_group' => array())
										);
						}
						
						$group_list[$option]['option']['super_group'][$id] = $qty;
					} break;
					case "bundle":
					{
						$item_list[$position] = array(
								'product_type' => 'bundle',
								'product' => $product,
								'qty' => $qty,
								'option' => $this->processBundleOptions($option)
								);
					} break;
				}
			}
			else
			{
				// Simple Product
				
				// MAKE SURE WE CAN LOAD THE PRODUCT
				$product = Mage::getModel('catalog/product')->load($item_id);
				if (!$product)
					continue;
				
				$item_list[] = array(
								'product_type' => 'simple',
								'product' => $product,
								'qty' => $qty
								);
			}
		}
		
		// Merge the group and simple products
		foreach ($group_list as $group)
		{
			$item_list[$group['position']] = $group;
		}
		
		// Sort it so that the order matches the cart order
		ksort($item_list);
		
		return $item_list;
	}
	
	private function restoreCartItems($cart_data)
	{
		$quote = $this->getQuote();
		
		$item_list = $this->prepareCartItems($cart_data);
		
		foreach ($item_list as $item)
		{
			$this->addItemToCart($item);
		}

		// update our totals, save.
		$quote->getBillingAddress();
		$quote->collectTotals();
		$quote->save();
		
		$this->getCheckout()->setQuoteId($quote->getId());
	}
	
	private function addItemToCart($item)
	{
		try
		{
			$product = $item['product'];
			
			$data = array(
					'options' => array()
				);
			
			if (isset($item['qty']))
				$data['qty'] = $item['qty'];
			
			if (isset($item['option']))
			{
				foreach ($item['option'] as $key => $option)
				{
					$data[$key] = $option;
				}
			}

			$quote = $this->getQuote();
		
			// add the product to our quote
			$quote->addProductAdvanced($product, new Varien_Object($data));

			return true;
		} 
		catch (Exception $e)
		{
			Mage::logException($e);
			return false;
		}
	}
	
	private function removeOldQuote($original_quote_id)
	{
		$quote = Mage::getModel('sales/quote')->load($original_quote_id);
		
		if ($quote->getId() == $original_quote_id)
			$quote->delete();
	}
	
	private function clearCart($original_quote_id)
	{
		$customer_session = Mage::getSingleton('customer/session');
		
		$checkout = $this->getCheckout();
		
		$checkout->clear();
		
		$quote = $this->getQuote();
		
		// Check to see if we need to remove the quote
		// if they aren't logged in then we will need to otherwise when they login the items won't match
		$test_mode = Mage::app()->getRequest()->getParam('test_mode') == "true";
		
		if ((!$test_mode && $quote->getId() != $original_quote_id))
			$this->removeOldQuote($original_quote_id);
		
		foreach ($quote->getItemsCollection() as $item) 
		{
			$item->isDeleted(true);
		}
		
		$quote->getBillingAddress();
		$quote->getShippingAddress();
		$quote->getPayment();
		$quote->setEmailDirectAbandonedDate(null);
		
		$customer = $customer_session->getCustomer();
		if ($customer)
			$quote->assignCustomer($customer);
		
		$quote->save();
	}
	
	private function getQuote()
	{
		return Mage::getSingleton('checkout/session')->getQuote();
	}
	
	private function getCheckout()
	{
		return Mage::getSingleton('checkout/session');
	}
}