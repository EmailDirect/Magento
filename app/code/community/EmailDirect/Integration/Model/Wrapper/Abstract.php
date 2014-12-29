<?php

class EmailDirect_Integration_Model_Wrapper_Abstract
{
	protected function getLists($key = "additional_lists")
	{
		$lists = Mage::helper('emaildirect')->config($key);
		
		if ($lists == "")
			return "";
		
		$list_ids = explode(",",$lists);
		
		$list_data = "<Lists>";
		
		foreach ($list_ids as $id)
		{
			$list_data .= "<int>{$id}</int>";
		}
		
		$list_data .= "</Lists>";
		
		return $list_data;
	}
	
	protected function getCustomFields($merge_vars)
	{
		$data = "<CustomFields>";
      
      foreach($merge_vars as $key => $value)
      {
         $data .= "<CustomField><FieldName>{$key}</FieldName><Value><![CDATA[{$value}]]></Value></CustomField>";
      }
		
      $data .= "</CustomFields>";
		
		return $data;
	}
	
	protected function getPublications($key = "publication")
	{
		$publication_id = Mage::helper('emaildirect')->config($key); //Mage::getStoreConfig('emaildirect/general/publication');
		
		return "<Publications><int>{$publication_id}</int></Publications>";
	}
	
	protected function getSource($email)
	{
		$override_source = Mage::helper('emaildirect')->config('override_source');
		$source_id = Mage::helper('emaildirect')->config('sourceid');
		
		if (!$override_source && Mage::getSingleton('emaildirect/wrapper_subscribers')->subscriberExists($email))
			return "";
		
		return "<SourceID>{$source_id}</SourceID>";
	}
}	