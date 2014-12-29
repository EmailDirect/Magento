<?php

class EmailDirect_Integration_Helper_Troubleshooting extends Mage_Core_Helper_Abstract
{	
	const LOG_FILE_NAME 		= 'emaildirect';
	const LOG_FILE_EXT 		= '.log';
	const LOG_LEVEL_HIGH 	= 0;
	const LOG_LEVEL_NORMAL 	= 5;
	const LOG_LEVEL_LOW 		= 100;
	
	const ABANDONED_CART 	= "AB";
	const ORDERS 				= "OR";
	const NEWSLETTER 			= "NL";
	const CUSTOMER 			= "CU";
	const CONFIG 				= "CG";
	const IGNORE 				= "IG";
	const WISHLIST 			= "WL";
	const DEFAULT_AREA		= "ED";
	
	const DISABLED_REASON_PREFIX = "Skipping";

	private $_log_level 			= self::LOG_LEVEL_NORMAL;
	private $_log_level_active = self::LOG_LEVEL_NORMAL;
	private $_log_area 			= self::DEFAULT_AREA;
	private $_current_store 	= null;
	
	private $_output_log			= array();
	
	private $_config_options 	= null;
	
	private $_status 				= array();
	private $_debug_mode 		= false;
	
	private $_debug_execute_mode = "request";
	
	private $_debug_request 	= null;
	private $_debug_response 	= null;
	
	protected $_date_format 	= EmailDirect_Integration_Helper_Data::DATE_FORMAT;
	
	private $_areas 				= array(
												"AB" 	=> "Abandoned Carts",
												"OR"	=> "Orders",
												"NL"	=> "Newsletter",
												"CU"	=> "Customer",
												//"CG"	=> "Configuration",
												"WL"	=> "Wishlist"
												);
	
	public function validateApiKey($apikey, $report_error = false)
	{
		$rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommandDirect($apikey, 'sources');
		if (isset($rc->ErrorCode))
		{
			if ($report_error)
				return (string) $rc->Message;
			return false;
		}
		
		return true;
	}
	
	public function getDebugExecuteMode()
	{
		return $this->_debug_execute_mode;
	}
	
	public function setDebugExecuteMode($mode)
	{
		$this->_debug_execute_mode = $mode;
	}
	
	public function setDebugRequest($request)
	{
		$this->_debug_request = $request;
	}
	
	public function setDebugResponse($response)
	{
		$this->_debug_response = $response;
	}
	
	public function getDebugRequest()
	{
		return $this->_debug_request;
	}
	
	public function getDebugResponse()
	{
		return $this->_debug_response;
	}
	
	public function getAreas()
	{
		return $this->_areas;
	}
	
	public function turnOnDebug()
	{
		$this->_debug_mode = true;
	}
	
	public function turnOffDebug()
	{
		$this->_debug_mode = false;
	}
	
	public function getDebugData()
	{
		return $this->_output_log;
	}
	
	public function safeDump($data, $level = 0)
	{
		try
		{
			$indent = str_repeat("   ",$level);
			
			$output = "";
			
			if (is_object($data))
			{
				$output = get_class($data) . " Object\n(\n";
				$output .= $this->safeDump($data->getData(), $level + 1);
				$output .= ")\n";
			}
			
			if (is_array($data))
			{
				$output = "{$indent}Array\n{$indent}(\n";
				//$inner_indent = $indent . "   ";
				$inner_indent = str_repeat("   ",$level + 1);
				foreach ($data as $key => $value)
				{
					$output .= "{$inner_indent}[{$key}] => ";
					if (is_object($value))
						$output .= get_class($value) . " Object";
					else if (is_array($value))
						$output .= $this->safeDump($value,$level + 1);
					else
						$output .= $value;
					
					$output .= "\n";
				}
				
				$output .= "{$indent})\n";
			}
			
			return $output;
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
	}
	
	private function formatData($data)
	{
		$output = $this->safeDump($data);
		
		$output = str_replace("\n","<br />",$output);
		$output = str_replace("  ","&nbsp;&nbsp;",$output);
		
		return $output;
	}
	
	private function debugFromLog($data)
	{
		if (!$this->_debug_mode)
			return;
		
		$this->debug($data);
	}
	
	public function debug($data)
	{
		if (!$this->_debug_mode)
			return;
		
		if (!is_string($data))
			$this->_output_log[] = $this->formatData($data);
		else
			$this->_output_log[] = $data;
	}
	
	public function debugHeader($header, $level = 2)
	{
		$line = str_repeat('==========================================================',$level);
		//$this->debug($line);
		$this->debug($header);
		$this->debug($line);
		$this->debug('');
	}
		
	
	public function debugXml($data)
	{
		if (!$this->_debug_mode)
			return;
			
		$this->_output_log[] = "<pre>" . htmlentities($this->formatXml($data)) . "</pre>";
	}
	
	public function isDebugMode()
	{
		return $this->_debug_mode;
	}
	
	public function formatXml($xml_string)
	{
		try
		{
			$dom = new DOMDocument('1.0');
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$dom->loadXML($xml_string);
			return $dom->saveXML();
		}
		catch (Exception $e)
		{
			$this->logException($e);
			return $xml_string;
		}
	}
	
	private function getReportTable($heading, $table, $headings = true)
	{
		$output = "<h1>{$heading}</h1>";
		$output .= "<table cellspacing='0' border='1'>";
		
		if ($headings)
			$output .= "<thead>
			<tr class='headings'>
				<th>Setting</th>
				<th>Value</th>
			</tr>
		</thead>";
			
		foreach ($table as $key => $value)
		{
			$output .= "<tr class='border'>
				<td>{$key}</td>
				<td>{$value}</td>
				</tr>";
		}	
		$output .= "</table>";
		
		return $output;
	}
	
	public function getReport($customer = null)
	{
		$environment = $this->getEnvironmentInfo();
		$store_configuration = $this->getConfigurationInfo();
		
		$output = "<html><head></head><body>";
		
		if ($customer != null)
			$output .= $this->getReportTable("Customer Information", $customer, false);
		
		$output .= $this->getReportTable("General Configuration", $environment);
  		
  		foreach ($store_configuration as $code => $configuration)
		{
			$output .= $this->getReportTable("Module Configuration for store: {$code}", $configuration);
		}
  
  		$output .= "<h1>Log File</h1>";
  		$output .= "<pre>" . $this->getLogFileContents() . "</pre>";
  
  		$output .= "</body></html>";
  
		return $output;
	}
	
	private function getArrayData($data)
	{
		$output = "<table cellspacing='0' class='data ed-config-subtable'>
		<thead>
			<tr class='headings'>
				<th>Magento</th>
				<th>EmailDirect</th>
			</tr>
		</thead>";
		
		foreach ($data as $row)
		{
			$output .= "<tbody><tr class='border'><td>{$row['magento']}</td><td>{$row['emaildirect']}</td></tr></tbody>";
		}
		
		$output .= "</table>";
		
		return $output;
	}
	
	public function getEnvironmentInfo()
	{
		$data = array(
				'Magento Version' => Mage::getVersion(),
				'EmailDirect Version' => (string) Mage::getConfig()->getNode('modules/EmailDirect_Integration/version'),
				'Website URL' => Mage::getBaseUrl(),
				'PHP Version' => phpversion(),
				'Server Software' => $_SERVER['SERVER_SOFTWARE']
				);
				
		if (method_exists("Mage","getEdition"))
			$data['Magento Edition'] = Mage::getEdition();
		return $data;
	}
	
	public function getConfigurationInfo()
	{
		$store_data = array();

		$stores = Mage::app()->getStores();
		
		foreach ($stores as $store)
		{
			$e_config = Mage::getStoreConfig("emaildirect/general", $store);
			
			$data = array(	);
			
			$data['store id'] = $store->getId();
			$data['url'] =  Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_SECURE_BASE_URL, $store);
			
			foreach ($e_config as $key => $value)
			{
				if (is_array($value))
				{
					// Skip
				}
				else
				{
					// Check if the data is serialized
					$test_data = @unserialize($value);
					if ($test_data !== false)
						$data[$key] = $this->getArrayData($test_data);
					else
						$data[$key] = $value;;
				}
			}
			
			$store_data[$store->getName()] = $data;
		}
		
		return $store_data;
	}
	
	public function getLogFileContents()
	{
		$log_file = $this->getLogFilePath();
		
		if (!file_exists($log_file))
			return "";
		
		$max_size = $this->getMaxLogFileSize();
		$log_file_size = filesize($log_file);
		$offset = 0;
		
		if ($log_file_size > $max_size)
			$offset = $log_file_size - $max_size;
		
		return htmlentities(file_get_contents($log_file,false,null,$offset));
	}

	public function getLogFilePath()
	{
		return Mage::getBaseDir('log') . DS. self::LOG_FILE_NAME . self::LOG_FILE_EXT;
	}
	
	public function getLogFileName()
	{
		return self::LOG_FILE_NAME . self::LOG_FILE_EXT;
	}

	public function getLogFileSize()
	{
		$log_file = $this->getLogFilePath();
		
		if (file_exists($log_file))
			return filesize($log_file);
		
		return 0;
	}
	
	public function isLogFileTooLarge()
	{
		if ($this->getLogFileSize() > $this->getMaxLogFileSize())
			return true;
		return false;
	}
	
	public function getLoggingStatus()
	{
		$this->checkTimeout();
		
		$enabled = $this->config('logging_enabled') == 1;
		
		$stores = Mage::app()->getStores();
		$stores_selected = $this->arrayConfig("logging_stores_selected");
		
		$areas_selected = $this->arrayConfig("logging_areas_selected");
		
		$date = $this->config('logging_start_date');
		
		$duration = (int)$this->config('logging_duration') * 60;
		
		$trouble_status = array(
			'enabled' => $enabled,
			'start_date' => "",
			'seconds' => "",
			'duration' => $duration,
			'duration_display' => Mage::helper('emaildirect')->getDuration($duration),
			'stores' => array()
		);
		
		if ($date != "")
		{
			$trouble_status['start_date'] = $date;
			
			$trouble_status['elapsed'] = Mage::helper('emaildirect')->timeElapsed2String($date);
			$now = Mage::getModel('core/date')->gmtTimestamp();
			$trouble_status['seconds'] = abs($now - strtotime($date));
		}
		
		foreach ($stores as $store)
		{
			$data = array();
			
			$store_id = $store->getId();
			
			$selected = in_array($store_id,$stores_selected);
			
			$data['id'] = $store_id;
			$data['name'] = Mage::helper('emaildirect')->getFullStoreName($store);
			
			$data['emaildirect_enabled'] = Mage::helper('emaildirect')->config('active',$store) == 1;
			
			if ($enabled)
				$data['logging_enabled'] = $this->isLoggingEnabledForStore($store_id);
			else
				$data['logging_enabled'] = false;
			
			$data['selected'] = $selected;
			
			$trouble_status['stores'][$store_id] = $data;
		}
		
		$areas = array();
		
		foreach ($this->_areas as $area => $label)
		{
			
			if ($enabled == false)
				$area_enabled = false;
			else
				$area_enabled = $this->isLoggingEnabledForArea($area);
			
			$areas[$area] = array(
										'area' => $area,
										'label' => $label, 
										'logging_enabled' => $area_enabled,
										'selected' => in_array($area,$areas_selected)
										);
		}
		
		$trouble_status['areas'] = $areas;
		
		return $trouble_status;
	}
	
	public function getLogFilelastUpdate()
	{
		$log_file = $this->getLogFilePath();
		
		if (file_exists($log_file))
			return filemtime($log_file);
		
		return "";
	}
	
	public function getMaxLogFileSize()
	{
		return $this->config('max_file_size');
	}

	public function config($value)
	{
		return Mage::getStoreConfig("emaildirect/troubleshooting/{$value}", 0);
	}
	
	public function arrayConfig($value)
	{
		$data = $this->config($value);
		
		if ($data == "")
			return array();
		
		return explode(",",$data);
	}
	
	private function updateConfig($path, $value, $scope = "default", $store = 0)
	{
		$config = Mage::getConfig();
		$config->saveConfig("emaildirect/troubleshooting/{$path}",$value,$scope,$store);
		$config->cleanCache();
	}
	
	public function disable()
	{
		$this->updateConfig("logging_start_date","");
		$this->updateConfig("logging_enabled","0");
	}
	
	public function isDiagnosticEnabled()
	{
		return $this->config('diagnostic_enabled') == 1;
	}
	
	private function getConfigOptions()
	{
		if ($this->_config_options == null)
		{
			$options = $this->config('options');
			
			if ($options == "")
				$this->_config_options = "";
			else
				$this->_config_options = unserialize($options);
		}
		
		return $this->_config_options;
	}
	
	private function isLoggingEnabledForArea($area = "")
	{
		if ($area == "")
			$area = $this->_log_area;
		
		if ($this->config('logging_areas') == "all")
			return true;
		
		$areas_selected = $this->arrayConfig("logging_areas_selected");
		
		return in_array($area,$areas_selected);
	}
	
	private function isLoggingEnabledForStore($store = 0)
	{
		//if ($this->config('logging_enabled') == 0)
			//return false;
		
		if ($this->config('logging_stores') == "all")
			return true;
		
		$stores_selected = $this->arrayConfig("logging_stores_selected");
		
		if (is_object($store))
			$store = $store->getId();
		
		return in_array($store,$stores_selected);
	}
	
	private function isLoggingEnabledForIP()
	{
		//if ($this->config('logging_enabled') == 0)
			//return false;
		
		$ip = Mage::helper('core/http')->getRemoteAddr();
		
		$logging_ip = $this->arrayConfig("logging_ip");
		
		if (count($logging_ip) == 0)
			return true;
		
		return in_array($ip,$logging_ip);
	}

	public function isLoggingEnabled($store = 0)
	{
		// First check the global setting
		if (!$this->config('logging_enabled'))
			return false;
		if (!$this->isLoggingEnabledForIP())
			return false;
		if (!$this->isLoggingEnabledForArea())
			return false;
		if (!$this->isLoggingEnabledForStore($store))
			return false;
		return $this->checkTimeout();
	}
	
	private function checkTimeout()
	{
		$date = $this->config('logging_start_date');
		
		if ($date == "" || $date == 0)
		{
			$this->disable();
			return false;
		}
		
		$now = Mage::getModel('core/date')->gmtTimestamp();
		
		$seconds = abs($now - strtotime($date));
		
		$duration = (int)$this->config('logging_duration');
		
		$duration *= 60;
		
		if ($seconds > $duration)
		{
			$this->disable();
			Mage::app()->reinitStores();
			return false;
		}
		
		return true;
	}
	
	public function logAndDebug($data)
	{
		$this->debug($data);
		
		$this->log($data);
	}
	
	public function log($data, $prefix = "", $area = "")
	{
		//$this->debug($data);
		
		$store = Mage::helper('emaildirect')->getCurrentStore();
		
		if ($area == "")
			$area = $this->_log_area;
		
		if ($this->_log_level > $this->_log_level_active)
			return;
		
		if ($this->isLoggingEnabled($store, $area))
		{
			if (is_array($data) || is_object($data))
			{
				if (is_object($data) && get_class($data) == "SimpleXMLElement")
					$data = $this->formatXml($data->asXml());
				else
					$data = $this->safeDump($data);
			}

			if ($prefix != "")
				$prefix .= ": ";

			if ($area != "")
				$data = "[{$area}] [{$store}] {$prefix}{$data}";
			
			$this->forceLog($data);
		}
	}
	
	public function forceLog($data)
	{
		Mage::log($data,null,self::LOG_FILE_NAME . self::LOG_FILE_EXT, true);
	}
	
	public function eraseLog()
	{
		try
		{
			$log_file = Mage::getBaseDir('var') . DS . 'log' . DS . self::LOG_FILE_NAME . self::LOG_FILE_EXT;
			
			if (!file_exists($log_file))
				return true;
			
			unlink($log_file);
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	public function logReason($data)
	{
		$this->log($data,self::DISABLED_REASON_PREFIX);
	}
	
	public function logException($e)
	{
		$this->log($e->__toString(),"Exception");
	}
	
	public function logXml($xml, $prefix = "")
	{
		$this->log($this->formatXml($xml),$prefix);
	}
	
	public function setLogArea($area)
	{
		$this->_log_area = $area;
	}
	
	public function setLogLevel($level)
	{
		$this->_log_level = $level;
	}
	
	public function resetLogLevel()
	{
		$this->_log_level = self::LOG_LEVEL_NORMAL;
	}
}
