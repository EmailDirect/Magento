<?php

class EmailDirect_Integration_Model_Wrapper_Lists
{
	public function getLists()
	{
		$lists = array();
		$rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand('lists');
		
		if (!isset($rc->List))
			return $lists;
		
		foreach($rc->List as $list)
		{
			$newlist = array('id' => (int)$list->ListID,
							'name' => (string)$list->Name,
							'members' => (int)$list->ActiveMembers,
							'active' => (boolean)$list->IsActive
							);
			$lists[] = $newlist;
		}
		return $lists;
	}

	public function listUnsubscribe($list_id, $email)
	{
		$xml = "<Subscribers><EmailAddress>{$email}</EmailAddress></Subscribers>";
		$rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand('lists','RemoveEmails',$list_id,$xml);
		if (isset($rc->ErrorCode))
		{
			Mage::getSingleton('customer/session')->addError((string)$rc->Message);
			Mage::throwException((string)$rc->Message);
		}
		elseif((int)$rc->ContactsSubmitted != (int)$rc->Successes)
		{
			Mage::getSingleton('customer/session')->addError((string)$rc->Failures->Failure->Message);
			Mage::throwException((string)$rc->Failures->Failure->Message);
		}
	}

	public function listSubscribe($list_id, $email)
	{
		// ask if the customer is a subscriber
		$rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand('subscribers',null,$email);
		
		// if already a subscriber
		if (isset($rc->EmailID))
		{
			$xml = "<Subscribers><EmailAddress>{$email}</EmailAddress></Subscribers>";
			$rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand('lists','AddEmails',$list_id,$xml);
		}
	}
}