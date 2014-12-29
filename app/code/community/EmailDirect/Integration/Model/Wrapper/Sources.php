<?php

class EmailDirect_Integration_Model_Wrapper_Sources
{
	public function getSources()
	{
		$sources = array();
		$rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand('sources');
		if (isset($rc->ErrorCode))
			return (string) $rc->Message;
		
		if (!isset($rc->Source))
			return $sources;
		
		foreach($rc->Source as $source)
		{
			$newsource = array('id' => (int)$source->SourceID,
							'name' => (string)$source->Name,
							'members' => (int)$source->ActiveMembers,
							'active' => (boolean)$source->IsActive
							);
			$sources[] = $newsource;
		}
		return $sources;
	}
	
	public function addSource($name)
	{
		$xml = "<Source><Name>{$name}</Name><Description>{$name}</Description></Source>";
		$rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand('sources',"",null,$xml);
		return $rc;
	}
}
