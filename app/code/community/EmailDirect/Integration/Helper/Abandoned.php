<?php

class EmailDirect_Integration_Helper_Abandoned extends EmailDirect_Integration_Helper_Data
{
	private $_sequences = null;
	private $_current_sequence = 0;
	private $_sequence_enabled = false;
	private $_sequence_field = "";
	
	public function getAbandonedUrl($quote)
   {
      // We are using comma separated lists for the ID's and Quantities so that it takes up less
      // space when we generate the Querystring
      
		$item_data = array();
      
		$this->debug("Get Abandoned URL");
		
      foreach ($quote->getAllItems() as $item)
		{
			$product = $this->getProduct($item);
			$parent_id = $item->getParentItemId();
			$item_id = $item->getId();
			
			if ($parent_id != null)
			{
				$item_data[$parent_id]['option'] = $product->getId();
			}
			else
			{
				$item_data[$item_id]['product_id'] = $product->getId();
				$item_data[$item_id]['qty'] = $item->getQty();
				
				if ($item->getProductType() == 'grouped')
				{
					$option = $item->getOptionByCode('product_type');
					if ($option)
					{
        				$product = $option->getProduct();
						$item_data[$item_id]['option'] = $product->getId();
					}
				}
				else if ($item->getProductType() == 'bundle')
				{
					$option = $item->getOptionByCode('info_buyRequest');
					
					if ($option)
					{
						$data = unserialize($option->getValue());
						
						$bundle_option = "";
						
						foreach ($data['bundle_option'] as $id => $value)
						{
							if ($bundle_option != "")
								$bundle_option .= "-";
							
							$bundle_option .= "{$id}=";
							
							if (is_array($value))
							{
								$bundle_option .= implode(":",$value);
							}
							else
							{
								$qty = $data['bundle_option_qty'][$id];
								$bundle_option .= "{$value}~{$qty}";
							}
						}
						$item_data[$item_id]['bundle_option'] = $bundle_option;
					}
				}
			}
		}
		
		$ids = "";
      $qtys = "";
		
		$this->debug("Item Data:");
		$this->debug($item_data);
		
		foreach ($item_data as $item)
		{
			if ($ids != "")
         {
            $ids .= ",";
            $qtys .= ",";
         }
			
			$ids .= $item['product_id'];
         $qtys .= $item['qty'];
			
			if (isset($item['bundle_option']))
				$ids .= "|{$item['bundle_option']}";
			else if (isset($item['option']))
				$ids .= "|{$item['option']}";
		}
		
		$url_data = array("quote" => $quote->getId(), "id" => $ids, "qty" => $qtys);
		
      //$this->logAndDebug($url_data, "Abandoned Url Data");
      $this->debug("URL Data:");
		$this->debug($url_data);
		
      $url = base64_encode(serialize($url_data));
      
      $url = Mage::getUrl('emaildirect/abandoned/restore',array('_secure'=>true)) . "?cart={$url}";
		
		$this->debug("URL:");
		$this->debug($url);
      
      return $url;
   }

	public function getLastOrder($quote)
	{
		$this->logAndDebug("Get Last Order");
		$customer_id = $quote->getData('customer_id');
		
		$this->logAndDebug("Customer ID: {$customer_id}");
		
		$orders = Mage::getResourceModel('sales/order_collection')
		    ->addFieldToSelect('*')
		    ->addFieldToFilter('customer_id', $customer_id)
		    ->addAttributeToSort('created_at', 'DESC')
		    ->setPageSize(1);
		
		$this->logAndDebug("Order Count: " . $orders->getSize());
		if ($orders->getSize() <= 0)
		{
			$this->logAndDebug("No Orders Found");
			return null;
		}
		
 		$this->logAndDebug("Order Found");
		$order = $orders->getFirstItem();
		
		return $order;
	}
	
	public function addSequence(&$merge_vars)
	{
		if (!$this->_sequence_enabled)
			return;
		
		if (!isset($this->_sequences[$this->_current_sequence]))
			$this->_current_sequence = 0;
		
		$merge_vars[$this->_sequence_field] = $this->_sequences[$this->_current_sequence++];
	}
	
	public function setupSequence()
	{
		$this->_sequence_enabled = $this->config('abandonedsequence_enabled');
		
		if (!$this->_sequence_enabled)
			return;
		
		$sequences = str_replace("\r\n","\n",$this->config('abandonedsequence_options'));
		$this->_current_sequence = $this->config('abandonedsequence_current');
		
		if (!is_numeric($this->_current_sequence))
			$this->_current_sequence = 0;
		
		$this->_sequences = explode("\n",$sequences);
		$this->_sequence_field = $this->config('abandonedsequence_field');
	}
	
	public function saveCurrentSequence()
	{
		if (!$this->_sequence_enabled)
			return;
			
		$this->updateConfig('abandonedsequence_current',$this->_current_sequence);
	}
}	