<?php

class EmailDirect_Integration_Helper_Data extends Mage_Core_Helper_Abstract
{
	const DATE_FORMAT 		= "Y-m-d H:i:s";
	
	const DISABLED_REASON_PREFIX = "Skipping";
	
	protected $_logger = null;
	
	public function __construct()
	{
		$this->_logger = Mage::helper('emaildirect/troubleshooting');
	}
	
	protected function logAndDebug($data)
	{
		$this->_logger->logAndDebug($data);
	}
	
	protected function debug($data)
	{
		$this->_logger->debug($data);
	}
	
	protected function debugHeader($data, $level = 2)
	{
		$this->_logger->debugHeader($data, $level);
	}
	
	public function getMergeVars($customer)
	{
		$merge_vars = array();
		$maps = unserialize( $this->config('map_fields', $customer->getStoreId()) );
		
		if ($maps)
		{
			$this->debug("Customer Field Mapping");
			$this->processMap($merge_vars, $maps, $customer);
		}
		
		$address_maps = unserialize( $this->config('address_fields', $customer->getStoreId()) );
		
		// Process address
		if ($address_maps)
		{
			$address = $customer->getBillingAddress();
			if ($address)
			{
				$this->debug("Address Field Mapping");
		 		$this->processMap($merge_vars, $address_maps, $address);
			}
		}

		return $merge_vars;
	}

	protected function processMap(&$merge_vars,$maps, $data)
   {
		$request = Mage::app()->getRequest();
		
		$this->debug("Mappings");
		$this->debug($maps);
		
		$this->debug("Map Data");
		$this->debug($data);
		
		foreach ($maps as $map)
		{
			// Make sure we have values for both
			if (!isset($map['magento']) || !isset($map['emaildirect']))
				continue;
			
			$custom_field = $map['magento'];
			$emaildirect_field = $map['emaildirect'];

			if ($emaildirect_field && $custom_field)
			{
				switch ($custom_field)
				{
				 	case 'state_code':
				 	{
						$region_id = $data->getData('region_id');
						
						if (!$region_id)
							continue;

						$region = Mage::getModel('directory/region')->load($region_id);
						
						if (!$region)
							continue;
						
						$state_code = $region->getCode();
						
						if ($state_code != "")
							$merge_vars[$emaildirect_field] = $state_code;
				 	} break;
				 	default:
					{
						if (($value = (string)$data->getData(strtolower($custom_field))) || ($value = (string)$request->getPost(strtolower($custom_field))))
							$merge_vars[$emaildirect_field] = $value;
					} break;
				}
			}
		}
	}

	protected function getProduct($item)
	{
		$product_id = $item->getProductId();
		
		if (!$product_id)
			return null;
		
		$product = Mage::getModel('catalog/product')->load($product_id);
		
		if ($product == null || !$product->getId())
			return null;
		
		return $product;
	}
	
	// Used to restore configurable products to the cart
	public function getConfigurableOptions($product, $simple_product_id)
	{
		$type_instance 	= $product->getTypeInstance(true);
		$child_products	= $type_instance->getUsedProducts(null, $product);
		$attrbutes			= $type_instance->getUsedProductAttributes($product);
		
		$super_attrbutes = array();

		foreach ($child_products as $child)
		{
			if ($child->getId() == $simple_product_id)
			{
	    		foreach ($attrbutes as $attribute)
	    		{
	        		$super_attrbutes[$attribute->getAttributeId()] = $child->getData($attribute->getAttributeCode());
	    		}
			}
		}
		
		return $super_attrbutes;
	}
	
	public function getStoreId($code)
	{
		if ($code == null)
			return 0;
		
		try
		{
			$store = Mage::getModel("core/store")->load($code);
			return $store->getId();
		}
		catch (Exception $e)
		{
			return 0;
		}
	}
	
	public function getAdminStore()
	{
		$code = Mage::app()->getRequest()->getParam('store');
		
		return $this->getStoreId($code);
	}
	
	public function getUrlParams()
	{
		$param_options = array('store','group','website');
		
		$request = Mage::app()->getRequest();
		
		foreach ($param_options as $param)
		{
			if ($request->getParam($param) != null)
				return array($param => $request->getParam($param));
		}
		
		return array();
	}
	
	public function forceSubscribe()
	{
		$option = (int)$this->config('checkout_subscribe');
		
		// Force subscribe on
		if ($option == 3)
			return true;
		
		return false;
	}
	
	public function getOrderExportCollection($from, $to, $include, $store = null)
	{
		$from_date = Mage::getModel('core/date')->gmtDate(null,strtotime($from)); // 2010-05-11 15:00:00
		
		$to_date =  Mage::getModel('core/date')->gmtDate(null,strtotime($to)); // 2010-05-11 15:00:00
		
		$orders = Mage::getModel('sales/order')->getCollection()
			->addAttributeToFilter('created_at', array('from' => $from_date, 'to' => $to_date));
		
		$mode = $this->config('send_field');
		
		if ($mode == 'state')
		{
			$states = Mage::helper('emaildirect')->config('send_states');
			$state_list = explode(",",$states);
			$orders->addAttributeToFilter('state', array('in' => $state_list));
		}
		else
		{
			$statuses = Mage::helper('emaildirect')->config('send_statuses');
			$status_list = explode(",",$statuses);
			$orders->addAttributeToFilter('status', array('in' => $status_list));
		}
		
		if ($store != null && $store != 0)
			$orders->addAttributeToFilter('store_id', array('eq' => $store));
		
		if (!$include)
		{
			$orders->getSelect()->joinLeft(array('ed_or' => Mage::getSingleton('core/resource')->getTableName("emaildirect/order")),"ed_or.order_id=main_table.entity_id",array(
					'date_sent' 	=> "date_sent"
	        	));
			$orders->getSelect()->where('`ed_or`.`date_sent` IS NULL');
		}
		
		return $orders;
	}
	
	public function getProductExportCollection($include = false, $store = null)
	{
		$products = Mage::getModel('catalog/product')->getCollection();
		
		if ($include == false)
			$products->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
		
		if ($store != null && $store != 0)
			$products->addStoreFilter($store);
		
		return $products;
	}
	
	public function formatSize($size)
	{
		try
		{
			if ($size == 0 || $size == null || $size == "")
				return "0 b";
			
			$unit = array('b','kb','mb','gb','tb','pb');
			
			$numeric_size = @round($size/pow(1024,($i=$size?floor(log($size,1024)):0)),2);
			
			if (!isset($unit[$i]))
				return "Unknown";
			
			return "{$numeric_size} {$unit[$i]}";
		}
		catch (Exception $e)
		{
			$this->logException($e);
			return "Unknown";
		}
	}
	
	public function getDuration($timeline, $zero = false)
	{
		$periods = array('day' => 86400, 'hour' => 3600, 'minute' => 60);
		
		$ret = "";

		foreach($periods as $name => $seconds)
		{
			$num = floor($timeline / $seconds);
			
			if ($num > 0 || ($num == 0 && $zero == true))
			{
			$timeline -= ($num * $seconds);
			$ret .= $num.' '.$name.(($num > 1 || $num == 0) ? 's' : '').' ';
			}
		}

		return trim($ret);
	}
	
	public function timeElapsed2string($time)
	{
		if ($time == "")
			return "";
		
		if (is_string($time))
			$time = strtotime($time);
		
		$timeline = time() - $time;
		
		return $this->getDuration($timeline);
	}

	public function getExportFileName($name,$full = true)
	{
		$filename = "{$name}.csv";
		
		if ($full)
			return Mage::getBaseDir('export').'/' . $filename;
		
		return $filename;
	}

	public function getApiKey($store = null)
	{
		return $this->config('apikey',$store);
	}
	
	public function isWebsiteConfig()
	{
		$request = Mage::app()->getRequest();
		
		if ($request->getParam('website') && !$request->getParam('store'))
			return true;
		
		return false;
	}
	
	public function updateConfig($path, $value, $store = 0, $base_path = 'general')
	{
		$scope = "stores";
		
		if ($store == null)
			$store = $this->getCurrentStore();
		
		if ($store == 0)
			$scope = "default";
		
		$config = Mage::getConfig();
		$config->saveConfig("emaildirect/{$base_path}/{$path}",$value,$scope,$store);
		$config->cleanCache();
	}
	
	public function deleteConfig($path, $store = 0, $base_path = 'general')
	{
		$scope = "stores";
		
		if ($store == null)
			$store = $this->getCurrentStore();
		
		if ($store == 0)
			$scope = "default";
		
		$config = Mage::getConfig();
		$config->deleteConfig("emaildirect/{$base_path}/{$path}",$scope,$store);
		$config->cleanCache();
	}
	
	public function resetCurrentStore()
	{
		$this->_current_store = null;
	}
	
	public function getCurrentStore()
	{
		$store = Mage::app()->getStore()->getId();
		$config_store = Mage::app()->getRequest()->getParam('store');
		$on_config = $this->onConfigPage();
		
		if ($config_store && $on_config)
			$store = $config_store;
		//else if (!is_null($this->_current_store))
		//	$store = $this->_current_store;
		
		if (is_string($store))
			return $this->getStoreId($store);
		
		return $store;
	}

	private function _config($value, $section, $store = null)
	{
		if (is_null($store))
			$store = $this->getCurrentStore();
		
		//echo "emaildirect/{$section}/{$value}";
		
		$realvalue = Mage::getStoreConfig("emaildirect/{$section}/{$value}", $store);
		
		return $realvalue;
	}

	public function config($value, $store = null)
	{
		return $this->_config($value,'general',$store);
	}
	
	public function exportConfig($value, $store = null)
	{
		return $this->_config($value,'export',$store);
	}
	
	public function troubleConfig($value, $store = null)
	{
		return $this->_config($value,'troubleshooting',$store);
	}
   
   public function getEmailDirectColumnOptions()
   {
		$columns = Mage::getSingleton('emaildirect/wrapper_database')->getAllColumns();
		
		$custom_fields = Mage::helper('emaildirect/fields')->getCustomFields(true);
		
		$invalid_columns = array();
		
		foreach ($custom_fields as $cf)
		{
			$invalid_columns[] = $cf['name'];
		}
		
		$options = array();
		
		if (!isset($columns))
			return $options;
		
		foreach ($columns as $column)
		{
			if ($column->IsCustom == 'true' && !in_array((string)$column->ColumnName,$invalid_columns))
			{
				$key = (string)$column->ColumnName;
				
				$options[$key] = $key;
			}
		}
		
		return $options;
	}
	
	public function getShippingColumnOptions()
   {
		$options = array(
					'shipping_code' => 'Shipping Code',
					'shipping_description' => 'Shipping Description',
					'carrier_code' => 'Tracking Carrier Code',
					'title' => 'Tracking Title', 
					'number' => 'Tracking Number');
		
		return $options;
   }

	public function getDefaultPublication($storeId)
	{
		return $this->config('publication', $storeId);
	}
	
	public function isSignupTest()
	{
		return Mage::app()->getRequest()->getParam('signup_test') == "true";
	}
	
	public function canCapture()
	{
		if (!$this->canEdirect())
			return false;
		
		if ((bool)($this->config('capture_enabled') != 0) && !Mage::helper('customer')->isLoggedIn())
			return true;
			
		return false;
	}

	public function canSendWishlist()
	{
		if (!$this->canEdirect())
			return false;
		
		if (Mage::helper('customer')->isLoggedIn() && (bool)($this->config('wishlist_enabled') != 0))
			return true;
		
		return false;
	}
	
	public function canSendLastLogin()
	{
		if (!$this->canEdirect())
			return false;
		
		if (Mage::helper('customer')->isLoggedIn() && (bool)($this->config('lastlogin_enabled') != 0))
			return true;
		
		return false;
	}

	public function isSignupEnabled()
	{
		if (!$this->canEdirect())
			return false;
		
		if ((bool)($this->config('signup_enabled') == 0) || (bool)($this->config('signup_activated') == 0))
			return false;
		
		return true;
	}
	
	public function canShowSignup()
	{
		if ($this->isSignupTest())
			return true;
		
		if (!$this->isSignupEnabled())
			return false;
		
		$last_closed = Mage::getModel('core/cookie')->get('ed_signup');
		
		$recurrence = $this->config('signup_recurrence');
		
		if ($last_closed == "")
			return true;

		switch ($recurrence)
		{
			case "once": return false;
			default:
				$last_closed = strtotime("+{$recurrence}",$last_closed); break;
		}
		
		if ($last_closed > time())
			return false;
		
		return true;
	}
	
	public function canCheckoutSubscribe()
	{
		if (!$this->canEdirect())
			return false;
		
		return (bool)($this->config('checkout_subscribe') != 0);
	}
	
	public function canEdirect()
	{
		// Necessary?
		//Mage::helper('core')->isModuleOutputEnabled('EmailDirect_Integration');
		
		$active = $this->config('active');
		$setup = $this->config('setup');
		
		if ($active && $setup)
			return true;
		
		return $this->onConfigPage();
	}
	
	public function onConfigPage()
	{
		$controller = Mage::app()->getRequest()->getControllerName();
		
		if ($controller == 'system_config')
			return true;
		
		return false;
	}

	public function registerGuestCustomer($order)
	{
		if (Mage::registry('ed_guest_customer'))
			return;

		$customer = new Varien_Object;

		$customer->setId(time());
		$customer->setEmail($order->getBillingAddress()->getEmail());
		$customer->setStoreId($order->getStoreId());
		$customer->setFirstname($order->getBillingAddress()->getFirstname());
		$customer->setLastname($order->getBillingAddress()->getLastname());
		$customer->setPrimaryBillingAddress($order->getBillingAddress());
		$customer->setPrimaryShippingAddress($order->getShippingAddress());
		Mage::register('ed_guest_customer', $customer, TRUE);
	}
	
	public function getFullStoreName($store)
	{
		if ($store == null)
			return "Unknown";
		
		$store_name = "";
		
		if ($website = $store->getWebsite())
			$store_name .= $website->getName() . " - ";
		
		if ($group = $store->getGroup())
			$store_name .= $group->getName() . " - ";
		
		$store_name .= $store->getName();
		
		return $store_name;
	}
	
	public function getFullStoreNameById($store_id)
	{
		$store = Mage::getModel('core/store')->load($store_id);
		
		return $this->getFullStoreName($store);
	}
	
	public function getAbandonedStatus()
	{
		$stores = Mage::app()->getStores();
		
		$abandoned_status = array(
			'enabled' => false,
			'cron_last_run' => $this->getCronLastRunHtml(),
			'stores' => array()
		);
		
		foreach ($stores as $store)
		{
			$data = array();
			
			$data['id'] = $store->getId();
			$data['name'] = $this->getFullStoreName($store);
			$data['enabled'] = $this->getAbandonedEnabled($store->getId());
			if ($data['enabled'])
				$abandoned_status['enabled'] = true;
			
			$data['last_run'] = $this->getAbandonedLastRunhtml($store->getId());
			
			$abandoned_status['stores'][] = $data;
		}
		
		return $abandoned_status;
	}
	
	public function getCronLastRun($store = null, $config = 'abandoned_last_run')
	{
		$last_run = $this->config($config, $store);
		
		$data = array();
		
		if ($last_run != null)
		{
			$data['last_run'] = $last_run;
			
			$minutes = round(abs(time() - strtotime($last_run)) / 60,0);
			
			$data['minutes'] = $minutes;
			
			if ($minutes > 60)
				$data['class'] = 'ab_ng';
			else
				$data['class'] = 'ab_ok';
			
			$data['last_run_display'] = Mage::helper('core')->formatTime($last_run, 'long', true);
			$data['time_elapsed'] = $this->timeElapsed2string($last_run); 
		}
		else
		{
			$data['last_run'] = 'NEVER';
			$data['minutes'] = -1;
			$data['last_run_display'] = 'NEVER';
			$data['time_elapsed'] = "";
			$data['class'] = 'ab_ng';
		}
		
		return $data;
	}

	private function getLastRunHtml($store = null, $config = 'abandoned_last_run')
	{
		$data = $this->getCronLastRun($store,$config);
		$warning_img = "";
		
		if ($data['class'] == "ab_ng")
			$warning_img = "<a href='" . $this->getAdminUrl("ed_integration/admin_troubleshooting/index") . "#abandoned_carts' target='_blank'><img src='" . Mage::getDesign()->getSkinUrl('images/warning_msg_icon.gif') . "' class='cron_warning' /></a>";
		
		$time_elapsed = "";
		
		if ($data['time_elapsed'] != '')
			$time_elapsed = "({$data['time_elapsed']})";
		
		return "<span class='{$data['class']}'>{$data['last_run_display']} {$time_elapsed}</span>{$warning_img}";
	}

	public function getAbandonedLastRunHtml($store = null)
	{
		return $this->getLastRunHtml($store);
	}
	
	public function getCronLastRunHtml()
	{
		return $this->getLastRunHtml(0, 'cron_last_run');
	}

	public function getAdminUrlParams($params = array())
	{
		$request = Mage::app()->getRequest();
		$url_params = array('website','store');
		
		foreach ($url_params as $up)
		{
			if ($request->getParam($up))
				$params[$up] = $request->getParam($up);
		}
		
		return $params;
	}
	
	public function getAdminUrl($url, $params = array())
	{
		$params = $this->getAdminUrlParams($params);
		
		return Mage::helper("adminhtml")->getUrl($url, $params);
	}
	
	public function getOrdersEnabled()
	{
		$active = $this->config('active');
		$setup = $this->config('setup');
		$sendit = $this->config('sendorder');
		
		if (!$sendit || !$setup || !$active)
			return false;
		
		return true;
	}
	
	public function getBatchEnabled()
	{
		$active = $this->config('active');
		$setup = $this->config('setup');
		$batch = $this->config('batch_enabled');
		
		if (!$batch || !$setup || !$active)
			return false;
		
		return true;
	}
	
	public function getBatchOnly()
	{
		$batch_only = $this->config('batch_only');
		
		if (!$batch_only)
			return false;
		
		return true;
	}
	
	public function getDisabledReason()
	{
		if (!$this->config('active'))
			return "EmailDirect Module is Disabled.";
		
		if (!$this->config('setup'))
			return "EmailDirect Module is not configured with a valid API Key.";
		
		return "";
	}
	
	public function getOrdersDisabledReason()
	{
		$base = $this->getDisabledReason();
		
		if ($base != "")
			return $base;
		
		return "Sending Orders is disabled.";
	}
	
	public function getWishlistDisabledReason()
	{
		$base = $this->getDisabledReason();
		
		if ($base != "")
			return $base;
		
		if (!Mage::helper('customer')->isLoggedIn())
			return "Customer not logged in";
		
		return "Wishlist is disabled.";
	}
	
	public function getLastLoginDisabledReason()
	{
		$base = $this->getDisabledReason();
		
		if ($base != "")
			return $base;
		
		//if (!Mage::helper('customer')->isLoggedIn())
			//return "Customer not logged in";
		
		return "Last Login is disabled.";
	}
	
	public function getBatchDisabledReason()
	{
		$base = $this->getDisabledReason();
		
		if ($base != "")
			return $base;
		
		return "Sending Orders in batch is disabled.";
	}
	
	public function getAbandonedEnabled($store = null)
	{
		$active = $this->config('active', $store);
		$setup = $this->config('setup', $store);
		$sendit = $this->config('sendabandoned', $store);
		$abandoned_setup = $this->config('abandonedsetup', $store);
		
		if (!$sendit || !$setup || !$active || !$abandoned_setup)
			return false;
		
		return true;
	}

	public function getAbandonedDisabledReason()
	{
		$base = $this->getDisabledReason();
		
		if ($base != "")
			return $base;
		
		if (!$this->config('abandonedsetup'))
			return "Abandoned Cart Processing Not Setup.";
		
		return "Sending Abandoned Carts is Disabled";
	}
	
	// Newsletter
	
	private function getSubscriberData($data, $email)
	{
		$properties = Mage::getSingleton('emaildirect/wrapper_subscribers')->getProperties($email);
		$pub_count = 0;
		$list_count = 0;
		$publication_subscribed = false;
		
		if (!isset($properties->Publications->Publication))
			return $data;
		
		foreach($properties->Publications->Publication as $publication)
		{
			if ((int) $publication->PublicationID == $data['publication']['id'])
			{
				$data['publication']['subscribed'] = true;
				$publication_subscribed = true;
				$pub_count++;
			}
		}
		
		$data['count'] = $pub_count;
		
		// Disable the lists if the publication is not subscribed
		if (!$publication_subscribed)
		{
			foreach ($data['lists'] as $list_id => $list)
			{
				$data['lists'][$list_id]['disabled'] = true;
			}
			$data['list_count'] = 0;
			return $data;
		}
		
		if (!isset($properties->Lists->List))
			return $data;
		
		foreach($properties->Lists->List as $list)
		{
			$list_id = (int)$list->ListID;
			
			if (isset($data['lists'][$list_id]))
			{
				$data['lists'][$list_id]['subscribed'] = true;
				$list_count++;
			}
		}
		
		$data['count'] = $pub_count + $list_count;
		$data['list_count'] = $list_count;
		
		return $data;
	}
	
	private function getPublication()
	{
		$general = $this->config('publication');

		$rc = Mage::getSingleton('emaildirect/wrapper_publications')->getPublication($general);
		return array('id' =>(int) $rc->PublicationID,'name'=> (string)$rc->Name, 'subscribed' => false, 'disabled' => false);
	}

	private function getLists()
	{
		$list_data = array();
		$additional_lists = $this->config('additional_lists');
		
		if ($additional_lists == "")
			return $list_data;
		
		$active_lists = explode(",",$additional_lists);
		
		$all_lists = Mage::getSingleton('emaildirect/wrapper_lists')->getLists();
		
		foreach($all_lists as $list)
		{
			if (in_array($list['id'],$active_lists))
				$list_data[$list['id']] = array(
									'id' => $list['id'],
									'name' => $list['name'],
									'subscribed' => false,
									'disabled' => false);
		}
		
		return $list_data;
	}
	
	public function getSubscriptions($email)
	{
		$this->_logger->setLogArea(EmailDirect_Integration_Helper_Troubleshooting::CUSTOMER);
		$this->_logger->setLogLevel(EmailDirect_Integration_Helper_Troubleshooting::LOG_LEVEL_LOW);
		
		$data = array(
				'publication' => array('id' => -1,'name' => 'Unknown', 'subscribed' => false, 'disabled' => true),
				'lists' => array(),
				'count' => 0,
				'list_count' => 0
				);
		
		try
		{
			$data['publication'] = $this->getPublication();
			$data['lists'] = $this->getLists();
			
			$data = $this->getSubscriberData($data, $email);
			
			$this->_logger->resetLogLevel();
			
			return $data;
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->logException($e);
			$this->_logger->resetLogLevel();
			return $data;
		}
	}
}