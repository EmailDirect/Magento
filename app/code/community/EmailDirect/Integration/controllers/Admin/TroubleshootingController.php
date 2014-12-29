<?php

class EmailDirect_Integration_Admin_TroubleshootingController extends Mage_Adminhtml_Controller_Action
{
	private $_output = array();
	private $_config = null;
	
	public function indexAction()
   {
		$this->loadLayout();
		$this->getLayout()->getBlock('head')->setTitle($this->__('EmailDirect Troubleshooting'));
		$this->_setActiveMenu('system');
		$this->renderLayout();
   }
	
	public function validateAction()
	{
		$apikey = $this->getRequest()->getParam('apikey');
		
		$valid = Mage::helper('emaildirect/troubleshooting')->validateApiKey($apikey);
		
		$result = array('valid' => $valid);
		$this->getResponse()->setBody(json_encode($result));
	}
	
	private function getLogFile()
	{
		$empty = false;
		$too_large = false;
		$file_size = Mage::helper('emaildirect/troubleshooting')->getLogFileSize();
		
		if ($file_size == 0)
			$empty = true;
		else
			$too_large = Mage::helper('emaildirect/troubleshooting')->isLogFileTooLarge();
		
		$max_size = Mage::helper('emaildirect')->formatSize(Mage::helper('emaildirect/troubleshooting')->getMaxLogFileSize());
		
		$result = array(
							'success' 		=> true,
							'empty' 			=> $empty,
							'too_large' 	=> $too_large,
							'max_size'		=> $max_size,
							'contents' 		=> Mage::helper('emaildirect/troubleshooting')->getLogFileContents());
		return $result;
	}
	
	public function ajaxAction()
	{
		$method = $this->getRequest()->getParam('method');
		
		$result = array('success' => false);
		
		switch ($method)
		{
			case "status": $result = $this->getStatus(); break;
			case "log_refresh": $result = $this->getLogFile(); break;
			case "test_logging": $result = $this->testLogging(); break;
			case "erase_log": $result = $this->eraseLog(); break;
		}
		
		$this->getResponse()->setBody(json_encode($result));
		
		return $result;
	}

	private function eraseLog()
	{
		Mage::helper('emaildirect/troubleshooting')->eraseLog();
		
		return $this->getLogFile();
	}
	
	private function testLogging()
	{
		Mage::helper('emaildirect/troubleshooting')->forceLog('======================= LOG TEST =======================');
		
		return $this->getLogFile();
	}
	
	private function getStatus()
	{
		$status = Mage::helper('emaildirect/troubleshooting')->getStatus();
		
		$result = array();
		$result['success'] = true;
		$result['status'] = $status;
		
		return $result;
	}
	
	public function sendAction()
	{
		$params = $this->getRequest()->getParams();
		
		$customer = array();
		
		$customer['Customer Name'] = $params['customer_name'];
		$customer['Email'] = $params['customer_email'];
		if ($params['customer_company'] != "")
			$customer['Company'] = $params['customer_company'];
		$customer['Comments'] = $params['customer_comments'];
		
		$message = Mage::helper('emaildirect/troubleshooting')->getReport($customer);
		
		$to = Mage::helper('emaildirect')->troubleConfig('email');
		$subject = Mage::helper('emaildirect')->troubleConfig('subject');
		$from = $params['customer_email'];
		
		$headers  = "From: {$from}\r\n";
		$headers .= "Content-type: text/html\r\n";

		// now lets send the email.
		$sent = mail($to, $subject, $message, $headers);
		
		if ($sent)
		{
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('emaildirect')->__('Troubleshooting Report was sent to EmailDirect'));
			
			$this->_redirect('adminhtml/system_config/edit/',array('section' => 'emaildirect'));
		}
		else
		{
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('emaildirect')->__('Failed to send Report Email'));
			
			$this->_redirect('ed_integration/admin_troubleshooting/');
		}
	}

	public function downloadAction()
	{
		$output = Mage::helper('emaildirect/troubleshooting')->getReport();
		
		$this->_prepareDownloadResponse(Mage::helper('emaildirect')->troubleConfig('report_file'), $output);
	}

	private function updateConfig($path, $value)
	{
		$this->_config->saveConfig("emaildirect/troubleshooting/{$path}",$value,"default",0);
	}

	public function saveAction()
	{
		$params = $this->getRequest()->getParams();
		
		$this->_config = Mage::getConfig();
		
		$enabled = $params['logging_enabled'];
		
		if ($enabled == "yes")
		{
			$this->updateConfig('logging_enabled',1);
			
			$date_format 	= EmailDirect_Integration_Helper_Data::DATE_FORMAT;
			$start_date = date($date_format, Mage::getModel('core/date')->gmtTimestamp());
   		
			$this->updateConfig("logging_start_date",$start_date);
			
			$advanced_enabled = $params['logging_advanced_enabled'];
			
			if ($advanced_enabled)
			{
				$this->updateConfig("logging_advanced_enabled", 1);
				
				$logging_stores = $params['logging_stores'];
				
				if ($logging_stores == "selected")
				{
					if (isset($params['logging_stores_selected']))
						$this->updateConfig("logging_stores_selected", implode(",",$params['logging_stores_selected']));
				}
				
				$this->updateConfig("logging_stores", $logging_stores);
				
				$logging_areas = $params['logging_areas'];
				
				if ($logging_areas == "selected")
				{
					if (isset($params['logging_areas_selected']))
						$this->updateConfig("logging_areas_selected", implode(",",$params['logging_areas_selected']));
				}
				
				$this->updateConfig("logging_areas", $logging_areas);
				$this->updateConfig("logging_duration", $params['logging_duration']);
			}
			else
			{
				$this->updateConfig("logging_duration", 10);
				$this->updateConfig("logging_advanced_enabled", 0);
			}
		}
		else
			$this->updateConfig('logging_enabled',0);
		
		$this->updateConfig('diagnostic_enabled',$params['diagnostic_enabled']);
		
		$this->_config->cleanCache();
		
		Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('emaildirect')->__('Troubleshooting settings updated!'));
		
		$this->_redirect("*/*/index",Mage::helper('emaildirect')->getAdminUrlParams(array('tab' => 'troubleshooting_view_tabs_trouble_settings')));
	}
}