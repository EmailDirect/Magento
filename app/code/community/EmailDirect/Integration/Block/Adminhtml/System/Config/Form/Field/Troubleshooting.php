<?php

class EmailDirect_Integration_Block_Adminhtml_System_Config_Form_Field_Troubleshooting extends Mage_Adminhtml_Block_System_Config_Form_Field
{	

	private $_helper = null;

	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('emaildirect/system/config/form/field/trouble.phtml');
		$this->_helper = Mage::helper('emaildirect');
	}

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$this->setElement($element);
		$html = $this->_toHtml();
		return $html;
	}
	
	public function getLoggingStatus()
	{
		return Mage::helper('emaildirect/troubleshooting')->getLoggingStatus();
	}
	
	public function getStartDate()
	{
		try
		{
			$date = $this->_helper->troubleConfig('start_date');
			
			$date = Mage::helper('core')->formatTime($date, 'long', true);
		
			$minutes = "<br />(" . $this->_helper->timeElapsed2string(strtotime($date)) . " ago)";
		
			return "{$date} {$minutes}";
		}
		catch (Exception $e)
		{
			$this->logException($e);
			Mage::logException($e);
			return "Unknown/Error";
		}
	}
	
	public function getEDirectInfo()
	{
		$active = $this->_helper->config('active');
		$setup = $this->_helper->config('setup');
		
		$class= 'ab_ok';
		$msg = "Enabled";
		
      if (!$setup)
		{
			$class = 'ab_ng';
			$msg = "Invalid API Key";
		}
		else if (!$active)
		{
			$class = 'ab_ng';
			$msg = "Disabled";
		}
		
		return "<span class='{$class}'>{$msg}</span>";
	}
	
	public function getAbandonedInfo()
	{
		$active = $this->_helper->config('active');
		$setup = $this->_helper->config('setup');
		$sendit = $this->_helper->config('sendabandoned');
      $abandoned_setup = $this->_helper->config('abandonedsetup');
      
		$class= 'ab_ok';
		$msg = "Enabled";
		
      if (!$setup || !$active || !$sendit || !$abandoned_setup)
		{
			$class = 'ab_ng';
			$msg = "Disabled";
		}
			
		return "<span class='{$class}'>{$msg}</span>";
	}
	
	public function getLogInfo()
	{
		try
		{
			$helper = $this->_helper;
			
			$file_size = Mage::helper('emaildirect/troubleshooting')->getLogFileSize();
			$formatted_size = $helper->formatSize($file_size);
			
			$max_size = Mage::helper('emaildirect/troubleshooting')->getMaxLogFileSize();
			
			if ($file_size > $max_size || $file_size == 0)
				$formatted_size = "<span class='log_size_warning'>{$formatted_size}</span>";
			
			$file_date = Mage::helper('emaildirect/troubleshooting')->getLogFilelastUpdate();
			
			if ($file_date == "")
				$last_update = "";
			else
				$last_update = "<br />" . Mage::helper('core')->formatTime(date(EmailDirect_Integration_Helper_Data::DATE_FORMAT,$file_date), 'long', true);
			
			$ago = $helper->timeElapsed2string($file_date);
			
			if ($ago != "")
				$ago = "<br />({$ago} ago.)";
			
			return Mage::helper('emaildirect/troubleshooting')->getLogFileName() . " ({$formatted_size}){$last_update}{$ago}";
		}
		catch (Exception $e)
		{
			$this->logException($e);
			Mage::logException($e);
			return "Unknown/Error";
		}
	}
	
	public function isLoggingEnabled()
	{
		return Mage::helper('emaildirect/troubleshooting')->isLoggingEnabled();
	}
		
}