<?php

class EmailDirect_Integration_SignupController extends EmailDirect_Integration_Controller_Front_Abstract
{
	private $_test_mode = false;
	private $_signup_width = 0;
	private $_signup_height = 0;
	private $_signup_url = "";
	private $_signup_delay = 0;
	private $_signup_opacity = 0;
	
	private $_active = false;
	
	public function checkAction()
	{
		$result = array();
		try
		{
			if ($this->canShow())
			{
				$result['can_show'] = true;
				$result['html_content'] = $this->getSignupHtml();
			}
			else
				$result['can_show'] = false;
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$result['can_show'] = false;
			$result['error'] = $e->getMessage();
		}
		
		$result['test'] = Mage::helper('emaildirect')->isSignupTest();
		$this->getResponse()->setBody(json_encode($result));
	}
	
	private function canShow()
	{
		if (Mage::helper('emaildirect')->isSignupTest())
		{
			$request = Mage::app()->getRequest();
			
			$this->_width = $request->getParam('width');
			$this->_height = $request->getParam('height');
			$this->_url = $request->getParam('url');
			$this->_opacity = $request->getParam('opacity');
		}
		else if (!Mage::helper('emaildirect')->canShowSignup())
			return false;
		else
		{
			$this->_width = Mage::helper('emaildirect')->config('signup_width');
			$this->_height = Mage::helper('emaildirect')->config('signup_height');
			$this->_url = Mage::helper('emaildirect')->config('signup_url');
			$this->_opacity = Mage::helper('emaildirect')->config('signup_opacity');
		}
		
		if ($this->_width == "" || $this->_height == "" || $this->_url == "")
			return false;
		
		return true;
	}
	
	private function getSignupHtml()
	{
		$html = "<style type='text/css'>
#emaildirect_signup_background 
{
   background:rgb(0,0,0);
	background: transparent\9;
	background:rgba(0,0,0,{$this->getSignupOpacity()});
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr={$this->getSignupOpacityHex()},endColorstr={$this->getSignupOpacityHex()});
	zoom: 1;
}
.div:nth-child(n) {
	filter: none;
}
</style>

<div id='emaildirect_signup_background'>
	<div id='emaildirect_signup' style='width: {$this->_width}px; height: {$this->_height}px;'>
		<a id='emaildirect_signup_close' href='#' onclick='return closeSignup();'></a>
		<iframe frameborder='0' src='{$this->_url}' id='ed_form' style='width: 100%; height: 100%;'></iframe>
	</div>
</div>";

		return $html;
	}
	
	private function getSignupOpacity()
	{
		return ((int)$this->_opacity) / 100;
	}
	
	private function getSignupOpacityHex()
	{
		$opacity = $this->getSignupOpacity();
		
		$hex_opacity = dechex(255 * $opacity);
		
		return "#{$hex_opacity}000000";
	}
}