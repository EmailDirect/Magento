<?php

class EmailDirect_Integration_Model_Configuration_Observer extends EmailDirect_Integration_Model_Observer_Abstract
{
	private function validateSource($source, $sources, $store)
	{
		if ($source == '')
		{
			$this->_logger->log("validateSource blank");
			$source = "Magento";
			$this->_helper->updateConfig('source', $source, $store);
		}
		
		//check if the source exist		
		$source_id = '';
		foreach($sources as $item)
		{
			if($item['name']==$source)
			{
				$source_id = $item['id'];
				break;
			}
		}
		
		if ($source_id == '')
		{
			$rc = Mage::getSingleton('emaildirect/wrapper_sources')->addSource($source);
			if(!isset($rc->SourceID))
				Mage::throwException("Error adding source");
			else
				$this->_helper->updateConfig('sourceid', $rc->SourceID, $store);
		}
		else
			$this->_helper->updateConfig('sourceid', $source_id, $store);
	}
	
	public function saveConfig(Varien_Event_Observer $observer)
	{
		$this->_logger->setLogArea(EmailDirect_Integration_Helper_Troubleshooting::CONFIG);
		$this->_logger->setLogLevel(EmailDirect_Integration_Helper_Troubleshooting::LOG_LEVEL_LOW);
		$store  = $this->_helper->getStoreId($observer->getEvent()->getStore());
		$post   = Mage::app()->getRequest()->getPost();
		
		$fields = $post['groups']['general']['fields'];
		
		$apikey = isset($fields['apikey']['value']) ? $fields['apikey']['value'] : $this->_helper->config('apikey');
		
		if ($apikey == '')
		{
			$this->_helper->updateConfig('setup', 0, $store);
			$this->_helper->updateConfig('active', false, $store);
			return;
		}
		
		$oldkey = $fields['old_apikey']['value'];
		
		$sources = Mage::getSingleton('emaildirect/wrapper_sources')->getSources();
		
		if (is_string($sources))
		{
			// Only mark the api key invalid if it has changed.
			if ($oldkey != $apikey)
				$this->_helper->updateConfig('setup', 0, $store);
			$e = new Exception("Module setup failed: {$sources}");
			$this->_logger->logException($e);
			Mage::logException($e);
			
			Mage::throwException($e->getMessage());
		}
		
		$this->_helper->updateConfig('setup', true, $store);
		
		$source = isset($fields['source']['value']) ? $fields['source']['value'] : $this->_helper->config('source');

		$this->validateSource($source, $sources, $store);
		
		$force_product = false;
		if (isset($fields['save_latest_order']['value']) && $fields['save_latest_order']['value'] == true)
			$force_product = true;
		
		// Verify all custom fields are present
		Mage::helper('emaildirect/fields')->verifyFields($force_product);
		
		$this->_logger->resetLogLevel();
	}
}