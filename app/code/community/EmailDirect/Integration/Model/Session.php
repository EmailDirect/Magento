<?php

class EmailDirect_Integration_Model_Session extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		parent::_construct();
		$this->_init('emaildirect/session');
	}
	
	public function loadByMagentoSessionId($magento_session_id)
	{
		if ($data = $this->getResource()->loadByMagentoSessionId($magento_session_id))
			$this->addData($data);
		else
			return false;
		
		return $this;
	}

	public function init($email = null)
	{
		try
		{
			$customer_session = Mage::getSingleton("customer/session");
			
			$magento_session_id = $customer_session->getEncryptedSessionId();
			
			$emaildirect_id = Mage::getModel('core/cookie')->get('ed_id');
			
			$new = false;
			
			if (!empty($emaildirect_id))
				$this->load($emaildirect_id);
			else
			{
				$new = true;
				if (!$this->loadByMagentoSessionId($magento_session_id));
					$this->setMagentoSessionId($magento_session_id);
			}
			
			if ($customer_session->isLoggedIn())
				$this->setCustomerId($customer_session->getId());
			
			if ($email != null)
				$this->setEmail($email);
			
			if ($new)
			{
				$this->save();
				
				Mage::getModel('core/cookie')->set('ed_id', $this->getId(), true, null, null, null, false);
			}
			else
				$this->save();
			
			Mage::getModel('emaildirect/abandoned')->init($this);
		}
		catch (Exception $e)
		{
			Mage::helper('emaildirect')->logException($e);
			Mage::logException($e);
		}
		
		return $this;
	}
}