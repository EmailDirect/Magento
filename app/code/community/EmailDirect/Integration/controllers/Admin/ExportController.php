<?php

class EmailDirect_Integration_Admin_ExportController extends Mage_Adminhtml_Controller_Action
{
	protected $_min_category_level = 2;
	
	protected $_export_type = null;

	public function countAction()
	{
		$count = "Unknown";
		try
		{
			$from = $this->getRequest()->getParam('from');
			$to = $this->getRequest()->getParam('to');
			$include = $this->getRequest()->getParam('include');
			$store = Mage::app()->getRequest()->getParam('store');
			
			$orders = Mage::helper('emaildirect')->getOrderExportCollection($from, $to, $include, $store);
			
			$count = $orders->getSize();
		}
		catch (Exception $e)
		{
			Mage::logException($e);
		}
		
		$this->getResponse()->setBody($count);
	}

	private function setConfigValue($name)
	{
		$old_value = Mage::helper('emaildirect')->exportConfig($name);
		
		$new_value = $this->getRequest()->getParam($name);
		
		if ($new_value != '' && $new_value != $old_value)
		{
			Mage::getConfig()->saveConfig("emaildirect/export/{$name}", $new_value,"default","default");
			return true;
		}
		
		return false;
	}

	private function setConfiguration()
	{
		$config_options = array('include_disabled','include_already_sent');
		
		$changed = false;
		
		foreach ($config_options as $option)
		{
			if ($this->setConfigValue($option))
				$changed = true;
		}
		
		if ($changed)
		{
			Mage::getConfig()->cleanCache();
			Mage::getConfig()->reinit();
         Mage::app()->reinitStores();
		}
	}

	public function productsAction()
	{
		// Update export configuration options if changed before button click
		$this->setConfiguration();
		
		$this->loadLayout();
		$this->renderLayout();
	}
	
	public function ordersAction()
	{
		// Update export configuration options if changed before button click
		$this->setConfiguration();
		
		$this->loadLayout();
		$this->renderLayout();
	}
	 
	private function getCategoryPath($category)
	{
		$name = "";
		
		while ($category->parent_id != 0 && $category->level >= $this->_min_category_level)
		{
			if ($name != "")
				$name = $category->getName() . "/{$name}";
			else
				$name = $category->getName();
			
			$category = $category->getParentCategory();
		}
		
		return $name;
	}
	 
	private function getProductData($id)
	{
		$product = Mage::getModel('catalog/product')->load($id);

		$product_data = array($product->getName(), $product->getSku());
			
		$product_categories = $product->getCategoryCollection()->exportToArray();
		
		$category_data = array();
		
		foreach($product_categories as $cat)
		{
			$category = Mage::getModel('catalog/category')->load($cat['entity_id']);
			
			$category_data[] = $this->getCategoryPath($category);
		}

		if (count($category_data) > 0)
			$product_data[] = implode(",",$category_data);
		else
			$product_data[] = "";
		
		return $product_data;
	}
	
	private function getOrderData($id)
	{
		$order = Mage::getModel('sales/order')->load($id);
		
		//EmailAddress, OrderNumber, ProductName, SKU, Quantity, PurchaseDate, UnitPrice
		
		$date = $order->getCreatedAt();
      $orderNum = $order->getIncrementId();
		$email = $order->getCustomerEmail();
      
      $items = $order->getAllItems();
      
		$order_data = array();
      
      if (is_array($items))
      {
         foreach($items as $item)
         {
         	
         	if ($item->getParentItemId() != null)
					continue;
         	
				$row = array($email,$orderNum);
				
				$qty = (int)$item->getQtyOrdered();
            $name = $item->getName();
            $sku = $item->getSku();
            $price = $item->getPrice();
            
            $row[] = $name;
				$row[] = $sku;
				$row[] = $qty;
				$row[] = $date;
				$row[] = $price;
            
            $order_data[] = $row;
         }
         
      }
     	return $order_data;
	}
	
	private function saveRow($fields,$name)
	{
		$file = Mage::helper('emaildirect')->getExportFileName($name);
		
		if (file_exists($file))
		{
			$fp = fopen($file, 'a');
		}
		else
		{
			if ($this->getExportType() == 'product')
				$header_fields = array('Product Name','SKU','Root Category');
			else
				$header_fields = array('EmailAddress', 'OrderNumber', 'ProductName', 'SKU', 'Quantity', 'PurchaseDate', 'UnitPrice');
			
			$fp = fopen($file, 'w');
			fputcsv($fp, $header_fields, ',','"');
		}

		fputcsv($fp, $fields, ',','"');
		
		fclose($fp);
	}
	
	private function getMinCategoryLevel()
	{
		$roots = Mage::getModel('catalog/category')->load(1)->getChildren();
		
		if (strpos($roots,',') === FALSE)
			return 2;
		return 1;
	}
	
	private function getExportType()
	{
		if ($this->_export_type == null)
			$this->_export_type = $this->getRequest()->getParam('export_type', 'product');
		
		return $this->_export_type;
	}

	public function batchRunAction()
	{
		if ($this->getExportType() == 'product')
			return $this->batchRunProducts();
		
		return $this->batchRunOrders();
	}
	
	private function batchRunOrders()
	{
		if ($this->getRequest()->isPost()) 
		{
			$order_id = $this->getRequest()->getPost('id', 0);
			$file_name = $this->getRequest()->getPost('filename', 0);
			
			if (is_array($order_id))
			{
				foreach ($order_id as $id)
				{
					$csv_data = $this->getOrderData($id);

					foreach ($csv_data as $data_row)
					{
						$this->saveRow($data_row,"emaildirect_orders_{$file_name}");
					}
				}
				
				$result = array(
                'savedRows' => count($order_id),
                'errors'    => array()
            	);
			}
			else
			{
				$csv_data = $this->getOrderData($order_id);

				foreach ($csv_data as $data_row)
				{
					$this->saveRow($data_row,"emaildirect_orders_{$file_name}");
				}
			
				$result = array(
                'savedRows' => 1,
                'errors'    => array()
            	);
			}
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
		}
	}
	
	private function batchRunProducts()
	{
		$this->_min_category_level = $this->getMinCategoryLevel();
		
		if ($this->getRequest()->isPost()) 
		{
			$product_id = $this->getRequest()->getPost('id', 0);
			$file_name = $this->getRequest()->getPost('filename', 0);
			
			if (is_array($product_id))
			{
				foreach ($product_id as $id)
				{
					$csv_data = $this->getProductData($id);

					$this->saveRow($csv_data,"emaildirect_products_{$file_name}");
				}
				
				$result = array(
                'savedRows' => count($product_id),
                'errors'    => array()
            	);
			}
			else
			{
				$csv_data = $this->getProductData($product_id);

				$this->saveRow($csv_data,"emaildirect_products_{$file_name}");
			
				$result = array(
                'savedRows' => 1,
                'errors'    => array()
            	);
			}
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
		}
	}
	
	public function downloadAction()
	{
		$file_name = "emaildirect_" . $this->getExportType() . "s_" . $this->getRequest()->getParam('filename');
		
		$file = Mage::helper('emaildirect')->getExportFileName($file_name);

		$this->_prepareDownloadResponse(Mage::helper('emaildirect')->getExportFileName($file_name,false), file_get_contents($file));
	}

	public function batchFinishAction()
	{
		if ($this->getRequest()->isPost()) 
		{
			$file_name = $this->getRequest()->getPost('filename', 0);
			$store = $this->getRequest()->getPost('store', 0);
			if ($store != 0)
			{
				$starting_store = Mage::app()->getStore()->getCode();
				Mage::app()->setCurrentStore($store);
			}

			$url = $this->getUrl('*/*/download') . "filename/{$file_name}/export_type/" . $this->getExportType();
			
			$result = array(
                'download_link' => $url,
            );
			
			$ed_url = $this->getUrl('ed_integration/export/download') . "filename/{$file_name}/export_type/" . $this->getExportType();
			$api = Mage::getSingleton('emaildirect/wrapper_ftp');
			$rc = $api->upload($ed_url,"magento_" . $this->getExportType() . "s_{$file_name}.csv");
		
			if (isset($rc->ErrorCode))
				$result['error'] = "EmailDirect Error: (" . (string) $rc->ErrorCode . "): " . (string)$rc->Message;
			
			if ($store != 0)
				Mage::app()->setCurrentStore($starting_store);
			
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
		}	
	}
}