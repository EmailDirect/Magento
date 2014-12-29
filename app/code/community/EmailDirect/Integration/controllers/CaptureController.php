<?php

class EmailDirect_Integration_CaptureController extends Mage_Core_Controller_Front_Action
{	
	public function indexAction()
	{
		$result = array();
		
		if (!$this->getRequest()->isXmlHttpRequest())
		{
			$this->_redirect('*/*/');
			return;
		}
		
		$email = Mage::app()->getRequest()->getParam('email');
		
		if (!Zend_Validate::is($email, 'EmailAddress'))
		{
			$result['success'] = false;
			$result['message'] = "Invalid Email";
		}
		else
		{
			Mage::getSingleton('emaildirect/session')->init($email);
			$result['success'] = true;	
		}
		
		$this->getResponse()->setBody(json_encode($result));
	}
}





